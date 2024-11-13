<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditCard extends Model
{

    protected $fillable = [
        "person_id",
        "brand",
        "token",
        "last4",
        "is_active",
        "data",
    ];

    protected $casts = [
        "data" => "array",
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
