<?php

namespace App\Jobs\FirstFillData\Students;

use App\Jobs\FirstFillData\AbstractToBatchJob;
use App\Models\Person;
use DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;

class UpdateSchoolsKeysStudentsJob extends AbstractToBatchJob implements ShouldQueue
{
    public int $tries = 3;

    public function backoff(): array
    {
        return [5, 10, 15];
    }

    public function handle(): void
    {
        $people = $this->getPeople()
            ->load('father');

        DB::transaction(function () use ($people) {
            $people->each(function (Person $person) {
                if ($person->prev_school_id === $person->school_id && $person->school_id) {
                    $person->schools()->attach($person->prev_school_id, ['created_at' => now()->subYear(), 'updated_at' => now()->subYear()]);
                    $person->schools()->attach($person->school_id, ['created_at' => now()->subMonths(6), 'updated_at' => now()->subMonths(6)]);
                }

                $schools = array_filter([
                    $person->prev_school_id => ['created_at' => now()->subYear(), 'updated_at' => now()->subYear()],
                    $person->school_id => ['created_at' => now()->subMonths(6), 'updated_at' => now()->subMonths(6)],
                ], fn ($key) => (bool) $key, ARRAY_FILTER_USE_KEY);

                count($schools) && $person->schools()->syncWithoutDetaching($schools);

                if ($person->father && $person->father_school_id) {
                    $person->father->schools()->syncWithoutDetaching([
                        $person->father_school_id => ['created_at' => now(), 'updated_at' => now()],
                    ]);
                }
            });
        }, 3);
    }

    public static function baseQuery(bool $onlyIds = false): Builder
    {
        return parent::baseQuery($onlyIds)
            ->whereNotNull('people.data_raw->import_students')
            ->when($onlyIds === false, function (Builder $query) {
                $query
                    ->leftJoin('schools as s', 's.name', 'people.data_raw->prev_school')
                    ->leftJoin('schools as s2', 's2.name', 'people.data_raw->school')
                    ->leftJoin('schools as s3', 's3.name', 'people.data_raw->father_school')
                    ->where(function ($query) {
                        $query
                            ->whereNotNull('s.id')
                            ->orWhereNotNull('s2.id')
                            ->orWhereNotNull('s3.id');
                    })
                    ->select('s.id as prev_school_id', 's2.id as school_id', 's3.id as father_school_id', 'people.*');
            });

    }
}
