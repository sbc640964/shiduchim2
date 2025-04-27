<?php

namespace App\Models;

use App\Filament\Clusters\Settings\Pages\Statuses;
use App\Filament\Resources\ProposalResource;
use App\Models\Traits\HasActivities;
use App\Models\Traits\HasProposalFilamentFormsFields;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Tags\HasTags;
use App\Models\SettingOld as Setting;
use Filament\Notifications\Notification;


class Proposal extends Model
{
    use HasActivities,
        HasProposalFilamentFormsFields,
        HasTags;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status',
        'sub_status',
        'status_girl',
        'status_guy',
        'finished_at',
        'reason_status',
        'created_by',
        'matchmaker_id',
        'offered_by',
        'family_id',
        'handling_by',
        //        'offered_by_id',
        //        'handling_by_id',
        'description',
        'girl_next_time',
        'guy_next_time',
        'girl_id',
        'guy_id',
        'hidden_at',
        'opened_at',
        'closed_at',
        'reason_closed',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'finished_at' => 'datetime',
        'created_by' => 'integer',
        'matchmaker_id' => 'integer',
        'offered_by' => 'integer',
        'family_id' => 'integer',
        'handling_by' => 'integer',
        'offered_by_id' => 'integer',
        'handling_by_id' => 'integer',
        'girl_next_time' => 'date',
        'guy_next_time' => 'date',
        'hidden_at' => 'datetime',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static array $defaultActivityDescription = [
        'open' => 'פתיחת הצעת שידוך',
        'close' => 'סגירת הצעת שידוך',
    ];

