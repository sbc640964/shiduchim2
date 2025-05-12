<?php

namespace App\Models;

use App\Jobs\RunImportRowJob;
use App\Jobs\RunImportRowsJob;
use App\Services\Imports\Students\RunRow;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportBatch extends Model
{
    protected $fillable = [
        'name',
        'user_id',
        'status',
        'error',
        'total',
        'success',
        'failed',
        'options',
        'file_path',
        'started_at',
        'finished_at',
        'type',
        'headers',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'options' => 'array',
        'headers' => 'array',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class)->chaperone('batch');
    }

    public function run(): void
    {
        $this->update(array_merge([
            'status' => 'running'
        ], $this->started_at ? [] : [
            'started_at' => now()
        ]));

        try {
            $this->rows()->whereStatus('pending')
                ->chunk(150, function ($rows) {
                    $rows->each(fn ($row) => RunImportRowJob::dispatch($row));
                });

        } catch (\Throwable $e) {
            $this->update(['status' => 'pending', 'error' => $e->getMessage()]);
            dump($e);
        }
    }
}
