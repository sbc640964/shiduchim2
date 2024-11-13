<?php

namespace App\Jobs\FirstFillData\Students;

use App\Jobs\FirstFillData\AbstractToBatchJob;
use App\Models\Person;
use DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;

class UpdateForeignKeysStudentsJob extends AbstractToBatchJob implements ShouldQueue
{
    public int $tries = 3;

    public function backoff(): array
    {
        return [5, 10, 15];
    }

    public function handle(): void
    {
        $people = $this->getPeople();

        $people->load(
            'father.spouse',
            'father.family'
        );

        $updateNames = [];

        $updatePeople = $people->map(function (Person $person) use (&$updateNames) {

            $spouse = $person->father?->spouse;

            if ($spouse && ! $spouse->first_name) {
                $key = $spouse->gender === 'B' ? 'father_name' : 'mother_name';
                $updateNames[] = [$spouse->id => $person->data_raw[$key] ?? null];
            }

            if ($person->father && ! $person->father->first_name) {
                $key = $person->father->gender === 'B' ? 'father_name' : 'mother_name';
                $updateNames[] = [$person->father->id => $person->data_raw[$key] ?? null];
            }

            return [
                'id' => $person->id,
                'father_id' => $person->father?->gender === 'B' ? $person->father?->id ?? null : $spouse?->id ?? null,
                'mother_id' => $person->father?->gender === 'G' ? $person->father?->id ?? null : $spouse?->id ?? null,
                'parents_family_id' => $person->father?->family?->id ?? null,
            ];
        });

        DB::transaction(function () use ($updatePeople, $updateNames) {
            Person::upsert(
                $updatePeople->toArray(),
                ['id'],
                collect($updatePeople->first())->except('id')->keys()->toArray(),
            );

            if (count($updateNames) > 0) {
                $updateNames = collect($updateNames)->map(function ($item) {
                    return [
                        'id' => key($item),
                        'first_name' => $item[key($item)],
                    ];
                })->values()->toArray();
                Person::upsert(
                    $updateNames,
                    ['id'],
                    ['first_name'],
                );
            }
        }, 3);

    }

    public static function baseQuery($onlyIds = false): Builder
    {
        return parent::baseQuery($onlyIds)
            ->when($onlyIds === false, function ($query) {
                $query
                    ->leftJoin('people as p', 'p.external_code', 'people.data_raw->father_external_code')
                    ->select('people.*', 'p.id as father_id');
            })
            ->whereNotNull('people.data_raw->import_students');
    }
}
