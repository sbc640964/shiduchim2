<?php

namespace App\Jobs\FirstFillData\Students;

use App\Jobs\FirstFillData\AbstractToBatchJob;
use App\Models\Person;
use App\Models\Phone;
use DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;

class UpdatePhonesJob extends AbstractToBatchJob implements ShouldQueue
{
    public int $tries = 3;

    public function backoff(): array
    {
        return [5, 10, 15];
    }

    public function handle(): void
    {
        $people = $this->getPeople()
            ->load('father', 'mother', 'parentsFamily');

        DB::transaction(function () use ($people) {
            $people->each(function (Person $person) {
                $parentsFamilyPhone = trim(\Str::replace('-', '', data_get($person->data_raw, 'phone', '')));
                $fatherPhone = trim(\Str::replace('-', '', data_get($person->data_raw, 'father_phone', '')));
                $motherPhone = trim(\Str::replace('-', '', data_get($person->data_raw, 'mother_phone', '')));

                $existingPhones = Phone::whereIn('number', [
                    $parentsFamilyPhone,
                    $fatherPhone,
                    $motherPhone,
                ])->pluck('number')->toArray();

                if (
                    $person->parentsFamily
                    && $parentsFamilyPhone
                    && ! in_array($parentsFamilyPhone, $existingPhones, true)) {
                    $person->parentsFamily->phones()->create([
                        'number' => $parentsFamilyPhone,
                    ]);
                }

                if ($person->father
                    && $fatherPhone
                    && $fatherPhone !== $parentsFamilyPhone
                    && ! in_array($fatherPhone, $existingPhones, true)) {
                    $person->father->phones()->create([
                        'number' => $fatherPhone,
                    ]);
                }

                if ($person->mother
                    && $motherPhone
                    && $motherPhone !== $parentsFamilyPhone
                    && $motherPhone !== $fatherPhone
                    && ! in_array($motherPhone, $existingPhones, true)) {
                    $person->mother->phones()->create([
                        'number' => $motherPhone,
                    ]);
                }
            });
        }, 3);
    }

    public static function baseQuery(bool $onlyIds = false): Builder
    {
        return parent::baseQuery($onlyIds)
            ->whereNotNull('people.data_raw->import_students');

    }
}
