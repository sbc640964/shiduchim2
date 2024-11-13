<?php

namespace App\Imports;

use App\Models\Person;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithProgressBar;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Str;

class StudentsImport implements SkipsEmptyRows, SkipsOnFailure, ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow, WithLimit, WithProgressBar, WithUpserts, WithValidation
{
    use Importable, SkipsFailures;

    protected ?string $importUUID;

    public function __construct()
    {
        $this->importUUID = Str::uuid()->toString().'|'.now()->getTimestamp();
    }

    public function model(array $row): Person
    {
        $gender = match ($row['gender']) {
            'בת' => 'G',
            default => 'B'
        };

        return new Person([
            'external_code_students' => $row['external_student_code'],
            'external_code' => $row['external_code'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'gender' => $gender,
            'data_raw' => array_merge($row, [
                'import_students' => $this->importUUID,
            ]),
        ]);
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function rules(): array
    {
        return [
            'external_code' => ['nullable', 'integer'],
            'external_student_code' => ['required', 'integer'],
        ];
    }

    public function limit(): int
    {
        return 25000;
    }

    public function uniqueBy(): string
    {
        return 'external_code_students';
    }
}
