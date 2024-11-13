<?php

namespace App\Jobs\FirstFillData\Students;

use App\Jobs\FirstFillData\AbstractToBatchJob;
use App\Models\Family;
use App\Models\Person;
use DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Schema;

class ChcekDuplicates extends AbstractToBatchJob implements ShouldQueue
{
    public int $tries = 3;

    public static int $offset = 100;

    public function backoff(): array
    {
        return [5, 10, 15];
    }

    public function handle(): void
    {
        $people = $this->getPeople();

        DB::transaction(function () use ($people) {
            Schema::disableForeignKeyConstraints();

            $people->each(function (Person $person) {
                $duplicate = $person->duplicate;

                $person->city_id = $duplicate->city_id;
                $person->external_code = $duplicate->external_code;
                $person->address = $duplicate->address;

                $person->data_raw = array_merge($person->data_raw, [
                    'duplicate' => $duplicate->toArray(),
                ]);

                if ($duplicate->current_family_id) {
                    $family = Family::find($duplicate->current_family_id);
                    $family->people()->detach();
                    $family->delete();
                }

                $duplicate->delete();

                if ($duplicate->spouse_id) {
                    Person::whereId($duplicate->spouse_id)->delete();
                }

                $person->save();
            });
            Schema::enableForeignKeyConstraints();
        }, 3);
    }

    public static function baseQuery(bool $onlyIds = false): Builder
    {
        Person::resolveRelationUsing('duplicate', function (Person $person) {
            return $person->belongsTo(Person::class, 'duplicate_id', 'id');
        });

        return parent::baseQuery($onlyIds)
            ->whereNotNull('people.data_raw->import_students')
            ->whereDoesntHave('father', function (Builder $query) {
                $query->where('external_code', '0');
            })
            ->with('duplicate')
            ->select('people.*', 'p2.id as duplicate_id')
            ->join('people as p2', function (JoinClause $join) {
                $join->on('people.father_id', 'p2.father_id')
                    ->on('people.id', '!=', 'p2.id')
                    ->on('people.first_name', 'p2.first_name');
            });

    }
}
