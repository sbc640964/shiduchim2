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
        'notes'
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'body' => 'array',
            'is_completed' => 'boolean',
            'error' => 'array',
            'notes' => 'array',
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

    public function getHeadersStackAttribute($value): array
    {
        return array_map(fn ($item) => $item[0], $this->headers);
    }

    public function addNote(string $value): self
    {
        $notes = $this->notes;

        $notes[] = [
            'value' => $value,
            'created_at' => now()->format('Y-m-d H:i:s'),
        ];

        $this->notes = $notes;

        $this->saveQuietly();

        return $this;
    }
}
