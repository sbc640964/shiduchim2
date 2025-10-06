<?php

namespace App\Models;

use Filament\Forms\Components\Select;
use Blade;
use DB;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Staudenmeir\EloquentHasManyDeep\HasTableAlias;

class Family extends Model
{
    use HasRelationships, HasTableAlias;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'address',
        'city_id',
        'status',
        'engagement_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'city_id' => 'integer',
        'engagement_at' => 'date',
    ];

    public static function searchToSelect($search): array
    {
        return static::searchNames($search)
            ->with(['people.father', 'people.fatherInLaw'])
            ->limit(50)
            ->get()
            ->pluck('option_select', 'id')
            ->toArray() ?? [];
    }

    static public function filamentSelect(string $name, ?Family $currentValue = null)
    {
        return Select::make($name)
            ->getOptionLabelFromRecordUsing(fn (Family $record) => $record->option_select)
            ->getSearchResultsUsing(fn ($search, $record) =>
                array_merge(
                    Family::searchToSelect($search),
                    $currentValue ? [$currentValue->id => $currentValue->option_select] : []
                )
            )
            ->allowHtml()
            ->extraAttributes(['class' => 'option-select-w-full'])
            ->searchable();
    }

    public function scopeSearchNames($query, $search)
    {
        return $query
            ->whereRelation('people', function ($query) use ($search) {
                $query->searchName($search);
            });
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Person::class, 'parents_family_id');
    }

    public function childrenInLaw(): HasManyDeep
    {
        return $this->hasManyDeep(
            Person::class,
            [Person::class . ' as p2'],
            ['parents_family_id', 'id'],
            ['id', 'spouse_id']
        )->select('people.*');
    }

    public function proposal(): HasOne
    {
        return $this->hasOne(Proposal::class)
            ->withoutGlobalScopes();
    }

    public function diaries(): MorphMany
    {
        return $this->morphMany(Diary::class, 'model');
    }

    public function phones(): MorphMany
    {
        return $this->morphMany(Phone::class, 'model');
    }

    public function contacts(): MorphMany
    {
        return $this->morphMany(Contact::class, 'model');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    //    public function husband(): HasOneThrough
    //    {
    //        return $this->hasOneThrough(
    //            Person::class,
    //            PersonFamily::class,
    //            'family_id',
    //            'id',
    //            'id',
    //            'person_id'
    //        )->whereGender('B');
    //    }

    public function husband(): Attribute
    {
        return new Attribute(
            get: fn() => $this->people->firstWhere('gender', 'B'),
        );
    }

    public function wife(): Attribute
    {
        return new Attribute(
            get: fn() => $this->people->firstWhere('gender', 'G'),
        );
    }
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'married' => 'נשואים',
            'divorced' => 'גרושים',
            'widower' => 'אלמן',
            'widower_g' => 'אלמנה',
            default => 'לא ידוע',
        };
    }


    public function getOptionSelectAttribute(): string
    {
        $father = $this->husband->first_name ?? '';
        $mother = $this->wife->first_name ?? '';
        $name = $this->name;
        $status = $this->status_label;
        $city = $this->city?->name ?? '';
        $colorBadge = match ($status) {
            'נשואים' => 'success',
            'גרושים' => 'danger',
            'אלמן', 'אלמנה' => 'secondary',
            default => 'gray',
        };

        $parentNames = $this->husband->renderPivotSideAndAddress(false);

        return Blade::render(
<<<'HTML'

<div class="flex justify-between">
<div>
    <div class="flex gap-2">
        <div class="font-bold text-gray-800">{{ $name }}</div>
        <div class="text-gray-500">{{ $father }} ו{{ $mother }} - {{ $city }}</div>
    </div>
    <div class="text-gray-400 text-xs mt-0.5">
        {{ $parentNames }}
    </div>
</div>

<div>
    <x-filament::badge color="{{ $colorBadge }}">
    {{ $status }}
    </x-filament::badge>
</div>
</div>

HTML
            , compact(
                'father',
                'mother',
                'name',
                'status',
                'city',
                'colorBadge',
                'parentNames',
            )
        );
    }

    public function divorce(): bool
    {
        return DB::transaction(fn()  =>
            $this->people->each->update([
                'spouse_id' => null,
                'father_in_law_id' => null,
                'mother_in_law_id' => null,
            ])
            && $this->update(['status' => 'divorced'])
        );
    }

    public function rollbackDivorces(): bool
    {
        $this->loadMissing('people');

        return DB::transaction(function () {
            $this->wife->update([
                'spouse_id' => $this->husband->id,
                'father_in_law_id' => $this->husband->father_id,
                'mother_in_law_id' => $this->husband->mother_id,
            ]);

            $this->husband->update([
                'spouse_id' => $this->wife->id,
                'father_in_law_id' => $this->wife->father_id,
                'mother_in_law_id' => $this->wife->mother_id,
            ]);

            return $this->update(['status' => 'married']);
        });
    }

    public function getSelectOptionHtmlAttribute(): string
    {
        $html = <<<'Blade'
            <div class="flex justify-between">
                {{ $}}
                <div>
                    <x-filament::badge color="{{ $colorBadge }}">
                        {{ $status }}
                    </x-filament::badge>
                </div>
            </div>
        Blade;

        return Blade::compileString($html);
    }
}
