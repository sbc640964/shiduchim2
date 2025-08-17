<?php

namespace App\Jobs\FirstFillData\Students;

use DB;
use App\Jobs\FirstFillData\AbstractToBatchJob;
use App\Models\Family;
use App\Models\Person;
use App\Models\Pivot\PersonFamily;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;

class AssingeFamiliesJob extends AbstractToBatchJob implements ShouldQueue
{
    public function handle(): void
    {
        $people = $this->getPeople()
            ->groupBy('data_raw.spouse')
            ->filter(fn ($t) => $t->count() === 2);

        DB::transaction(function () use ($people) {

            $personFamilies = [];

            $updatePeople = $people->map(function ($group) use (&$personFamilies) {

                if (! in_array($group->pluck('gender')->join(''), ['GB', 'BG'])) {
                    return [];
                }

                $family = Family::create([
                    'name' => $group->firstWhere('gender', 'B')->last_name ?? 'Unknown',
                    'status' => 'married',
                ]);

                $personFamilies[] = $group->map(fn ($person) => [
                    'person_id' => $person->id,
                    'family_id' => $family->id,
                ]);

                return $group->map(function ($person) use ($family, $group) {

                    $spouse = $group->firstWhere('gender', '!=', $person->gender);

                    return [
                        'id' => $person->id,
                        'current_family_id' => $family->id,
                        'spouse_id' => $spouse->id,
                        'father_in_law_id' => $spouse->father_id,
                        'mother_in_law_id' => $spouse->mother_id,
                    ];
                });
            });

            $updatePeople = $updatePeople->values()->flatten(1)->toArray();
            Person::upsert(
                $updatePeople,
                ['id'],
                ['current_family_id', 'spouse_id', 'father_in_law_id', 'mother_in_law_id']
            );
            PersonFamily::insert(collect($personFamilies)->flatten(1)->toArray());
        });

    }

    public static function baseQuery(bool $onlyIds = false): Builder
    {
        return parent::baseQuery($onlyIds)
            ->whereNotNull('data_raw->import_students')
            ->whereNotNull('data_raw->spouse')
            ->whereDoesntHave('families')
            ->orderBy('data_raw->spouse');
    }
}
