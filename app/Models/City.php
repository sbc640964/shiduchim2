<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'country',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
    ];

    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }

    public function mergeCities(array $cities) : ?bool
    {
        if(! count($cities)) {
            return false;
        }

        return DB::transaction(function () use ($cities) {
            Person::whereIn('city_id', $cities)->update(['city_id' => $this->id]);
            Family::whereIn('city_id', $cities)->update(['city_id' => $this->id]);
            School::whereIn('city_id', $cities)->update(['city_id' => $this->id]);

            return static::whereIn('id', $cities)->delete();
        });
    }
}
