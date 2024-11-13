<?php

namespace App\Jobs\FirstFillData;

use App\Models\City;
use App\Models\Person;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddCitiesByPeopleDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
    }

    public function handle(): void
    {
        $cities = Person::query()
            ->whereNotNull('data_raw->city')
            ->select('data_raw->city as city')
            ->pluck('city')
            ->unique();

        City::insert($cities->map(fn ($city) => ['name' => $city])->toArray());

        $cities = City::all();

        $cities->each(function (City $city) {
            Person::whereNotNull('data_raw->city')
                ->where('data_raw->city', $city->name)
                ->update(['city_id' => $city->id]);
        });
    }
}
