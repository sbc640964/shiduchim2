<?php

namespace App\Models;

use App\Filament\Resources\PersonResource;
use App\Models\Pivot\PersonFamily;
use App\Models\Traits\HasActivities;
use App\Services\Nedarim;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Spatie\Tags\HasTags;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Staudenmeir\EloquentHasManyDeep\HasTableAlias;

class Person extends Model
{
    use HasRelationships,
        HasTableAlias,
        HasTags,
        Traits\HasFormEntries,
        Traits\HasPersonFormFields,
        Traits\HasPersonFilamentTableColumns,
        HasActivities;


    protected static array $defaultActivityDescription = [
        'married' => 'נישואין',
        'reMarried' => 'חזרה מטעות נישואין',
        'divorce' => 'גירושין',
        'rollbackDivorces' => 'ביטול גירושין',
        'update' => 'עדכון פרטים',
    ];

    public function getModelLabel(): string
    {
        return $this->full_name;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'external_code',
        'gender',
        'first_name',
        'last_name',
        'parents_family_id',
        'prefix_name',
        'suffix_name',
        'email',
        'address',
        'city_id',
        'last_update_relatives',
        'born_at',
        'died_at',
        'data_raw',
        'live_with_id',
        'parents_id',
        'father_id',
        'mother_id',
        'father_in_law_id',
        'mother_in_law_id',
        'spouse_id',
        'current_family_id',
        'auto_contacts',
        'external_code_students',
        'phone_default_id',
        'info',
        'info_private',
        'ichud_id',
        'telephone',
        'phone_number',
        'country',
        'birthday',
        'father_name',
        'father_in_law_name',
        'is_inhuman',
        'status_family',
        'billing_status',
        'billing_method_id',
        'billing_matchmaker',
        'billing_amount',
        'billing_next_date',
        'billing_balance_times',
        'billing_method',
        'billing_credit_card_id',
        'billing_matchmaker_day',
        'billing_payer_id',
        'billing_start_date',
        'billing_referrer_id',
        'billing_notes',
        'billing_published',
        'last_diary_id'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'external_code' => 'integer',
        'parents_family_id' => 'integer',
        'city_id' => 'integer',
        'last_update_relatives' => 'datetime',
        'born_at' => 'datetime',
        'died_at' => 'datetime',
        'data_raw' => 'array',
        'live_with_id' => 'integer',
        'parents_id' => 'integer',
        'auto_contacts' => 'array',
        'info_private' => 'array',
        'billing_next_date' => 'datetime',
        'billing_start_date' => 'datetime',
        'billing_published' => 'boolean',
    ];

