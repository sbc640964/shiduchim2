<?php

namespace App\Jobs;

use App\Models\Person;
use App\Models\Phone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AfterImportPeopleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $ids;

    public function backoff(): array
    {
        return [3, 10, 15];
    }

    public function __construct(array $ids)//
    {
        $this->ids = $ids;
    }

    public function handle(): void
    {
        $spouses = collect();
        $phones = collect();

        Person::with('spouse.phones', 'phones', 'family.phones')->whereIn('id', $this->ids)
            ->each(function (Person $person) use (&$phones, &$spouses) {
                $dataImport = $person->data_raw['import_gur_202403'];

                if(
                    $dataImport['wife_name']
                    && $person->gender === 'B'
                    && $spouse = $person->spouse
                ){
                    $spouse->first_name = $spouse->first_name ?? $dataImport['wife_name'];
                    $spouses->push($spouse);
                }

                //update phones
                if($person->current_family_id && $phone = \Str::replace('-', '', $dataImport['phone'])){
                    $hasPhone = $person->family->phones->firstWhere('number', $phone);

                    if(!$hasPhone){
                        $phones->push([
                            'number' => $phone,
                            'model_type' => 'App\Models\Family',
                            'model_id' => $person->current_family_id,
                        ]);
                    }
                }

                if($phone = \Str::replace('-', '', $dataImport['phone_a'])){
                    $hasPhone = $person->phones->firstWhere('number', $phone);

                    if(!$hasPhone){
                        $phones->push([
                            'number' => $phone,
                            'model_type' => 'App\Models\Person',
                            'model_id' => $person->id,
                        ]);
                    }
                }

                if($person->spouse_id && $phone = \Str::replace('-', '', $dataImport['phone_b'])){
                    $hasPhone = $person->spouse->phones->firstWhere('number', $phone);

                    if(!$hasPhone){
                        $phones->push([
                            'number' => $phone,
                            'model_type' => 'App\Models\Person',
                            'model_id' => $person->spouse_id,
                        ]);
                    }
                }
            });

        if ($spouses->isNotEmpty()) {
            Person::upsert(
                $spouses->map->only(['id', 'first_name'])->toArray(),
                ['id'],
                ['first_name'],
            );
        }

        if ($phones->isNotEmpty()) {
            Phone::upsert(
                $phones->toArray(),
                ['number'],
                ['model_type', 'model_id'],
            );
        }
    }
}
