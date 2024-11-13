<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
//use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Contact extends Relations\MorphPivot
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contacts';

    public $timestamps = true;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'person_id',
        'model',
        'type',
        'model_id',
        'model_type',
        'side',
        'last_phone_call',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'person_id' => 'integer',
        'last_phone_call' => 'array',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
