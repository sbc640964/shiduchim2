<?php

namespace App\Models;

use App\Events\CallActivityEvent;
use App\Services\PhoneCallGis\ActiveCall;
use App\Services\PhoneCallGis\CallPhone;
use Derrickob\GeminiApi\Data\Content;
use Derrickob\GeminiApi\Data\GenerationConfig;
use Derrickob\GeminiApi\Data\Schema;
use Derrickob\GeminiApi\Gemini;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Str;

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
        'text_call'
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

    private function updateTextCall(): void
    {
        $text = $this->getParserTheCallText();

        if($text){
            $this->text_call = $text;
            $this->save();
        }
    }

    function getParserTheCallText(): ?string
    {
        if($this->audio_url && $this->diaries->count()){
            $file = base64_encode(
                file_get_contents(
                    urldecode($this->audio_url)
                )
            );

            $client = new Gemini([
                "apiKey" => config('gemini.api_key')
            ]);

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
                                "השדכן: $this->user->name",
                                "ההורה: ".$this->phoneModel->model->full_name,
                                "ההצעות אליהם נידון בשיחה: ".$this->diaries->map(function (Diary $diary) {
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

            dump($response->text());
            return $response->text();
        }

        return null;
    }

    public function refreshCallText(): void
    {
        $this->updateTextCall();
    }

    function getTextCallAttribute()
    {
        return $this->renderCallText();
    }

    function getRenderCallTextToString()
    {
        $json = $this->getCallTextToJson();

        $text = '';

        foreach ($json as $item) {
            $text .= $item->spoken . ': ' . $item->text . PHP_EOL;
        }

        return $text;
    }

    function getCallTextToJson()
    {
        $text = $this->attributes['text_call'];

        if(!$text || !Str::isJson($text)){
            return null;
        }

        return json_decode(json_decode($text, true));
    }

    function getProposalContactsCount(): int
    {
        return $this->phoneModel?->model?->proposal_contacts_count
            ?? $this->phoneModel?->model?->people?->first()?->proposal_contacts_count ?? 0;
    }

    function renderCallText($withTimes = true): string
    {

        $text = $this->getCallTextToJson();

        if(!$text){
            return 'לא נמצא טקסט לשיחה, יכול להיות שעוד לא פיענחנו?' ;
        }

        $blade = <<<'Blade'
        <div class="flex flex-col gap-2 text-xs">
            @php($current = '')
            @foreach($text as $item)
                <div @class([
                    "flex flex-col gap-1 p-2 rounded-lg",
                    "bg-gray-100" => $item->spoken === 'שדכן',
                    "bg-gray-200" => $item->spoken === 'הורה',
                ])>
                    @if($current !== $item->spoken)
                        <span class="font-bold">{{ $item->spoken }}</span>
                    @endif
                    <span>{{ $item->text }}</span>
                </div>
                @php($current = $item->spoken)
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
            return $this->phone;
        }

        if ($model instanceof Family) {
            return "משפ' ".$model->name;
        }

        return $model->full_name;
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
}
