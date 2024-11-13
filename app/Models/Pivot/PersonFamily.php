<?php

namespace App\Models\Pivot;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Staudenmeir\EloquentHasManyDeep\HasTableAlias;

class PersonFamily extends Pivot
{
    use HasTableAlias;

    protected $table = 'family_person';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'person_id',
        'family_id',
    ];
}
