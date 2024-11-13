<?php

namespace App\Jobs\FirstFillData;

use App\Models\Person;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;

class AddingChildrenToParentsJob extends AbstractToBatchJob implements ShouldQueue
{
    public function handle(): void
    {
        $people = count($this->ids) > 0
            ? static::baseQuery()
                ->whereIn('people.id', $this->ids)
                ->get()
            : static::baseQuery()
                ->skip($this->pageNumber * static::$offset)
                ->take(static::$offset)
                ->get();

        Person::upsert(
            $people->toArray(),
            ['id'],
            ['parents_family_id'],
        );
    }

    public static function baseQuery($onlyIds = false): Builder
    {
        return parent::baseQuery($onlyIds)
            ->whereNotNull('people.data_raw->father_external_code')
            ->where('people.data_raw->father_external_code', '<>', 0)
            ->join('people as father', 'father.external_code', '=', 'people.data_raw->father_external_code')
            ->join('family_person', 'family_person.person_id', '=', 'father.id')
            ->when($onlyIds === false, function (Builder $query) {
                $query
                    ->select(['people.id as id', 'family_person.family_id as parents_family_id']);
            });
    }
}
