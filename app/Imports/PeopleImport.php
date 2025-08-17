<?php

namespace App\Imports;

use Arr;
use App\Jobs\AfterImportPeopleJob;
use App\Models\Person;
use App\Models\Phone;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithProgressBar;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Events\AfterBatch;

class PeopleImport implements SkipsEmptyRows, SkipsOnFailure, ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow, WithLimit, WithProgressBar, WithUpserts, WithEvents
{
    use Importable, SkipsFailures, RegistersEventListeners;

    public array $rows = [];

    public function model(array $row): ?Person
    {
        $record = Person::whereExternalCode($row['external_code'])->first();

        if (! $record) {
            return null;
        }

        $record->data_raw = array_merge($record->data_raw, [
            'import_gur_202403' => $row,
        ]);

        if ($row['is_did'] == 1) {
            $record->died_at = Carbon::parse('1970-01-02 00:00:00');
        }

        if ($this->isGirl($row['suffix'])) {
            if ($row['wife_name'] && $record->gender === 'G') {
                $record->first_name = $record->first_name ?? $row['wife_name'];
            }
        }

        return $this->rows[] = $record;
    }

    public function isGirl($suffix): bool
    {
        return in_array($suffix, ['תחי', "תחי'", 'תליט"א']);
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function limit(): int
    {
        return 25000;
    }

    public function uniqueBy(): string
    {
        return 'external_code';
    }

    public function afterBatch(AfterBatch $event)
    {
        AfterImportPeopleJob::dispatch(Arr::pluck($this->rows, 'id'));
        $this->rows = [];
    }
}
