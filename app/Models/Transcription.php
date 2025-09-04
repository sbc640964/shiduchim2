<?php

namespace App\Models;

use Arr;
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

    public function setStatus(string $status, string $message = '', ?array $data = [], ?int $chunkIndex = null): bool
    {
        $data = [
            ...$data,
            'status_message' => $message,
        ];

        $modelData = $chunkIndex
            ? $this->data
            : collect($data)->dot()->merge(Arr::dot($this->data))->undot()->toArray();

        if($chunkIndex) {
            $modelData['chunks'][$chunkIndex] = [
                ...$modelData['chunks'][$chunkIndex],
                ...$data,
                'error' => $status,
            ];
        }

        $this->fill([
            'status' => $chunkIndex ? $this->status : $status,
            'data' => $modelData,
        ]);

        return $this->save();
    }

    public function addChunkTranscription(array $transcription, ?int $chunkIndex = null): bool
    {
        $data = $this->data;
        $data['chunks'][$chunkIndex ?? ($this->current_step - 1)]['transcription'] = $transcription;

        $this->data = $data;

        if(!$chunkIndex) {
            if($this->current_step === $this->total_steps) {
                $this->status = 'completed_transcription';
            }

            $this->current_step++;
        }

        return $this->save();
    }
}
