<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class School extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'city_id',
        'type',
        'gender',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];

    public static array $typeLabel = [
        'YS' => 'ישיבה קטנה',
        'YB' => 'ישיבה גדולה',
        'YH' => 'ישיבה גבוהה',
        'TT' => 'תלמוד תורה',
        'BS' => 'בית ספר',
        'T' => 'תיכון',
        'S' => 'סמינר',
        'SH' => 'שטיבל',
    ];

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Person::class);
    }

    public function contacts(): MorphToMany
    {
        return $this
            ->morphToMany(Person::class, 'model', 'contacts')
//            ->using(Contact::class)
            ->withPivot('type', 'side');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return static::$typeLabel[$this->type] ?? $this->type ?? 'לא ידוע';
    }
}
