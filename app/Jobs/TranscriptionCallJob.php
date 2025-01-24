<?php

namespace App\Jobs;

use App\Models\Call;
use App\Models\Diary;
use App\Models\Person;
use Derrickob\GeminiApi\Data\Content;
use Derrickob\GeminiApi\Data\GenerationConfig;
use Derrickob\GeminiApi\Data\Schema;
use Derrickob\GeminiApi\Gemini;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TranscriptionCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly Call $call)
    {
    }

    public function handle(): void
    {
        dump('start transcription');
        $call = $this->call;

        if(!$call->audio_url || !$call->diaries->count()) {
            return;
        }

        $file = base64_encode(
            file_get_contents(
                urldecode($call->audio_url)
            )
        );

        $client = new Gemini([
            "apiKey" => config('gemini.api_key'),
        ]);

        try {
            $response = $client->models()->generateContent([
                "model" => "models/gemini-2.0-flash-exp",
                "systemInstruction" =>
                    "אני מצרף לך קובץ שמע של שיחה ששדכן מתקשר להורה להציע שידוכ/ים לבנו או בתו, תמלל את השיחה'",
                "generationConfig" => new GenerationConfig(
                    responseMimeType: "application/json",
                    responseSchema: Schema::fromArray([
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'spoken' => [
                                    'type' => 'string',
                                    'enum' => ['הורה', 'שדכן']
                                ],
                                'text' => [
                                    'type' => 'string'
                                ],
                                'time' => [
                                    'type' => 'string'
                                ],
                                'duration' => [
                                    'type' => 'string'
                                ],
                            ],
                            'required' => ['spoken', 'text', 'time', 'duration']
                        ],
                    ]),
                    maxOutputTokens: 8192,
                    temperature: 1,
                    topP: 0.95,
                    topK: 40,
                ),
                "contents" => [
                    Content::createBlobContent(
                        mimeType: "audio/mp3",
                        data: $file,
                        role: "user"
                    ),
                    Content::createTextContent(
                        \Arr::join([
                                "השדכן: $call->user->name",
                                "ההורה: ".$call->phoneModel->model->full_name,
                                "ההצעות אליהם נידון בשיחה: ".$call->diaries->map(function (Diary $diary) {
                                    return $diary->proposal->people->map(function (Person $person) {
                                        return $person->full_name .' בן של '
                                            . ($person->father?->full_name ?? '?') . 'ו '
                                            . ($person->mother?->full_name ?? '')
                                            . " (בת של " . ($person->mother->father?->full_name ?? '?') . ")";
                                    })->join(' עם');
                                })->join(' ו')
                            ]
                            ,' '),
                        role: "user"
                    )
                ]
            ]);

            dump($response, $response->text());
            $call->text_call = $response->text();
            $call->save();

        } catch (\Exception $e) {
           dump($e->getMessage());
            return;
        }
    }
}
