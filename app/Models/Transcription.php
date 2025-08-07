<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transcription extends Model
{
    protected $fillable = [
        'status',
        'data',
        'current_step',
        'total_steps',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function call(): HasOne
    {
        return $this->hasOne(Call::class);
    }

    public function setStatus(string $status, string $message = '', ?array $data = []): bool
    {
        $data = [
            ...$data,
            'status_message' => $message,
        ];

        $data = collect($data)->dot()->merge(\Arr::dot($this->data))->undot();

        $this->fill([
            'status' => $status,
            'data' => $data,
        ]);

        return $this->save();
    }

    public function addChunkTranscription(array $transcription): bool
    {
        $data = $this->data;
        $data['chunks'][$this->current_step - 1]['transcription'] = $transcription;

        $this->data = $data;
        $this->current_step++;

        if($this->current_step >= $this->total_steps) {
            $this->status = 'completed_transcription';
        }

        return $this->save();
    }
}
