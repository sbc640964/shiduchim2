<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormEntry extends Model
{
    protected $fillable = [
        'data',
        'form_id',
        'user_id',
        'model_id',
        'model_type',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    protected function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    protected function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function model(): BelongsTo
    {
        return $this->morphTo();
    }
}
