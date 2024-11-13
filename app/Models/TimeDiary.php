<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeDiary extends Model
{
    protected $fillable = [
        'user_id',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusAttribute(): string
    {
        return !$this->end_at;
    }

    public function getDateAttribute(): string
    {
        return $this->start_at;
    }

    public function getSumHoursAttribute(): string
    {
        return $this->start_at->diff($this->end_at)->format('%H:%I:%S');
    }
}
