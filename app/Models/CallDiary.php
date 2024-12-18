<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallDiary extends Model
{
    protected $fillable = [
        'event',
        'call_id',
        'direction',
        'from',
        'to',
        'user_id',
        'person_id',
        'phone_id',
        'extension',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function phone(): BelongsTo
    {
        return $this->belongsTo(Phone::class);
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class, 'call_id', 'unique_id');
    }
}
