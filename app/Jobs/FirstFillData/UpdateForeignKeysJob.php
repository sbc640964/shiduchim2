<?php

namespace App\Jobs\FirstFillData;

use App\Models\Person;
use Closure;
use DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class UpdateForeignKeysJob extends AbstractToBatchJob implements ShouldQueue
{
    public int $tries = 3;

    public function backoff(): array
    {
        return [3, 10, 15];
    }

    public function handle(): void
    {
        $people = $this->getPeople();

        $people->load([
            'family.people.parentsFamily.people',
            'parentsFamily.people',
        ]);

        $updatePeople = $people->map(function (Person $person) {

            $spouse = null;

            try {
                $spouse = $person->families->first()->people
                    ->firstWhere('gender', '!=', $person->gender);
            } catch (Throwable $th) {

            }

            return [
                'id' => $person->id,
                'spouse_id' => $spouse?->id ?? null,
                'father_id' => $this->getIdByGender(fn () => $person->parentsFamily->people, 'B'),
                'mother_id' => $this->getIdByGender(fn () => $person->parentsFamily->people, 'G'),
                'father_in_law_id' => $this->getIdByGender(fn () => $spouse->parentsFamily->people, 'B'),
                'mother_in_law_id' => $this->getIdByGender(fn () => $spouse->parentsFamily->people, 'G'),
                'current_family_id' => $person->families->first()?->id ?? null,
            ];
        });

        DB::transaction(function () use ($updatePeople) {
            Person::upsert(
                $updatePeople->toArray(),
                ['id'],
                collect($updatePeople->first())->except('id')->keys()->toArray(),
            );

            $this->updateFathersFormRawOnly();
        }, 3);
    }

    private function getIdByGender(Closure $people, string $string)
    {
        try {
            $people = $people();
        } catch (Throwable $th) {
            return null;
        }

        if ($people?->count() > 0) {
            return $people->firstWhere('gender', $string)->id;
        }

        return null;
    }

    private function updateFathersFormRawOnly(): void
    {
        collect(['father_in_law', 'father'])->each(function ($key) {
            $people = static::baseQuery()
                ->whereIn('people.id', $this->ids)
                ->whereNotNull("people.data_raw->{$key}_external_code")
                ->where("people.data_raw->{$key}_external_code", '<>', 0)
                ->whereNull('people.'.$key.'_id')
                ->leftJoin('people as p', 'p.external_code', "people.data_raw->{$key}_external_code")
                ->select('people.id', "p.id as {$key}_id")
                ->get()
                ->toArray();

            Person::upsert(
                $people,
                ['id'],
                [$key.'_id'],
            );
        });
    }
}
