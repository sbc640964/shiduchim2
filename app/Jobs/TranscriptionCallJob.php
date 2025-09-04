<?php

namespace App\Jobs;

use Exception;
use App\Models\Call;
use App\Models\Diary;
use App\Models\Person;
use App\Models\Transcription;
use Arr;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Structured\Response;
use Prism\Prism\ValueObjects\Media\Audio;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class TranscriptionCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Call $call;

    public function __construct(protected int $callId, protected ?int $chunkIndex = null)
    {
        $this->call = Call::findOrFail($this->callId);
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $call = $this->call;

        if(!$call->audio_url) {
            // If there is no audio URL, we cannot proceed with transcription
            return;
        }

        $transcription = $call->transcription;

        if($transcription && in_array($transcription->status, ['completed', 'failed'])) {
            // If transcription already exists and is completed or failed, we do not need to process it again
            return;
        }

        if(!$transcription) {
            $transcription = Transcription::create([
                'status' => 'splitting',
                'data' => ['status_message' => 'Splitting audio file into chunks for transcription.'],
                'current_step' => 0,
                'total_steps' => 0,
            ]);

            $call->transcription()->associate($transcription); // ימלא את transcription_id
            $call->save();

            //split the audio file to 8-12 minute chunks
            $chunks = $call->splitAudioFile();

            if (!$chunks || !is_array($chunks) || count($chunks) === 0) {
                $transcription->setStatus('failed', 'Failed to split audio file.');
                return;
            }

            $transcription->total_steps = count($chunks);
            $transcription->current_step = 1; // Start from the first chunk
            $transcription->setStatus('split', 'Audio file split into chunks.', [
                'chunks' => $chunks,
            ]);

            if($this->chunkTranscription()) {
                if($transcription->total_steps > $transcription->current_step) {
                    static::dispatch($this->callId);
                }
            }

            return;
        }

        if ($this->chunkIndex) {

            return;
        }

        if (in_array($transcription->status, ['transcribing', 'split'])) {
            if($this->chunkTranscription()) {
                if($transcription->total_steps >= $transcription->current_step)
                    static::dispatch($this->callId);
            }
        }

        if($transcription->status === 'completed_transcription') {
            $this->deleteChunksFiles();
        }
    }

    public function chunkTranscription(): bool
    {
        $this->loadingRelations();

        $call = $this->call;

        $transcription = $call->transcription;

        if($transcription->status !== 'transcribing') {
            $transcription->setStatus('transcribing', 'Transcribing the each chunk of audio file.');
        }

        $currentStep = $this->chunkIndex ?? ($transcription->current_step - 1);

        $currentStepChunk = $transcription->data['chunks'][$currentStep] ?? null;

        if (!$currentStepChunk) {
            $transcription->setStatus('failed', 'No chunk found for current step.');
            return false;
        }

        try {
            $response = $this->sendToLLM($currentStepChunk);

            if($response->finishReason !== FinishReason::Stop) {
                $transcription->setStatus('failed', 'Transcription failed, ' . match ($response->finishReason) {
                        FinishReason::Error => 'An error occurred during transcription.',
                        FinishReason::Length => 'The response exceeded the maximum length.',
                        FinishReason::ContentFilter => 'The content was filtered out.',
                        FinishReason::ToolCalls => 'Tool calls were made during transcription.',
                        FinishReason::Other => 'An unknown issue occurred.',
                        default => 'The finish reason is unknown.',
                    }, chunkIndex: $this->chunkIndex);
                return false;

            }

            $transcription->addChunkTranscription(
                $response->structured,
                $this->chunkIndex,
            );

        } catch (Exception $e) {
            $transcription->setStatus('failed', 'Transcription failed with exception: ' . $e->getMessage(), chunkIndex: $this->chunkIndex);
            return false;
        }

        return true;
    }

    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'TranscriptionCall',
            description: 'Schema for the transcription of a phone call',
            properties: [
                new ArraySchema(
                    name: 'CallTranscript',
                    description: 'Transcript of a phone call',
                    items: new ObjectSchema(
                        name: 'item',
                        description: 'A single item in the call transcript',
                        properties: [
                            new EnumSchema(
                                name: 'spoken',
                                description: 'Who is speaking in the call',
                                options: ['הורה', 'שדכן'],
                            ),
                            new StringSchema(
                                name: 'text',
                                description: 'The text spoken in the call',
                            ),
                            new StringSchema(
                                name: 'time',
                                description: 'The time in milliseconds the text was spoken, add the milliseconds of the beginning of the episode (because it is connected in one part of the recording out of several)',
                            ),
                            new StringSchema(
                                name: 'duration',
                                description: 'The duration of the text spoken',
                            ),
                        ],
                        requiredFields: ['spoken', 'text', 'time', 'duration'],
                    ),
                ),
            ],
            requiredFields: ['CallTranscript'],
        );
    }

    public function getPrompt(): string
    {
        return Arr::join([
                $this->call->user ? "השדכן: " . $this->call->user->name : '',
                $this->call->phoneModel?->model ? "ההורה: " . $this->call->phoneModel->model->full_name : '',
                $this->call->diaries->count() ? "ההצעות אליהם נידון בשיחה: " . $this->call->diaries->map(function (Diary $diary) {
                        return $diary->proposal->people->map(function (Person $person) {
                            return $person->full_name . ' בן של '
                                . ($person->father?->full_name ?? '?') . 'ו '
                                . ($person->mother?->full_name ?? '')
                                . " (בת של " . ($person->mother->father?->full_name ?? '?') . ")";
                        })->join(' עם');
                    })->join(' ו') : '',
            ]
            , ' ');
    }

    public function getAudioBase64(array $chunk): Audio
    {
        if(!isset($chunk['file']) || !file_exists($chunk['file'])) {
            throw new Exception("Audio file does not exist for chunk: " . json_encode($chunk));
        }

        return Audio::fromLocalPath($chunk['file']);
    }

    public function sendToLLM(array $chunk): ?Response
    {
        $schema = $this->getSchema();
        $inputTextPrompt = $this->getPrompt();
        $audio = $this->getAudioBase64($chunk);
        $systemPrompt = "אתה עוזר לשדכן לכתוב את תמלול השיחה עם ההורה. " .
            "השיחה מתבצעת בעברית, התמלול צריך להיות בעברית, " .
            "התמלול צריך להיות מפורט ככל האפשר, כולל שמות של אנשים, תאריכים, מקומות וכו'. " .
            "אם יש משהו לא ברור, תכתוב [לא ברור], " .
            "אם יש לך ספק לגבי משהו, תכתוב [לא הבנתי], ".
            "אם לא צורפה לך הקלטה, תכתוב [לא צורפה הקלטה]. " .
            "כשאתה מחשב את המילישניות של הtime תוסיף לו " . ($chunk['start'] * 1000) . " מילישניות שבו מתחיל החלק הנוכחי של ההקלטה";

        return Prism::structured()
            ->using(Provider::Gemini, 'gemini-2.5-flash')
            ->withMaxTokens(65536)
//                ->using(Provider::OpenAI, 'gpt-4o-transcribe')
//                ->withInput($audio)
            ->withSchema($schema)
            ->withSystemPrompt($systemPrompt)
//                ->withPrompt($inputTextPrompt, [$audio])
            ->withProviderOptions([
                'thinkingBudget' => 0
//                    'temperature' => 0.5,
//                    'language' => 'he',
            ])
            ->withClientOptions([
                'timeout' => 120
            ])
            ->withMessages([
                new UserMessage(
                    $inputTextPrompt,
                    additionalContent: [
                        $audio,
                    ],
                ),
            ])
            ->asStructured();
    }


    public function loadingRelations(): void
    {
        $this->call->load(['diaries' => fn($q) => $q
            ->whereHas("proposal", fn($qq) => $qq->whereHas("people"))])
            ->with(['proposal.people.father', 'proposal.people.mother.father'])
        ;
        $this->call->loadMissing('phoneModel.model', 'user');
    }

//    public function middleware(): array
//    {
//        return [
//            new WithoutOverlapping(),
//        ];
//    }
    private function deleteChunksFiles()
    {
        //remove transcription directory
        $transcriptionDir = storage_path('app/chunks/' . $this->call->id);

        if (is_dir($transcriptionDir)) {
            $files = glob($transcriptionDir . '/*'); // get all file names
            foreach ($files as $file) { // iterate files
                if (is_file($file)) {
                    unlink($file); // delete file
                }
            }
            rmdir($transcriptionDir); // remove directory
        }
    }
}
