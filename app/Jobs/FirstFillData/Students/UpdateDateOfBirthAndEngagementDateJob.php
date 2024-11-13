<?php

namespace App\Jobs\FirstFillData\Students;

use App\Jobs\FirstFillData\AbstractToBatchJob;
use App\Models\Family;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;

class UpdateDateOfBirthAndEngagementDateJob extends AbstractToBatchJob implements ShouldQueue
{
    public function handle(): void
    {
        $people = static::baseQuery()
            ->select(
                'id',
                'data_raw->date_of_birth as born_at_',
                'data_raw->engagement_date as engagement_at',
                'current_family_id',
            )
            ->get();

        Person::upsert(
            $people
                ->where('born_at_', '!=', 'null')
                //where is born_at_ is valid date
                ->filter(fn (Person $person) => ! str($person->born_at_)->afterLast('-')->is('00'))
                ->map(fn (Person $person) => [
                    'id' => $person->id,
                    'born_at' => Carbon::make($person->born_at_)->format('Y-m-d H:i:s'),
                ])
                ->toArray(),
            ['id'],
            ['born_at']
        );

        Family::upsert(
            $people->map(fn (Person $person) => [
                'id' => $person->current_family_id,
                'engagement_at' => $person->engagement_at,
                'name' => 'to_delete',
                'status' => 'to_delete',
            ])
                ->whereNotNull('id')
                ->where('engagement_at', '!=', 'null')
                ->toArray(),
            ['id'],
            ['engagement_at']
        );
    }

    public static function baseQuery(bool $onlyIds = false): Builder
    {
        return parent::baseQuery($onlyIds)
            ->whereNotNull('data_raw->import_students');
    }
}
