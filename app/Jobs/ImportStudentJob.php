<?php

namespace App\Jobs;

use Str;
use Exception;
use Log;
use App\Models\City;
use App\Models\Family;
use App\Models\Person;
use App\Models\School;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ImportStudentJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Person $record;
    public function __construct(
        public array|Collection $data,
    )
    {}

    public function handle(): void
    {
        if($this->data instanceof Collection) {
            $this->data = $this->data->toArray();
        }

        if(! $this->allowImported()) {
            return;
        }

        $person = $this->resolvePerson();

        if($person->save()) {
            $this->record = $person;
            $this->afterSave();
        }
    }

    public function resolvePerson(): Person
    {
        $row = $this->data;

        $father = Person::whereExternalCode($this->data['father_external_code'] ?? null)->first();

        $studentExternalId = $row['external_code_students'];
        $externalId = $row['external_code'];

        $person = null;

        if (! blank($studentExternalId) || ! blank($externalId)) {
            $person =
                Person::query()
                    ->when(
                        ! blank($studentExternalId),
                        fn ($query) => $query
                            ->where('external_code_students', $studentExternalId)
                    )
                    ->when(
                        filled($externalId) && blank($studentExternalId),
                        fn ($query) => $query
                            ->where('external_code', $externalId)
                    )
                    ->when(
                        filled($studentExternalId) && filled($externalId),
                        fn ($query) => $query
                            ->orWhere('external_code', $externalId)
                    )
                    ->first();
        }

        if( ! $person) {
            $person = Person::firstOrNew([
                'first_name' => $this->data['first_name'] ?? null,
                'gender' => match ($this->data['gender'] ?? null) {
                    'בן', 'boy', 'B' => 'B',
                    'בת', 'girl', 'G' => 'G',
                    default => '?',
                },
                'father_id' => $father?->id,
            ]);
        }

        if ($father) {
            $person->setRelation('father', $father);
        }

        return $this->fillRecord($person, $row);
    }

    private function fillRecord(Person $person, $row): Person
    {
        if ($person->father) {
            if ($row['father_mother_external_code'] === $person->father->fatherInLaw?->external_code) {
                $person->mother_id = $person->father->spouse_id;
                $person->parents_family_id = $person->father->current_family_id;
            }
        }

        $person->fill([
            'last_name' => $person->last_name ?? $row['last_name'] ?? null,
            'external_code' => $row['external_code'] ?? null,
            'external_code_students' => $row['external_code_students'] ?? null,
            'city_id' => $this->getCityId($row['city']),
            'address' => $row['address'] ?? null,
            'born_at' => $this->getBornAt($row['date_of_birth']) ?? $person->born_at,
        ]);

        if(! $person->exists() ) {
            $person->data_raw = array_merge($person->data_raw, $row, [
                'imported_at' => now(),
            ]);

        }

        return $person;

    }

    private function getCityId($cityName)
    {
       return filled($cityName)
           ? City::createOrFirst(['name' => $cityName])->id
           : null;
    }

    private function getBornAt($born_at): ?Carbon
    {
        if(blank($born_at)) {
            return null;
        }

        return Carbon::createFromFormat(
            Str::contains($born_at, '/') ? 'd/m/Y' : 'Y-m-d',
            $born_at
        );
    }

    protected function afterSave(): void
    {
        if($this->record->wasRecentlyCreated) {
            $this->syncSchools();
        }

        $this->syncPhoneNumbers();

        if(filled($this->data['spouse']) && ! $this->record->spouse_id) {
            $this->syncSpouse();
        }
    }

    private function syncSchools(): void
    {
        $values = [
            'type' => 0,
            'gender' => $this->record->gender,
        ];

        $school = filled($this->data['school'])
            ? School::firstOrCreate(['name' => $this->data['school']], $values)
            : null;

        $prevSchool = $this->data['prev_school'] !== $school?->name
            ? (filled($this->data['prev_school'])
                ? School::firstOrCreate(['name' => $this->data['prev_school']], $values)
                :null
            ) : $school;

        $this->record->schools()
            ->sync(array_filter([
                $school?->id => ['created_at' => now()->subMonths(6), 'updated_at' => now()->subMonths(6)],
                $prevSchool?->id => ['created_at' => now()->subYear(), 'updated_at' => now()->subYear()],
            ], fn ($k) => !!$k, ARRAY_FILTER_USE_KEY));
    }

    private function syncSpouse(): void
    {
        $record = $this->record;

        $spouse = Person::where('data_raw->spouse', $this->data['spouse'])
            ->where('id', '!=', $record->id)
            ->where('gender', $record->gender === 'B' ? 'G' : 'B')
            ->first();

        if(! $spouse) {
            return;
        }

        $husband = $record->gender === 'B' ? $record : $spouse;
        $wife = $record->gender === 'G' ? $record : $spouse;

        $family = Family::create([
            'status' => 'married',
            'name' => $husband->last_name ?? 'לא ידוע',
            'city_id' => $husband->city_id,
            'address' => $husband->address,
        ]);

        $husband->update([
            'spouse_id' => $wife->id,
            'current_family_id' => $family->id,
        ]);

        $wife->update([
            'spouse_id' => $husband->id,
            'current_family_id' => $family->id,
        ]);

        $family->people()->attach([$husband->id, $wife->id]);
    }

    private function syncPhoneNumbers(): void
    {
        $phones = [
            'father' => $this->data['father_phone'],
            'mother' => $this->data['mother_phone'],
            'parentsFamily' => $this->data['phone'],
        ];

        foreach ($phones as $key => $phone) {
            $phone = Str::replace(['-', ' '], '', $phone);

            if (blank($phone)) {
                continue;
            }

            try {
                $this->record->{$key}?->phones()
                    ?->updateOrCreate([
                        'number' => $phone,
                    ]);
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }

        }
    }

    private function allowImported(): bool
    {
        if(blank($this->data['father_external_code'])) {
            $spouse = str($this->data['spouse'] ?? '')->trim();

            if($spouse->before('-') === 'PD'
                && ((int) $spouse->after('-')->value()) > 160
            ) {
                return true;
            }

            return false;
        }

        return true;
    }
}
