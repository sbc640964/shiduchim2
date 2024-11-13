<?php

namespace App\Jobs\FirstFillData;

use App\Models\Person;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddingMissingRowsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
    }

    public function handle(): void
    {
        $people = Person::whereNotExists(
            DB::table('people as people_b')->whereColumn(
                'people_b.external_code',
                'people.data_raw->father_external_code'
            )
        )
            ->select(
                ['data_raw->father_external_code as code',
                    'data_raw->father_name as name']
            )
            ->unionAll(
                Person::query()->whereNotExists(
                    DB::table('people as people_b')->whereColumn(
                        'people_b.external_code',
                        'people.data_raw->father_in_law_external_code'
                    )
                )->select(
                    'data_raw->father_in_law_external_code as code',
                    'data_raw->father_in_law_name as name'
                )
            )
            ->get()
            ->unique('code');

        $insertsPeople = $people->map(function ($person) {

            $lastName = str($person->name)->afterLast(' ')->value();
            $firstName = $lastName === $person->name ? null : str($person->name)->beforeLast(' ')->value();

            return [
                'external_code' => $person->code,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => 'B',
                'data_raw' => Json::encode([
                    'father_name' => null,
                    'father_external_code' => null,
                    'father_in_law_name' => null,
                    'father_in_law_external_code' => 8300,
                    'external_code' => $person->code,
                    'city' => null,
                    'last_name' => $firstName,
                    'synagogue' => null,
                    'first_name' => $lastName,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        Person::insert($insertsPeople->toArray());
    }
}