    public static function studentTagsKey(): string
    {
        return 'tags:students:'.auth()->id();
    }


    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(CreditCard::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscriber::class, 'person_id');
    }

    public function lastSubscription(): HasOne
    {
        return $this->hasOne(Subscriber::class, 'person_id')
            ->latestOfMany();
    }

    public function liveWith(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function parentsFamily(): BelongsTo
    {
        return $this->belongsTo(
            Family::class,
            'parents_family_id',
            'id',
        );
    }

    /**
     * @return BelongsToMany<Family>
     */
    public function families(): BelongsToMany
    {
        return $this->belongsToMany(Family::class, 'family_person');
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'current_family_id');
    }

    public function father(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function fatherInLaw(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function motherInLaw(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function spouse(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function matchmaker(): HasOne
    {
        return $this->hasOne(Matchmaker::class);
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class)
//            ->orderBy('person_school.created_at', 'desc')
            ->withPivot('created_at');
    }

    public function school(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'person_school')
            ->latest('person_school.created_at')
            ->take(1);
    }

    public function proposals(): BelongsToMany
    {
        return $this->belongsToMany(Proposal::class);
    }

    public function diaries(): MorphMany
    {
        return $this->morphMany(Diary::class, 'model');
    }

    public function phones(): MorphMany
    {
        return $this->morphMany(Phone::class, 'model');
    }

    public function contacts(): MorphToMany
    {
        return $this->morphToMany(Person::class, 'model', 'contacts');
    }

    public function proposalContacts(): MorphToMany
    {
        return $this->morphedByMany(Proposal::class, 'model', 'contacts')
            ->withPivot(['side', 'type']);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }

    public function defaultPhone(): BelongsTo
    {
        return $this->belongsTo(Phone::class, 'phone_default_id');
    }

    public function grandParents(): HasManyDeep
    {
        return $this->hasManyDeep(
            Person::class,
            [
                PersonFamily::class,
                Person::class. ' as p1',
                PersonFamily::class.' as fp',
            ],
            [
                'family_id',
                'id',
                'family_id',
                'id',
            ],
            [
                'parents_family_id',
                'person_id',
                'parents_family_id',
                'person_id',
            ]
        )->select('people.*');
    }

    public function childrenF(): HasMany
    {
        return $this->hasMany(Person::class, 'father_id');
    }

    public function childrenM(): HasMany
    {
        return $this->hasMany(Person::class, 'mother_id');
    }

    public function relatives(): BelongsToMany
    {
        return $this->belongsToMany(
            Person::class,
            'relatives',
            'person_id',
            'relative_id'
        )
            ->withPivot('type')
            ->withTimestamps();
    }

    /**************** Scopes ****************/

    public function scopeSearchName($query, $search, ?string $gender = null, ?bool $inParents = false, ?bool $isStudent = false): Builder
    {
        $search = trim($search);

        if ($gender) {
            $query->where('gender', $gender);
        }

        if(is_numeric($search)) {
            $query->searchExternalCode($search, $isStudent);
            return $query;
        }

        foreach (explode(' ', $search) as $word) {
            $query->where(function ($query) use ($inParents, $word) {
                $query->where('people.first_name', 'like', "%$word%")
                    ->orWhere('people.last_name', 'like', "%$word%");
                if ($inParents) {
                    $query->orWhereRelation('father', 'first_name', 'like', "%$word%")
                        ->orWhereRelation('father', 'last_name', 'like', "%$word%")
                        ->orWhereRelation('mother', 'first_name', 'like', "%$word%")
                        ->orWhereRelation('mother', 'last_name', 'like', "%$word%");
                }
            });
        }

        return $query;
    }

    public function scopeSearchExternalCode(Builder $query, string $search, ?bool $student = false): Builder
    {
        if(is_numeric($search)) {
            $query->where($student ? 'external_code_students' :'external_code', $search);
        }

        return $query;
    }

    public function scopeSingle($query)
    {
        return $query
            ->whereDoesntHave('family', function ($query) {
                $query->where('status', 'married');
            });
    }

    /************ Attributes ************/

    public function getFullNameAttribute(): string
    {
        return $this->last_name.' '.$this->first_name;
    }

    public function getReverseFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function statusFamily(): Attribute
    {
        return new Attribute(
            get: fn ($newState) => $this->family?->status ?? 'single',
            set: fn ($newState) => null
        );
    }

    public function getChildrenAttribute(): ?Collection
    {
        return $this->gender === 'G' ? $this->childrenM : $this->childrenF;
    }

    public function renderPivotSideAndAddress(?bool $withAddress = false)
    {
        $fatherName = $this->relationLoaded('father') ? $this->father?->{$this->gender === 'G' ? 'full_name' : 'first_name'} ?? '' : '';
        $fatherInLawName = $this->relationLoaded('fatherInLaw') ? $this->fatherInLaw?->reverse_full_name ?? '' : '';

        $fatherInLawName = $fatherInLawName ? "חתן ר' ".$fatherInLawName : null;
        $fatherName = $fatherName ? "ב'ר ".$fatherName : null;

        $parentsNames = collect([$fatherName, $fatherInLawName])->filter()->join(' | ');

        $address = $withAddress ? (filled($parentsNames) ? ' | ' : ''). collect([
                $this->address ?? $this->family?->address ?? null,
                $this->city?->name ?? $this->family?->city?->name ?? null,
            ])->filter(fn ($str) => filled(trim($str)))->join(', ') : '';

        $subText = trim($parentsNames . ' '. $address);

        if($subText === ',') {
            $subText = '';
        }

        return $subText;
    }

    public function getSelectOptionHtmlAttribute(?bool $withPivotSide = false, ?bool $withAddress = false): string
    {
        $subText = $this->renderPivotSideAndAddress($withAddress);

        return <<<HTML
            <div>
                <div>
                    $this->full_name
                </div>
                <div class='text-xs text-gray-400'>
                     $subText
                </div>
            </div>
        HTML;
    }

    public function getSpouseInfoAttribute()
    {
        if (! $this->spouse_id) {
            return $this->gender === 'B' ? 'לא נשוי' : 'לא נשואה';
        }

        return str($this->gender === 'G' ? 'נשואה ל' : 'נשוי ל')
            ->append($this->spouse->first_name ? ($this->spouse->first_name.' ') : '')
            ->append(($this->spouse->gender === 'G' ? 'בת ' : 'בן ')."ר' ")
            ->append($this->fatherInLaw->reverse_full_name ?? '...')
            ->value();
    }

    public function getParentsInfoAttribute(): string
    {
        return str('')
            ->when($this->father, fn ($str) => $str->append('ב"ר '.($this->father->first_name ? ($this->father->first_name.' ') : 'אין שם')))
            ->when($this->father && $this->mother, fn ($str) => $str->append(' '))
            ->when($this->mother, fn ($str) => $str->append('ו'.($this->mother->first_name ? ($this->mother->first_name.' ') : 'אין שם')))
            ->when($this->spouse_id && $this->gender === 'G', fn ($str) => $str->append(' '.$this->father?->last_name))
            ->value();

    }

    public function getAgeAttribute(): int|float|null
    {
        return $this->born_at?->hebcal()?->age();
    }

    /*********** Overrides __call & __get ***********/

    //    public function __get($key)
    //    {
    //        if ($key === 'relatives') {
    //            //$this->updateRelatives();
    //        }
    //
    //        return parent::__get($key);
    //    }

    //    public function __call($method, $parameters)
    //    {
    //        if ($method === 'relatives') {
    //            if (empty($parameters['skipUpdate'])) {
    //                //$this->updateRelatives();
    //            } else {
    //                unset($parameters['skipUpdate']);
    //            }
    //        }
    //
    //        return parent::__call($method, $parameters);
    //    }

    /*********** Methods **********
     * @throws \Throwable
     */

    public function olderSiblings(): Collection
    {
        return \Cache::remember(
            'older_siblings_'.$this->id,
            now()->addMinutes(10),
            fn() => Person::query()
                ->where('parents_family_id', $this->parents_family_id)
                ->where('id', '!=', $this->id)
                ->when($this->born_at, fn (Builder $query) => $query
                    ->whereDate('born_at', '<', $this->born_at)
                )
                ->whereDoesntHave('family')
                ->get()
        );
    }
    private function updateMarriageFields(Person $spouse, int $familyId): void
    {
        $this->recordActivity('married', [
            'spouse_id' => $spouse->id,
            'old_status_family' => $this->status_family,
        ]);

        $this->current_family_id = $familyId;
        $this->spouse_id = $spouse->id;
        $this->father_in_law_id = $spouse->father_id;
        $this->mother_in_law_id = $spouse->mother_id;
//        $this->status_family = 'married';

        $this->save();

        if($this->lastSubscription?->isActive()) {
            $this->lastSubscription->status = 'married';
            $this->lastSubscription->save()
            && $this->lastSubscription->recordActivity('married',
                    [
                        'old_status' => 'active',
                        'person_id' => $spouse->id
                    ],
                );
        }
    }

    public function married(Person $person, Carbon $date, Proposal $proposal = null): ?Family
    {
        if ($this->gender === 'G') {
            return null;
        }

        if($this->current_family_id || $person->current_family_id) {
            throw new \Exception('לא יכול להתחתן, אחד האנשים כבר נשוי.');
        }

        $thisPerson = $this;

        return DB::transaction(function () use ($proposal, $person, $date, $thisPerson) {

            $newFamily = Family::create([
                'status' => 'married',
                'engagement_at' => $date,
                'name' => $thisPerson->last_name,
            ]);

            $newFamily->people()->attach([$this->id, $person->id]);

            $proposal && $proposal->update(['family_id' => $newFamily->id]);

            $this->updateMarriageFields($person, $newFamily->id);
            $person->updateMarriageFields($this, $newFamily->id);

            $otherProposals = Proposal::query()
                ->when($proposal, fn (Builder $query) => $query->where('id', '!=', $proposal->id))
                ->whereHas('people', function (Builder $query) use ($thisPerson, $person) {
                    $query->whereIn('id', [$thisPerson->id, $person->id]);
                })->get();

            $otherProposals->each->close($proposal->id ?? null);

            return $newFamily;
        });
    }

    /**
     * @throws \Throwable
     */
    public function reMarried(?Proposal $proposal = null, ?Family $family = null): Family
    {
        DB::beginTransaction();

        try {
            $spouse = $family ? $family->wife : $this->spouse;
            $family = $family ?? $this->family;

            $this->updateRollbackMarriageFields($family);
            $spouse->updateRollbackMarriageFields($family);

            $family->people()->detach([$this->id, $spouse->id]);

            $peopleIdsToOpenProposals = collect([$this, $spouse])
                ->reject(fn (Person $person) => filled($person->current_family_id))
                ->pluck('id')
                ->toArray();

            $proposals = Proposal::query()
                ->withoutGlobalScope('withoutClosed')
                ->with('lastDiary', 'people')
                ->when($proposal, fn (Builder $query) => $query->where('id', '!=', $proposal->id))
                ->whereHas('people', fn (Builder $query) => $query
                    ->whereIn('id', $peopleIdsToOpenProposals)
                )->get();

            $proposals->each(function (Proposal $proposal) {
                if(!$proposal->guy->current_family_id && !$proposal->girl->current_family_id) {
                    $proposal->reopen(
                        status: $proposal->lastDiary->data['statuses']['proposal'] ?? null,
                        changeStatusOnly: true
                    );
                }
            });

            DB::commit();

            return $family;

        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @return void
     */
    private function reBackStatusInMarriedLastSubscription(): void
    {
        if ($this->lastSubscription && $this->lastSubscription->status === 'married') {
            $lastSubscriptionStatus = $this->lastSubscription->activities()->latest()
                ->firstWhere('type', 'married')?->data['old_status'] ?? 'hold';
            $this->lastSubscription->status = $lastSubscriptionStatus;
            $this->lastSubscription->save() &&
            $this->lastSubscription->recordActivity('run', description: 'הופעל מחדש אחרי שנרשם בטעות נישואין');
        }
    }

    public function marriedExternal(int $with, ?Carbon $date = null, ?int $matchmaker = null): Proposal
    {
        $proposal = Proposal::create([
            'matchmaker_id' => $matchmaker,
            'status' => 'temp',
        ]);

        $proposal->people()->attach([$this->id, $with]);

        $proposal->close([
            'finished_at' => $date,
            'reason_status' => '',
        ]);

        return $proposal;
    }

    public function updateRelatives(bool $force = false): bool
    {
        if (! $force && ! $this->shouldUpdateRelatives()) {
            return false;
        }

        //Update
        $notAllowedIds = Person::whereIsInhuman(true)->pluck('id')->toArray();

        $relatives = [];

        $fetchPerson = fn ($id) => $id && ! in_array($id, $notAllowedIds)
            ? static::query()->where('id', $id)->first()
            : null;

        $father = $fetchPerson($this->father_id);
        $fatherInLaw = $fetchPerson($this->father_in_law_id);

        $idsParents = collect($father->only(['father_id', 'father_in_law_id']))
            ->values()
            ->merge(collect($fatherInLaw->only(['father_id', 'father_in_law_id']))->values()->toArray())
            ->reject(fn ($i) => in_array($i, $notAllowedIds))
            ->filter()
            ->unique()
            ->toArray();

        $allIds = collect($this->only(['id', 'father_id', 'father_in_law_id']))
            ->values()
            ->reject(fn ($i) => in_array($i, $notAllowedIds))
            ->merge($idsParents)
            ->filter()
            ->unique()
            ->toArray();

        $relatives = static::query()
            ->where('id', '!=', $this->id)
            ->where(fn (Builder $query) => $query
                ->whereIn('id', $idsParents)
                ->orWhereIn('father_id', $allIds)
                ->orWhereIn('father_in_law_id', $allIds)
            )->get();

        $relatives->groupBy('gender');

//        $relativesSync = [];

        //TODO: Add relatives to $relativesSync and sync with person relatives table

        $this->last_update_relatives = now();
        $this->save();

        return true;
    }

    public function shouldUpdateRelatives(): bool
    {
        return $this->last_update_relatives->diffInDays(now()) > 1;
    }

    public function loadFathersAndMothers(): static
    {
        $persons = Person::find([
            $this->father_id,
            $this->mother_id,
            $this->father_in_law_id,
            $this->mother_in_law_id,
        ]);

        $this->setRelation('father', $persons->firstWhere('id', $this->father_id));
        $this->setRelation('mother', $persons->firstWhere('id', $this->mother_id));
        $this->setRelation('fatherInLaw', $persons->firstWhere('id', $this->father_in_law_id));
        $this->setRelation('motherInLaw', $persons->firstWhere('id', $this->mother_in_law_id));

        return $this;
    }

    public function addAutoContact(mixed $id, ?string $type = null): void
    {
        $autoContacts = $this->auto_contacts;

        if (is_array($id)) {
            $autoContacts = array_merge($autoContacts, $id);
        } else {
            $autoContacts[] = [
                'person_id' => $id instanceof Person ? $id->id : $id,
                'type' => $type,
            ];
        }

        $this->auto_contacts = $autoContacts;

        $this->save();
    }

    public function getProposalsUrl(): string
    {
        return PersonResource::getUrl('proposals', ['record' => $this->id]);
    }

    public function getProposalsCount(): int
    {
        return $this->proposalContacts()->count();
    }

    public function isAlive(): bool
    {
        return ! $this->died_at;
    }

    public function isDead(): bool
    {
        return ! $this->isAlive();
    }

    public function isMarried(): bool
    {
        return $this->family?->status === 'married';
    }

    public function divorce(): bool
    {
        return $this->family->divorce();
    }

    public function rollbackDivorces(): bool
    {
        return $this->family->rollbackDivorces();
    }

    public function scopeFilterStudent($query, $data)
    {
        return $query
            ->when($data['first_name'] ?? null, fn (Builder $query, $value) => $query->where('first_name', 'like', "%$value%"))
            ->when($data['last_name'] ?? null, fn (Builder $query, $value) => $query->where('last_name', 'like', "%$value%"))
            ->when($data['father_first_name'] ?? null, fn (Builder $query, $value) => $query->whereRelation('father', 'first_name', 'like', "%$value%"))
            ->when($data['school'] ?? null, fn (Builder $query, $value) => $query->whereRelation('schools', 'id', $value))
            ->when($data['synagogue'] ?? null, fn (Builder $query, $value) => $query->whereRelation('father.school', 'id', $value))
            ->when($data['city'] ?? null, fn (Builder $query, $value) => $query->whereRelation('parentsFamily.city', fn ($query) => $query->whereIn('id', \Arr::wrap($value))))
            ->when($data['age'] ?? null, function (Builder $query, $value) use ($data) {
                $operator = $data['age_operator'] ?? '=';

                $date = now()->subYears($value + 1);

                match ($operator) {
                    '<' => $query->where('born_at', '>', $date),
                    '>' => $query->where('born_at', '<', $date),
                    default => $query->whereBetween('born_at',[$date, $date->clone()->addYear()]),
                };
            })
            ->when($data['tags'] ?? null, function (Builder $query, $value) use ($data) {
                $operator = $data['tags_operator'] ?? 'and';

                match ($operator) {
                    'and' => $query->withAllTags($value, Person::studentTagsKey()),
                    'or' => $query->withAnyTags($value, Person::studentTagsKey()),
                    'like' => $query->whereHas('tags', fn (Builder $query) => $query->where('tags.name', 'like', "%$value%")),
                    default => null
                };
            })
            ->when($data['class'] ?? null, fn (Builder $query, $value) => $query->where('data_raw->class', 'like', "%$value%"))
            ->when($data['external_code_students'] ?? null, fn (Builder $query, $value) => $query->where('external_code_students', $value))
            ->when($data['gender'] ?? 'all', fn (Builder $query, $value) => $value === 'all' ? $query : $query->where('gender', $value))
            ->when($data['father_mother_name'] ?? null, fn (Builder $query, $value) => $query->whereRelation('mother.father', fn (Builder $query) => $query->searchName($value)));
    }


    //Billing

    public function billingMatchmaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'billing_matchmaker');
    }

    public function billingCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class, 'billing_credit_card_id');

    }

    public function billingPayer(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'billing_payer_id');
    }

    public function billingReferrer(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'billing_referrer_id');
    }

    public function lastDiary(): BelongsTo
    {
        return $this->belongsTo(Diary::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function createCreditCard(array $data = [])
    {
        $result = Nedarim::createDirectDebit($this, $data);

        return data_get($result, 'Status') !== 'OK'
            ? $result
            : $this->cards()->create([
                'brand' => 'UNKNOWN',
                'token' => $result['KevaId'],
                'last4' => $result['LastNum'],
                'is_active' => true,
                'data' => $result,
            ]);
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Payment::class,
            Subscriber::class,
            'person_id',
            'subscriber_id',
            'id',
            'id'
        );
    }

    public function legacyPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'student_id');
    }

    public function getCurrentSubscriptionMatchmakerAttribute(): ?string
    {
        if($this->lastSubscription?->status === 'active') {
            return $this->lastSubscription->matchmaker?->name;
        }
        return null;
    }

    public function mergePerson(self $person, bool $deleteAfterMerge = true): static
    {
        $oldId = $person->id;
        $newId = $this->id;

        DB::beginTransaction();

        $schemaName = config('database.connections.mysql.database');
        try {
            $tables = DB::select("
                SELECT TABLE_NAME, COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE REFERENCED_TABLE_NAME = 'people'
                AND CONSTRAINT_SCHEMA = '$schemaName'
                AND REFERENCED_COLUMN_NAME = 'id'
            ");

            collect($tables)->each(function ($table) use ($oldId, $newId) {
                DB::table($table->TABLE_NAME)
                    ->where($table->COLUMN_NAME, $oldId)
                    ->update([$table->COLUMN_NAME => $newId]);
            });

            $tableMorph = DB::select("
                SELECT TABLE_NAME, COLUMN_NAME
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE COLUMN_NAME in ('model_id', 'model_type')
                and TABLE_SCHEMA = '$schemaName'
            ");

            collect($tableMorph)
                ->groupBy('TABLE_NAME')
                ->each(function (\Illuminate\Support\Collection $items, $table) use ($oldId, $newId) {
                    if($items->count() === 2){
                        DB::table($table)
                            ->where('model_id', $oldId)
                            ->where('model_type', Relation::getMorphAlias(static::class))
                            ->update(['model_id' => $newId]);
                    }
                });

            if($deleteAfterMerge) {
//                \Schema::disableForeignKeyConstraints();
                $person->delete();
//                \Schema::enableForeignKeyConstraints();
            }



            DB::commit();

            return $this;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @param mixed $family
     * @param mixed $spouse
     * @return void
     */
    protected function updateRollbackMarriageFields(Family $family): void
    {
        if ($family->getKey() === $this->current_family_id) {
            $this->current_family_id = null;
            $this->spouse_id = null;
            $this->father_in_law_id = null;
            $this->mother_in_law_id = null;

            $lastStatusFamily = $this->activities()->latest()->firstWhere('type', 'married')?->data['old_status'] ?? 'single';

//            $this->status_family = $lastStatusFamily;
        }

        $this->save() && $this->reBackStatusInMarriedLastSubscription();
    }

}
