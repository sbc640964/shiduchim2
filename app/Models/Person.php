<?php

namespace App\Models;

use App\Filament\Resources\PersonResource;
use App\Models\Pivot\PersonFamily;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Tags\HasTags;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Staudenmeir\EloquentHasManyDeep\HasTableAlias;
use Filament\Forms;

class Person extends Model
{
    use HasFactory,
        HasRelationships,
        HasTableAlias,
        HasTags,
        Traits\HasFormEntries,
        Traits\HasPersonFormFields,
        Traits\HasPersonFilamentTableColumns;

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
        return $this->hasMany(Payment::class, 'student_id');
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

    public function scopeSearchName($query, $search, ?string $gender = null)
    {
        foreach (explode(' ', $search) as $word) {
            $query->where(function ($query) use ($word) {
                $query->where('first_name', 'like', "%$word%")
                    ->orWhere('last_name', 'like', "%$word%");
            });
        }

        if ($gender) {
            $query->where('gender', $gender);
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

    public function getSelectOptionHtmlAttribute(?bool $withPivotSide = false): string
    {
        $fatherName = $this->relationLoaded('father') ? $this->father?->first_name ?? '' : '';
        $fatherInLawName = $this->relationLoaded('fatherInLaw') ? $this->fatherInLaw?->reverse_full_name ?? '' : '';

        $divider = $fatherInLawName && $fatherName ? ' | ' : '';

        $fatherInLawName = $fatherInLawName ? "חתן ר' ".$fatherInLawName : '';
        $fatherName = $fatherName ? "ב'ר ".$fatherName : '';

        return <<<HTML
            <div>
                <div>
                    $this->full_name
                </div>
                <div class='text-xs text-gray-400'>
                    $fatherName $divider $fatherInLawName
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

    /*********** Methods ***********/

    public function married(Person $person, Carbon $date, ?Proposal $proposal = null): ?Family
    {
        if ($this->gender === 'G') {
            return null;
        }

        $thisPerson = $this;

        return \DB::transaction(function () use ($proposal, $person, $date, $thisPerson) {

            $newFamily = Family::create([
                'status' => 'married',
                'engagement_at' => $date,
                'name' => $thisPerson->last_name,
            ]);

            $newFamily->people()->attach([$this->id, $person->id]);

            $thisPerson->current_family_id = $newFamily->id;
            $thisPerson->spouse_id = $person->id;
            $thisPerson->father_in_law_id = $person->father_id;
            $thisPerson->mother_in_law_id = $person->mother_id;

            $person->current_family_id = $newFamily->id;
            $person->spouse_id = $this->id;
            $person->father_in_law_id = $this->father_id;
            $person->mother_in_law_id = $this->mother_id;

            $thisPerson->save();
            $person->save();

            $otherProposals = Proposal::query()
                ->when($proposal, fn (Builder $query) => $query->where('id', '!=', $proposal->id))
                ->whereHas('people', function (Builder $query) use ($thisPerson, $person) {
                    $query->whereIn('id', [$thisPerson->id, $person->id]);
                })->get();

            $otherProposals->each->close($proposal?->id ?? null);

            return $newFamily;
        });
    }

    public function reMarried(?Proposal $proposal = null): ?Family
    {
        $spouse = $this->spouse;
        $family = $this->family;

        $this->current_family_id = null;
        $this->spouse_id = null;
        $this->father_in_law_id = null;
        $this->mother_in_law_id = null;

        $spouse->current_family_id = null;
        $spouse->spouse_id = null;
        $spouse->father_in_law_id = null;
        $spouse->mother_in_law_id = null;

        $this->save();
        $spouse->save();

        $family->people()->detach([$this->id, $spouse->id]);

        $proposals = Proposal::query()
            ->withoutGlobalScope('withoutClosed')
            ->with('lastDiary')
            ->when($proposal, fn (Builder $query) => $query->where('id', '!=', $proposal->id))
            ->whereHas('people', fn (Builder $query) => $query
                ->whereIn('id', [$this->id, $spouse->id])
            )->get();

        $proposals->each(function (Proposal $proposal) {
            $proposal->reopen(
                status: $proposal->lastDiary->data['statuses']['proposal'] ?? null,
                changeStatusOnly: true
            );
        });

        return $family;
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

        $relativesSync = [];

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
}