    protected static function booted()
    {
        static::addGlobalScope('accessByUser', function (Builder $builder) {
            $builder->whereAccess();
        });

        static::addGlobalScope('withoutHidden', function (Builder $builder) {
            $builder->whereNull('hidden_at');
        });

        static::addGlobalScope('withoutClosed', function (Builder $builder) {
            $closedStatus = Statuses::getClosedProposalStatus();

            $builder->where('status', '!=', $closedStatus);
        });
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class);
    }

    public function offeredBy(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function matchmaker(): BelongsTo
    {
        return $this->belongsTo(Matchmaker::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function handlingBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function diaries(): HasMany
    {
        return $this->hasMany(Diary::class)->chaperone();
    }

    public function guy(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function girl(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function lastGuyDiary(): HasOne
    {
        return $this->hasOne(Diary::class, 'proposal_id')
            ->ofMany([
                'created_at' => 'max',
            ], function (Builder $query) {
                return $query
                    ->join('people', function (JoinClause $join) {
                        $join->on('people.id', '=', 'diaries.model_id')
                            ->where('people.gender', '=', 'B');
                    });
            });
    }

    public function lastGirlDiary(): HasOne
    {
        return $this->diaries()
            ->one()
            ->ofMany([
                'created_at' => 'max',
            ], function (Builder $query) {
                return $query
                    ->join('people', function (JoinClause $join) {
                        $join->on('people.id', '=', 'diaries.model_id')
                            ->where('people.gender', '=', 'G');
                    });
            });
    }

    public function lastDiary(): HasOne
    {
        return $this->diaries()
            ->one()
            ->latestOfMany('created_at');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_proposal')
            ->withPivot('timeout');
    }

    public function contacts(): MorphToMany
    {
        return $this->morphToMany(Person::class, 'model', 'contacts')
            ->using(Contact::class)
            ->withPivot('type', 'side', 'last_phone_call');
    }

    /************* SCOPES *************/

    public function scopeWhereAccess(Builder $query): Builder
    {
        if (app()->runningInConsole() || auth()->user()->hasAnyRole('admin', 'super_admin', 'developer')) {
            return $query;
        }

        return $query->whereHas('users', function (Builder $query) {
            $query
                ->where('id', auth()->id())
                ->where(function (Builder $query) {
                    $query
                        ->where('user_proposal.timeout', '>', now())
                        ->orWhereNull('user_proposal.timeout');
                });
        });
    }

    public function scopeSearchNameInPeople(Builder $query, string $search, ?string $gender = null): Builder
    {
        if (blank($search)) {
            return $query;
        }

        foreach (explode(' ', $search) as $word) {
            $query->whereHas('people', function (Builder $query) use ($word, $gender) {
                $query
                    ->when($gender, fn (Builder $query, $gender) => $query->where('gender', $gender));

                $query->where(function (Builder $query) use ($word) {
                    $query->searchName($word);
                });
            });
        }


        return $query;
    }

    public function scopeWithIndividualPeople(Builder $query, ?bool $withGroupBy = true): Builder
    {
        return $query;
    }

    public function scopeWhereNextTimeToday(Builder $query, ?string $gender = null): Builder
    {
        if ($gender) {
            return $query->whereDate("{$gender}_next_time", now());
        }

        return $query->whereDate('guy_next_time', now())
            ->orWhereDate('girl_next_time', now());
    }

    public function scopeWhereNextTimePast(Builder $query, ?string $gender = null): Builder
    {
        if ($gender) {
            return $query->whereDate("{$gender}_next_time", '<', now());
        }

        return $query->whereDate('guy_next_time', '<', now())
            ->orWhereDate('girl_next_time', '<', now());
    }

    public function scopeWithAccess(Builder $query): Builder
    {
        if (app()->runningInConsole() || auth()->user()->hasAnyRole('admin', 'super_admin', 'developer')) {
            return $query->selectRaw('1 as access');
        }

        return $query->withExists('users as access', function (Builder $query) {
            $query
                ->where('id', auth()->id())
                ->where(function (Builder $query) {
                    $query
                        ->where('user_proposal.timeout', '>', now())
                        ->orWhereNull('user_proposal.timeout');
                });
        });
    }

    /************** ATTRIBUTES **************/

    public function getGuyAttribute()
    {
        return $this->getSpoken('guy');
    }

    public function getGirlAttribute()
    {
        return $this->getSpoken('girl');
    }

    public function getFamiliesNamesAttribute()
    {
        return $this->people->pluck('reverse_full_name')->join(' עם ');
    }

    /*************** METHODS ***************/

    public function getSpoken($gender): ?Person
    {
        if ($this->relationLoaded($gender) && $relation = $this->getRelation($gender)) {
            return $relation;
        }

        return $this->people->firstWhere('gender', $gender === 'guy' ? 'B' : 'G');
    }

    public function close(null|int|array $data = []): self
    {
        $closeStatus = Statuses::getClosedProposalStatus();

        if (is_array($data)) {
            if ($family = $this->getSpoken('guy')
                ->married(
                    $this->getSpoken('girl'),
                    $data['finished_at'] ?? now(),
                    $this
                )
            ) {
                $this->update([
                    'status' => $closeStatus,
                    'finished_at' => $data['finished_at'] ?? now(),
                    'reason_status' => $data['reason_status'] ?? null,
                    'family_id' => $family->id,
                ]);
            }
        } elseif (is_int($data)) {
            $this->update([
                'status' => $closeStatus,
                'reason_status' => 'נסגר בשידוך '.$data,
            ]);
        } else {
            $this->update([
                'status' => $closeStatus,
                'reason_status' => 'נסגר ע"י שדכן חיצוני',
            ]);
        }

        return $this;
    }

    public function reopen(?string $status = null, ?string $reason = null, ?bool $changeStatusOnly = false): void
    {
        if($status && $status === Statuses::getClosedProposalStatus() && ! $changeStatusOnly) {
            return;
        }

        if($changeStatusOnly) {
            $this->update([
                'status' => $status ?? Statuses::getDefaultProposalStatus(),
                'reason_status' => $reason,
            ]);

            return;
        }

        \DB::transaction(function () use ($reason, $status) {

            $family = $this->getSpoken('guy')
                ->reMarried($this, $this->family);

            if($family) {
                $this->update([
                    'status' => $status,
                    'finished_at' => null,
                    'reason_status' => $reason,
                    'family_id' => null,
                ]);

                dump($family->load('people')->toArray(), $this->toArray());

                $family->delete();
            }
        });
    }

    public function allowedBeDefinedStatuses(?string $type = null, ?bool $assignCurrentStatus = false): Collection
    {
        $statuses = is_null($type)
            ? Statuses::getProposalStatuses()
            : Statuses::getGuyGirlStatuses();

        $currentStatus = $statuses->firstWhere('name', is_null($type)
            ? $this->status
            : $this->{"status_$type"});

        if (is_null($currentStatus)) {
            return $statuses;
        }

        $currentStatus['list'] = empty($currentStatus['list'])
            ? $statuses->pluck('name')->values()->toArray()
            : $currentStatus['list'];

        if ($assignCurrentStatus) {
            if ((bool) $currentStatus['is_not_include'] === false) {
                $currentStatus['list'] = array_filter($currentStatus['list'], function ($status) use ($currentStatus) {
                    return $status !== $currentStatus['name'];
                });
            } else {
                $currentStatus['list'][] = $currentStatus['name'];
            }
        }

        //remove closed status from list
        $closedStatus = Statuses::getClosedProposalStatus();

        if ($closedStatus && $currentStatus['name'] !== $closedStatus) {
            if((bool) $currentStatus['is_not_include'] === false) {
                $currentStatus['list'][] = $closedStatus;
            } else {
                $currentStatus['list'] = array_filter($currentStatus['list'], function ($status) use ($closedStatus) {
                    return $status !== $closedStatus;
                });
            }
        }

        return ((bool) $currentStatus['is_not_include']) === false
            ? $statuses->whereNotIn('name', $currentStatus['list'] ?? [])
            : $statuses->whereIn('name', $currentStatus['list'] ?? []);
    }

    public function isGirl(int|Person $id): bool
    {
        $id = $id instanceof Person ? $id->id : $id;

        return $this->girl->id === $id;
    }

    public function isGuy(int|Person $id): bool
    {
        $id = $id instanceof Person ? $id->id : $id;

        return $this->guy->id === $id;
    }

    public function getNextDate($gender): ?string
    {
        $nextTime = $this->{"{$gender}_next_time"};

        if (! $nextTime instanceof Carbon) {
            return null;
        }

        return match (true) {
            $nextTime->isToday() => 'היום',
            $nextTime->isTomorrow() => 'מחר',
            $nextTime->isYesterday() => 'אתמול',
            default => ($nextTime->isPast() ? 'לפני ' : 'בעוד ').$nextTime->diffForHumans([
                'skip' => 'h',
                'syntax' => CarbonInterface::DIFF_ABSOLUTE,
            ]),
        };
    }

    public function nextDateIsPast($gender): bool
    {
        return now()->startOfDay()->isAfter($this->{"{$gender}_next_time"});
    }

    public function nextDateIsToday($gender): bool
    {
        return now()->isSameDay($this->{"{$gender}_next_time"});
    }

    public function getStatus(?string $side = null)
    {
        return $this->allowedBeDefinedStatuses(type: $side, assignCurrentStatus: true)
            ->firstWhere('name', $this->{$side ? "status_$side" : 'status'});
    }

    /************* Filament Table Columns *************/

    public function columnStatus(?string $side = null): string
    {
        $status = $this->getStatus($side);

        return \View::make('components.status-option-in-select', [
            'status' => $status,
            'isColumn' => true,
            'rawStatus' => $this->status,
        ])->render();
    }

    /**************** STATIC METHODS ****************/

    public static function createWithPeopleAndContacts(array $attributes, $people): self
    {
        $statuses = Setting::findMany([
            'statuses_proposal',
            'statuses_proposal_person',
        ]);

        $defaultStatus = collect($statuses->first(
            fn (Setting $setting) => $setting->getAttributes()['key'] === 'statuses_proposal'
        )?->value ?? [])
            ->firstWhere('is_default', true)['name'] ?? 'UNKNOWN';

        $defaultPersonStatus = collect($statuses->first(
            fn (Setting $setting) => $setting->getAttributes()['key'] === 'statuses_proposal_person'
        )?->value ?? [])
            ->firstWhere('is_default', true)['name'] ?? 'UNKNOWN';

        $proposal = Proposal::query()->create(array_merge([
            'guy_id' => $people->firstWhere('gender', 'B')->id,
            'girl_id' => $people->firstWhere('gender', 'G')->id,
            'status' => $defaultStatus,
            'status_guy' => $defaultPersonStatus,
            'status_girl' => $defaultPersonStatus,
        ], $attributes));

        $proposal->people()->attach($people->pluck('id')->toArray());

        $proposal->users()->attach(auth()->id());

        $guy = $proposal->getSpoken('guy');
        $girl = $proposal->getSpoken('girl');

        $contacts = [];

        $guy->father_id &&
        $contacts[$guy->father_id] = [
            'type' => 'אבא',
            'side' => 'guy',
        ];

        $guy->mother_id &&
        $contacts[$guy->mother_id] = [
            'type' => 'אמא',
            'side' => 'guy',
        ];

        $girl->father_id &&
        $contacts[$girl->father_id] = [
            'type' => 'אבא',
            'side' => 'girl',
        ];

        $girl->mother_id &&
        $contacts[$girl->mother_id] = [
            'type' => 'אמא',
            'side' => 'girl',
        ];

        foreach ([$guy, $girl] as $person) {
            foreach ($person->auto_contacts ?? [] as $contact) {
                $contacts[$contact['person_id']] = [
                    'type' => $contact['type'],
                    'side' => $person->gender === 'B' ? 'guy' : 'girl',
                ];
            }
        }

        $proposal->contacts()->attach($contacts);

        return $proposal;
    }

    public function userCanAccess(?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if ($user->hasAnyRole(['admin', 'super_admin', 'developer'])) {
            return true;
        }

        if ($this->users()
            ->where('id', $user->id)
            ->where(function (Builder $query) {
                $query
                    ->where('user_proposal.timeout', '>', now())
                    ->orWhereNull('user_proposal.timeout');
            })
            ->exists()) {
            return true;
        }

        return false;
    }

    public function deleteDependencies(): void
    {
        $diariesIds = $this->diaries()->pluck('id');

        $diariesIds->isNotEmpty() && Person::query()->whereIn('last_diary_id', $diariesIds)
            ->update(['last_diary_id' => null]);

        $this->diaries()->delete();
        $this->people()->detach();
    }

    public function canReopen(): bool
    {
        //is today finished
        $toDayFinished = $this->finished_at?->isToday();

        return $this->status === Statuses::getClosedProposalStatus()
            && $this->finished_at
            && ($toDayFinished || auth()->user()->hasAnyRole('admin', 'super_admin', 'developer'))
            && $this->userCanAccess();
    }

    public function hide()
    {
        $this->update([
            'hidden_at' => now(),
        ]);
    }

    public function show()
    {
        $this->update([
            'hidden_at' => null,
        ]);
    }

    /**
     * @param string $side - guy|girl
     * @return int
     */
    function countSideCalls(string $side): int
    {
        $side = $this->getSpoken($side);

        $phones = collect([
            $side->contacts->pluck("phones")->flatten(1),
            $side->parentsFamily->phones,
            $side->father->phones,
            $side->mother->phones,
        ])->flatten(1)->pluck('id')->toArray();

        return $this->diaries->loadMissing('call')
            ->where('type', 'call')
            ->filter(fn (Diary $diary) => in_array($diary->call->phone_id, $phones, true))
            ->count();
    }

    public function openProposal(): static
    {
        $this->update([
            'opened_at' => now(),
            'closed_at' => null,
            'reason_closed' => null,
        ]);

        $this->recordActivity('open');

        return $this;
    }

    public function closeProposal(string $reason): static
    {
        $this->update([
            'closed_at' => now(),
            'reason_closed' => $reason,
        ]);

        $this->recordActivity('close', [
            'reason' => $reason,
        ]);

        return $this;
    }

    public function getIsOpenAttribute()
    {
        return $this->opened_at && !$this->closed_at;
    }

    public function share(int|array $id): Collection
    {
        $result = $this->users()->syncWithoutDetaching(\Arr::wrap($id));

        if(count($result['attached']) === 0) {
            return collect();
        }


        $users = User::whereIn('id', $result['attached'])->get();

        Notification::make()
            ->title('שותפה אתך הצעת שידוך')
            ->body(auth()->user()->name . " שיתף איתך הצעת שידוך " . $this->guy->full_name . ' ו' . $this->girl->full_name)
            ->actions([
                \Filament\Notifications\Actions\Action::make('get_to_page')
                    ->label('פתח הצעת שידוך')
                    ->markAsRead()
                    ->url(ProposalResource::getUrl('view', ['record' => $this->getKey()]))
            ])
            ->sendToDatabase($users, isEventDispatched: true);

        return $users;
    }

}
