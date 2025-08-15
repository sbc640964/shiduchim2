<?php

namespace App\Models;

use App\Events\CallActivityEvent;
use App\Jobs\TranscriptionCallJob;
use App\Services\PhoneCallGis\ActiveCall;
use App\Services\PhoneCallGis\CallPhone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Process;

class Call extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'started_at',
        'finished_at',
        'extension',
        'direction',
        'phone',
        'phone_id',
        'audio_url',
        'diary_id',
        'is_pending',
        'data_raw',
        'unique_id',
        'duration',
        'user_id',
        'text_call',
        'transcription_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'extension' => 'integer',
        'phone_id' => 'integer',
        'diary_id' => 'integer',
        'is_pending' => 'boolean',
        'data_raw' => 'array',
        'text_call' => 'json',
    ];

    public static function checkAndFinishOldCalls(): void
    {
        static::query()
            ->whereNull('finished_at')
            ->whereNotNull('extension')
            ->with('user')
            ->where('created_at', '<', now()->subMinutes(2))
            ->get()->each->checkAndFinish();
    }

    public function checkAndFinish(): void
    {
        if($this->finished_at || ! $this->extension) {
            return;
        }

        $state = (new CallPhone)->getExtensionState($this->extension);

        if($state->get('UniqueID') === $this->unique_id
            || $state->get('LinkedID') === $this->unique_id
        ) {
            return;
        }

        if($this->update([
            'finished_at' => now(),
        ]) && $this->user) {
            CallActivityEvent::dispatch($this->user, $this);
        }
    }

    public function diary(): BelongsTo
    {
        return $this->belongsTo(Diary::class);
    }

    public function transcription(): BelongsTo
    {
        return $this->belongsTo(Transcription::class);
    }

    public function diaries(): HasMany
    {
        return $this->hasMany(Diary::class, 'data->call_id');
    }

    public function proposals(): HasMany
    {
        return $this->diaries();
    }

    public function phoneModel(): BelongsTo
    {
        return $this->belongsTo(Phone::class, 'phone_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function activeCall(?bool $onlyStart = true, ?int $extension = null): null|Collection|self
    {
        $extension = $extension ?: auth()->user()->ext;

        if(!$extension) {
            return null;
        }

        $call = self::whereNull('finished_at')
            ->when($onlyStart, fn ($query) => $query->whereNotNull('started_at'))
            ->where('extension', $extension)
            ->latest();

        return $onlyStart ? $call->first() : $call->get();
    }

    public function getSelectOptionHtmlAttribute(?bool $hideCallInfo = false): string
    {
        $phone = $this->phone;
        $name = $this->phoneModel?->model?->full_name;
        $startedAt = $this->started_at->format('H:i:s');

        return \Blade::render(
            <<<'HTML'
            <div class="flex justify-between items-center">
                <div class="flex flex-col">
                    <span class="font-bold text-sm">{{ $phone }}</span>
                    <span class="text-gray-600 text-xs">{{ $name }}</span>
                </div>
                @if($showCallInfo)
                <div class="flex gap-4">
                    <span class="text-gray-600 text-xs">{{ $startedAt }}</span>
                    <span>
                        <x-icon name="{{ $icon }}" class="text-success-600 w-5 h-5"/>
                    </span>
                </div>
                @endif
            </div>
HTML
            ,
            [
                'phone' => $phone,
                'name' => $name,
                'startedAt' => $startedAt,
                'icon' => $this->direction === 'incoming' ? 'iconsax-bul-call-incoming' : 'iconsax-bul-call-outgoing',
                'showCallInfo' => !$hideCallInfo
            ]
        );
    }

    public function forceEnd(): bool
    {
        if(! $this->finished_at){

            return $this->update([
                'finished_at' => now(),
            ]);
        }

        return false;
    }

    function getGroupAttribute(): string
    {

        if($this->direction === 'outgoing'){
            return 'outgoing';
        }

        if($this->direction === 'incoming'){

            if ($this->started_at) {
                return 'incoming';
            }

            if ($this->finished_at) {
                return 'missed';
            }
        }

        return 'unknown';
    }

    static  public function updateModelPhones(?Phone $phone = null): void
    {
        if($phone){
            $calls = self::where('phone', $phone->number)->get();
            $calls->each->update([
                'phone_id' => $phone->id,
            ]);
            return;
        }


        $calls = self::whereNull('phone_id')->get();

        foreach ($calls as $call) {
            $phone = Phone::where('number', $call->phone)->first();

            if ($phone) {
                $call->update([
                    'phone_id' => $phone->id,
                ]);
            }
        }
    }
    public function refreshCallText(): void
    {
        if($this->transcription) {
            $this->transcription->delete();
        }
        TranscriptionCallJob::dispatch($this->id);
    }

    function getTextCallAttribute(): string
    {
        return $this->renderCallText();
    }

    function getRenderCallTextToString(): string
    {
        $json = $this->getCallTextToJson();

        $text = '';

        foreach ($json as $item) {
            $text .= $item->spoken . ': ' . $item->text . PHP_EOL;
        }

        return $text;
    }

    function getCallTextToJson(): ?array
    {
        $chunks = $this->transcription->data['chunks'] ?? null;

        if ($chunks === null) {
            return null;
        }

        return collect($chunks)
            ->pluck('transcription.CallTranscript')
            ->flatten(1)
            ->toArray();
    }

    function getProposalContactsCount(): int
    {
        return $this->phoneModel?->model?->proposal_contacts_count
            ?? $this->phoneModel?->model?->people?->first()?->proposal_contacts_count ?? 0;
    }

    function renderCallText($withTimes = true): string
    {

        $text = $this->getCallTextToJson();

        if (!$text) {
            return $this->transcription
                ? 'ההקלטה בתהליך פיענוח, סטטוס הפיענוח:' . ($this->transcription->data['status_message'] ?? 'לא ידוע')
                : 'לא נמצא טקסט לשיחה, יכול להיות שעוד לא פיענחנו?';
        }

        $blade = <<<'Blade'
        <div class="flex flex-col gap-2 text-xs">
            @php($current = '')
            @foreach($text as $item)
                @if(!$item)
                <div class="flex items-center justify-center p-2 bg-red-100 text-red-600 rounded-lg">
                    <span>לא נמצא טקסט לשיחה, יכול להיות שעוד לא פיענחנו, אולי עוד כמה דקות תנסה שוב, יש מצב?</span>
                </div>
                @else
                    <div @class([
                        "flex flex-col gap-1 p-2 rounded-lg",
                        "bg-gray-100" => $item['spoken'] === 'שדכן',
                        "bg-gray-200" => $item['spoken'] === 'הורה',
                    ])>
                        @if($current !== $item['spoken'])
                            <span class="font-bold">{{ $item['spoken'] }}</span>
                        @endif
                        <span>{{ $item['text'] }}</span>
                    </div>
                    @php($current = $item['spoken'])
                @endif
            @endforeach
        </div>
Blade;

        return \Blade::render($blade, ['text' => $text, 'withTimes' => $withTimes]);
    }

    public function getPersonContact()
    {
        if(!$this->phoneModel->model){
            return null;
        }

        return match ($this->phoneModel->model::class) {
            Person::class => $this->phoneModel->model,
            Family::class => $this->phoneModel->model->people->first(),
            default => null,
        };
    }

    /**
     * @return mixed|null
     */
    public function getPersonContactId(): mixed
    {
        return $this->getPersonContact()?->id ?? null;
    }

    public function extensionWithTarget($html = false)
    {
        $ext = $this->extension;
        $target = data_get($this->data_raw, 'events.0.target_phone');

        $target = ActiveCall::normalizedPhoneNumber($target);


        if(! $target) {
            return filled($ext) ? $ext : 'לא ידוע';
        }

        if(!$html) {
            return $target . ' -> ' . $ext;
        }

        return str("<span class='text-xs text-gray-500'>$target</span> -> <span class='text-xs font-bold text-gray-700'>$ext</span>")->toHtmlString();
    }

    function getDialName(): string
    {
        $model = $this->phoneModel?->model;

        if (! $model) {
            return $this->phone ?? 'אין מספר';
        }

        if ($model instanceof Family) {
            return "משפ' ".$model->name;
        }

        return $model->full_name ?? 'אין שם';
    }

    public function getStatusLabel(): ?string
    {
        if ($this->started_at && $this->finished_at == null) {
            return 'בשיחה';
        }

        if ($this->direction === 'outgoing') {
            return 'מחייג...';
        }

        if (! $this->started_at && ! $this->finished_at) {
            return 'שיחה נכנסת...';
        }

        return 'השיחה הסתיימה';
    }

    public function getDiaries(): \Illuminate\Support\Collection
    {
        $diaries = $this->diaries->collect()->map(function (Diary $item) {

            if(!$item->proposal) {
                return null;
            }

            $girl = $item->proposal->girl;
            $guy = $item->proposal->guy;

            return [
                'id' => $item->id,
                'proposal_id' => $item->proposal_id,
                'description' => $item->data['description'] ?? null,
                'proposal_name' => $guy->full_name.' - '.$girl->full_name,
                'guy_info' => $guy->full_name.' ('.$guy->father?->full_name.' ו'.$guy->mother?->full_name.')',
                'girl_info' => $girl->full_name.' ('.$girl->father?->full_name.' ו'.$girl->mother?->full_name.')',
                'status' => $item->proposal->status,
            ];
        });

        return $diaries
            ->filter()
            ->whereNotNull('description')
            ->groupBy('proposal_id');
    }

    public function splitAudioFile(): array
    {
        $audioPath = urldecode($this->audio_url);
        $maxChunkLength = 60 * 8;
        $outputDir = storage_path("app/chunks/" . $this->id);
        $minChunkLength = 60 * 6;

        //create output directory if it doesn't exist
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // זיהוי רגעי שקט – עם d גבוה יותר (למשל 2 שניות)
        $process = Process::run(
            "ffmpeg -i " .
            escapeshellarg($audioPath) .
            " -af silencedetect=noise=-30dB:d=2 -f null - 2>&1"
        );
        if (!$process->successful()) {
            throw new \Exception("ffmpeg failed: " . $process->errorOutput());
        }
        $output = $process->output();
        preg_match_all("/silence_end: ([0-9.]+)/", $output, $ends);

        // צור רשימת חיתוכים (כולל תחילת הקובץ)
        $cutPoints = [0];
        foreach ($ends[1] as $time) {
            $cutPoints[] = (float)$time;
        }

        // אורך קובץ
        $probe = Process::run(
            "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " .
            escapeshellarg($audioPath)
        );
        $length = (float)trim($probe->output());

        // איחוד צ'אנקים קטנים + שמירה על אורך מרבי
        $chunks = [];
        $last = 0;
        foreach ($cutPoints as $next) {
            if ($next - $last < $minChunkLength) {
                continue; // דלג על צ'אנקים קצרים מדי
            }
            while ($next - $last > $maxChunkLength) {
                $chunks[] = [$last, $last + $maxChunkLength];
                $last += $maxChunkLength;
            }
            if ($next > $last) {
                $chunks[] = [$last, $next];
                $last = $next;
            }
        }
        // אחרון עד סוף קובץ
        if ($last < $length) {
            $chunks[] = [$last, $length];
        }

        // חותך בפועל
        $result = [];
        foreach ($chunks as $i => [$start, $end]) {
            if ($i > 0) {
                $start = max(0, $start - 1);
            }
            $chunkFile = $outputDir . "/chunk_" . $i . ".mp3";
            $duration = $end - $start;
            $cutProcess = Process::run(
                "ffmpeg -y -ss {$start} -t {$duration} -i " .
                escapeshellarg($audioPath) .
                " -acodec copy " .
                escapeshellarg($chunkFile) .
                " 2>&1"
            );
            if (!$cutProcess->successful()) {
                throw new \Exception("ffmpeg chunk failed: " . $cutProcess->errorOutput());
            }
            $result[] = [
                "start" => $start,
                "end" => $end,
                "file" => $chunkFile
            ];
        }

        return $result;
    }
}
