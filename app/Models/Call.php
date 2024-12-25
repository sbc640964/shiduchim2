<?php

namespace App\Models;

use Carbon\Carbon;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'user_id'
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

    function startChat()
    {
        if(! $this->audio_url){
            return;
        }

        $data = [

        ];

    }
}
