<?php

namespace App\Jobs\FirstFillData\Students;

use App\Models\City;
use App\Models\Person;
use App\Models\School;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateSchoolsAndCitiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $this->createCities();

        $this->createSchools();
    }

    private function createCities(): void
    {
        $cities = Person::query()
            ->whereNotNull('data_raw->city')
            ->whereNotNull('data_raw->import_students')
            ->whereNotExists(DB::table('cities')->whereColumn('cities.name', 'people.data_raw->city'))
            ->select('data_raw->city as city')
            ->pluck('city')
            ->unique();

        City::insert($cities->map(fn ($city) => [
            'name' => $city,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ])->toArray());

        $cities = City::whereIn('name', $cities->toArray())->get();

        $cities->each(function (City $city) {
            Person::whereNotNull('data_raw->city')
                ->whereNotNull('data_raw->import_students')
                ->where('data_raw->city', $city->name)
                ->update(['city_id' => $city->id]);
        });
    }

    private function createSchools(): void
    {
        $now = now()->format('Y-m-d H:i:s');

        $schools = DB::table(
            Person::query()
                ->whereNotNull('data_raw->prev_school')
                ->whereNotNull('data_raw->import_students')
                ->select('data_raw->prev_school as school_name', 'gender')
                ->unionAll(Person::query()
                    ->whereNotNull('data_raw->school')
                    ->whereNotNull('data_raw->import_students')
                    ->select('data_raw->school as school_name', 'gender')
                )
                ->getQuery(), 'new_schools'
        )
            ->whereNotExists(
                DB::table('schools')
                    ->whereColumn('schools.name', 'new_schools.school_name')
                    ->whereColumn('schools.gender', 'new_schools.gender')
            )
            ->get()
            ->unique(fn ($s) => "$s->school_name $s->gender")
            ->whereNotNull('school_name');

        School::insert(
            $schools->map(fn ($school) => [
                'name' => $school->school_name,
                'gender' => $school->gender,
                'type' => 0, //unknown
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray()
        );

        $synagogue = Person::query()
            ->whereNotNull('data_raw->father_school')
            ->whereNotNull('data_raw->import_students')
            ->select('data_raw->father_school as school_name', 'city_id')
            ->whereNotExists(
                DB::table('schools')
                    ->whereColumn('schools.name', 'people.data_raw->father_school')
                //                    ->whereColumn('schools.city_id', 'people.city_id')
            )
            ->get()
            ->unique('school_name');

        School::insert(
            $synagogue->map(fn (Person $person) => [
                'name' => $person->school_name,
                'city_id' => $person->city_id,
                'gender' => 'B',
                'type' => 10, //synagogue
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray()
        );
    }
}
