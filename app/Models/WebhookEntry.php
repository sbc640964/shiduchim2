<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEntry extends Model
{
    protected $fillable = [
        'url',
        'headers',
        'body',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'body' => 'array',
            'is_completed' => 'boolean',
            'error' => 'array',
        ];
    }

    public function completed(): void
    {
        $this->is_completed = true;
        $this->save();
    }

    public function setError(array $error): void
    {
        $this->error = $error;
        $this->save();
    }
}
