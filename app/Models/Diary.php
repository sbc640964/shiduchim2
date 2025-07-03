<?php

namespace App\Models;

use App\Filament\Clusters\Settings\Pages\Statuses;
use App\Filament\Resources\ProposalResource;
use App\Models\Traits\HasActivities;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use wapmorgan\Mp3Info\Mp3Info;

class Diary extends Model
{
    use HasActivities,
        HasJsonRelationships;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'model_type',
        'model_id',
        'proposal_id',
        'data',
        'type',
        'created_by',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'proposal_id' => 'integer',
        'data' => 'array',
        'created_by' => 'integer',
    ];

    protected $touches = ['proposal'];

    protected static function booted(): void
    {
        static::created(function (Diary $model) {
            $proposal = $model->proposal;
            $otherProposalsUsers = $proposal->users()->where('user_id', '!=', $model->created_by)->get();

            //send notification to other matchmakers in the proposal
            Notification::make()
                ->title('התקדמות בהצעה')
                ->body(auth()->user()->name . " הוסיף תיעוד בהצעת שידוך שמשותפת אתך " . $proposal->guy->full_name . ' ו' . $proposal->girl->full_name)                ->icon('iconsax-bul-notification-bing')
                ->iconColor('primary')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('get_to_page')
                        ->label('פתח הצעת שידוך')
                        ->markAsRead()
                        ->url(ProposalResource::getUrl('view', ['record' => $proposal->getKey()]))
                ])
                ->broadcast($otherProposalsUsers);

            Notification::make()
                ->title('התקדמות בהצעה')
                ->body(auth()->user()->name . " הוסיף תיעוד בהצעת שידוך שמשותפת אתך " . $proposal->guy->full_name . ' ו' . $proposal->girl->full_name)
                ->actions([
                    \Filament\Notifications\Actions\Action::make('get_to_page')
                        ->label('פתח הצעת שידוך')
                        ->markAsRead()
                        ->url(ProposalResource::getUrl('view', ['record' => $proposal->getKey()]))
                ])
                ->sendToDatabase($otherProposalsUsers, isEventDispatched: true);

        });
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class, 'data->call_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*************** ATTRIBUTES ***************/

    public function getLabelTypeAttribute(): string
    {
        return match ($this->type) {
            'call' => 'שיחה',
            'document' => 'מסמך/תמונה',
            'email' => 'דוא"ל',
            'meeting' => 'פגישה',
            'message' => 'הודעה',
            'note' => 'הערה',
            default => 'אחר',
        };
    }

    public function getFilesAttribute(string|array|null|bool $items = null): ?Collection
    {
        if ($items === true) {
            return null;
        }

        $items = (! $items) ? $this->data['files'] ?? null : $items;

        if (empty($items)) {
            return null;
        }

        return collect(\Arr::wrap($items))->map(function ($file) {

            $name = $file;

            if (is_array($file)) {
                $name = $file['name'];
                $file = $file['path'];
            }

            if (\Str::startsWith($urlDecode = trim(urldecode($file)), 'https://api.phonecall')) {
                $data = [
                    'type' => 'mp3',
                    'url' => $urlDecode,
                    'name' => $name,
                ];
            } else {
                $data = [
                    'type' => \Str::after($file, '.'),
                    'url' => \Storage::url($file),
                    'name' => $name,
                ];
            }

            //            if ($data['type'] === 'mp3') {
            //                $data['duration'] = (new Mp3Info(storage_path('app/public/'.$file), true))->duration;
            //            }

            return $data;
        });
    }

    public function getFileAttribute()
    {
        return $this
            ->getFilesAttribute($this->data['file'] ?? true)
            ?->first() ?? null;
    }

    public function getFilesCountAttribute(): ?int
    {
        return $this->getFilesAttribute()?->count() ?? 0;
    }

    /**************** METHODS ***************/

    public function getDiaryTypeIcon(): string
    {
        return match ($this->type) {
            'call' => 'heroicon-o-phone',
            'document' => 'heroicon-o-document',
            'email' => 'heroicon-o-envelope',
            'meeting' => 'heroicon-o-calendar',
            'message' => 'heroicon-o-chat-bubble-bottom-center-text',
            'note' => 'heroicon-o-pencil',
        };
    }

    public function getDiaryTypeColor(): array
    {
        return match ($this->type) {
            'call' => Color::Amber,
            'email' => Color::Sky,
            'meeting' => Color::Red,
            'message' => Color::Cyan,
            'note' => Color::Lime,
            default => Color::Gray,
        };
    }

    public function getCallTypeLabel(): ?string
    {
        return $this->type === 'call' ? match ($this->data['call_type'] ?? null) {
            'inquiry_about' => 'בירור',
            'proposal' => 'הצעה',
            'heating' => 'חימום',
            'status_check' => 'בדיקת סטטוס',
            'assistance' => 'עזרה',
            'general' => 'כללי',
            default => null,
        } : null;
    }

    public function getLabelDescription(?string $type = null): string
    {
        return match ($type ?? $this->type) {
            'call' => 'סיכום שיחה',
            'document' => 'תיאור המסמך',
            'email' => 'תיאור הדוא"ל',
            'meeting' => 'סיכום הפגישה',
            'message' => 'תוכן ההודעה',
            'note' => 'תוכן ההערה',
            default => 'תיאור',
        };
    }

    public static function getLabelDescriptionByType(string $type): string
    {
        return (new static)->getLabelDescription($type);
    }

    public function getStatuses(): array
    {
        $statuses = $this->data['statuses'] ?? [];

        return [
            'proposal' => ($statuses['proposal'] ?? false) ? Statuses::getProposalStatuses()->firstWhere('name', $statuses['proposal']): null,
            'guy' => ($statuses['guy'] ?? false) ? Statuses::getGuyGirlStatuses()->firstWhere('name', $statuses['guy']): null,
            'girl' => ($statuses['girl'] ?? false) ? Statuses::getGuyGirlStatuses()->firstWhere('name', $statuses['girl']): null,
        ];
    }

    public function getStatusesHtmlAttribute(): string
    {
        $statuses = $this->getStatuses();

        return \Blade::render(
<<<'HTML'
<span class="flex divide-x rtl:divide-x-reverse">

@if($statuses['proposal'])
    <span class="flex flex-col justify-end pe-2">
        <span class="text-xs font-semibold text-gray-400">שידוך:</span>
        <span>
            <x-status-option-in-select
                :status="$statuses['proposal']"
            />
        </span>
    </span>
@endif

@if($statuses['guy'])
    <span class="flex flex-col justify-end px-2">
        <span class="text-xs font-semibold text-gray-400">בחור:</span>
        <span>
            <x-status-option-in-select
                :status="$statuses['guy']"
            />
        </span>
    </span>
@endif

@if($statuses['girl'])
    <span class="flex flex-col justify-end ps-2">
        <span class="text-xs font-semibold text-gray-400">בחורה:</span>
        <span>
            <x-status-option-in-select
                :status="$statuses['girl']"
            />
        </span>
    </span>
@endif
</span>

HTML
            , ['statuses' => $statuses]
        );
    }
}
