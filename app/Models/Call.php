<?php

namespace App\Models;

use Carbon\Carbon;
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
    use HasFactory;

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

    function updateTextCall()
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
                    topK: 40
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
                                    return $diary->proposal->people->map->full_name->join(' עם ');
                                })->join(' ו')
                            ]
                            ,' '),
                        role: "user"
                    )
                ]
            ]);

            return $response->text();
        }

        return null;
    }

    function refreshCallText(): void
    {
        $this->updateTextCall();
    }

    function getTextCallAttribute()
    {
        return $this->renderCallText();
    }

    function renderCallText($withTimes = true): string
    {

        $text = $this->attributes['text_call'];

        if(!$text || !Str::isJson($text)){
            return 'לא נמצא טקסט לשיחה, יכול להיות שעוד לא פיענחנו?';
        }

        $text = json_decode(json_decode($text, true));

        $markdown = '';

        foreach ($text as $line) {
            $markdown .= "#### **$line->spoken** ";
            $markdown .= '<span  style="color: #5d5d5d; font-size: xx-small; ">$line->time - $line->duration</span>\n';
            $markdown .= ">$line->text\n\n";
        }

        return $markdown;
    }
}
