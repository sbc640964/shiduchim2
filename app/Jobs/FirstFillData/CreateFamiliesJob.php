<?php

namespace App\Jobs\FirstFillData;

use DB;
use App\Models\Family;
use App\Models\Person;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;

class CreateFamiliesJob extends AbstractToBatchJob implements ShouldQueue
{
    public static int $offset = 100;

    public function handle(): void
    {
        $people = count($this->ids) > 0
            ? static::baseQuery()
                ->whereIn('id', $this->ids)
                ->get()
            : static::baseQuery()
                ->skip($this->pageNumber * static::$offset)
                ->take(static::$offset);

        $people->each(function (Person $person) {

            DB::transaction(function () use ($person) {
                $family = Family::create([
                    'name' => $person->last_name,
                    'address' => $person->address,
                    'city_id' => $person->city_id,
                    'status' => 'married',
                ]);

                $secondPerson = Person::create([
                    'last_name' => $person->last_name,
                    'gender' => $person->gender === 'B' ? 'G' : 'B',
                    'address' => $person->address,
                    'data_raw' => array_merge($person->data_raw, [
                        'father_name' => $person->data_raw['father_in_law_name'],
                        'father_in_law_name' => $person->data_raw['father_name'],
                        'father_in_law_external_code' => $person->data_raw['father_external_code'],
                        'father_external_code' => $person->data_raw['father_in_law_external_code'],
                    ]),
                ]);

                $family->people()->attach([$person->id, $secondPerson->id]);
            });
        });
    }

    public static function baseQuery($onlyIds = false): Builder
    {
        return parent::baseQuery($onlyIds)
            ->whereNotNull('data_raw->father_in_law_external_code')
            ->where('data_raw->father_in_law_external_code', '<>', 0)
            ->whereDoesntHave('family');
    }
}
