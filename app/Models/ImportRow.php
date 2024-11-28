<?php

namespace App\Models;

use App\Services\Imports\Students\RunRow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ImportRow extends Model
{
    protected $fillable = [
        'import_batch_id',
        'data',
        'status',
        'started_at',
        'finished_at',
        'import_model_type',
        'import_model_id',
        'import_model_state',
        'error_stack',
        'error',
    ];

    protected $casts = [
        'data' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'error_stack' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function importModel(): MorphTo
    {
        return $this->morphTo();
    }

    public function run($pendingOnly = true): void
    {
        RunRow::make($this)->handle($pendingOnly);
    }

    public function getMapData(): array
    {
        $mapping = array_flip($this->batch->options['mapping'] ?? []);

        $dataWithoutFieldsThatAreNotMapped = array_filter($this->data, fn ($value, $key) => in_array($key, array_keys($mapping)), ARRAY_FILTER_USE_BOTH);

        return collect($dataWithoutFieldsThatAreNotMapped)->mapWithKeys(function ($value, $key) use ($mapping) {
            return [$mapping[$key] => $value];
        })->toArray();
    }
}
