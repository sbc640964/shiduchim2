<?php

namespace App\Services\Imports\Students;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class BatchStore
{
    protected string $filePath;

    public function __construct(
        protected UploadedFile $file,
        protected array $mapping
    ){
    }

    public static function make(UploadedFile $file, array $mapping): self
    {
        return new static($file, $mapping);
    }

    public function handle(): void
    {
        $path = $this->file->storeAs('imports', $this->file->hashName(), [
            'disk' => 's3',
        ]);

        $rows = $this->getRows();

        $batch = ImportBatch::create([
            'name' => $this->file->getClientOriginalName(),
            'type' => 'students',
            'total' => $rows->count(),
            'user_id' => auth()->id(),
            'status' => 'pending',
            'options' => $this->getOptions(),
            'file_path' => $path,
        ]);

        $rows = $rows->map(function ($row) use ($batch) {
            return [
                'status' => 'pending',
                'data' => json_encode($row),
                'import_batch_id' => $batch->id,
            ];
        });

        $rows->chunk(1000)->each(function ($chunk) use ($batch) {
            ImportRow::insert($chunk->toArray());
        });
    }

    private function getRows(): Collection
    {
        return (new Importer)->toCollection($this->file)->first();
    }

    private function getOptions(): array
    {
        return [
            'mapping' => $this->mapping,
        ];
    }
}
