<?php

namespace App\Services\Imports\Students;

use App\Models\City;
use App\Models\Family;
use App\Models\ImportRow;
use App\Models\Person;
use App\Models\Phone;
use App\Models\School;
use Arr;
use DB;
use Illuminate\Support\Carbon;

class RunRow
{
    protected array $data;
    protected array $originalData;

    protected ?Person $record = null;

    public function __construct(
        protected ImportRow $row
    ){
        $this->data = $this->row->getMapData();
        $this->originalData = $this->row->data;
    }

    public static function make(ImportRow $row): self
    {
        return new static($row);
    }

    public function getRecord(): ?Person
    {
        return $this->record;
    }

    public function getRow(): ImportRow
    {
        return $this->row;
    }

    /**
     * @throws \Throwable
     */
    public function handle($pendingOnly = true): void
    {
        if($pendingOnly && $this->row->status !== 'pending') {
            return;
        }

        try {
            $this->row->update([
                'status' => 'running',
                'started_at' => now(),
            ]);

            $this->record = $this->resolveRecord();

            if($this->record->exists) {
                $this->row->update([
                    'import_model_type' => Person::class,
                    'import_model_id' => $this->record->id,
                    'import_model_state' => 'updated',
                ]);
            }
        } catch (\Exception $e) {
           dump($e);
        }



        DB::beginTransaction();

        try {

            $this->fillRecord();

            $this->beforeSave();

            $this->record->save();

            if($this->record->wasRecentlyCreated) {
                $this->afterCreate();
            }

            $this->afterSave();

            if(! $this->row->import_model_state) {
                $this->row->update([
                    'import_model_type' => Person::class,
                    'import_model_id' => $this->record->id,
                    'import_model_state' => 'created',
                ]);
            }

            DB::commit();

            $this->row->update([
                'status' => 'success',
                'error' => null,
                'error_stack' => null,
                'finished_at' => now(),
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            $this->row->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'error_stack' => [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                    'code' => $e->getCode(),
                ],
                'finished_at' => now(),
            ]);
        }

        $batch = $this->row->batch;

        if( ! $batch->rows()->whereStatus('pending')->exists()) {
            $batch->update([
                'status' => $batch->rows()->whereStatus('failed')->exists() ? 'error' : 'success',
                'finished_at' => now(),
            ]);

            $batch->rows()->whereNotIn('status', ['success', 'failed'])
                ->update([
                    'status' => 'failed',
                    'error' => 'שגיאה לא ידועה'
                ]);
        }
    }

    public function resolveRecord(): ?Person
    {
        $father = Person::whereExternalCode($this->data['father_code_ichud'] ?? null)->first();

        $studentExternalId = $this->data['external_code'] ?? null;
        $externalId = $this->data['code_ichud'] ?? null;

        $person = null;

        if (filled($studentExternalId) || filled($externalId)) {
            $person =
                Person::query()
                    ->when(
                        filled($studentExternalId),
                        fn ($query) => $query
                            ->where('external_code_students', $studentExternalId)
                    )
                    ->when(
                        filled($externalId) && blank($studentExternalId),
                        fn ($query) => $query
                            ->where('external_code', $externalId)
                    )
                    ->first() ?? null;
        }

        return $person->fill(['father_id' => $father?->id ?? null]) ?? Person::firstOrNew([
            'first_name' => $this->data['first_name'] ?? null,
            'last_name' => $this->data['last_name'] ?? null,
            'gender' => match (trim($this->data['gender']) ?? null) {
                'בן', 'boy', 'B' => 'B',
                'בת', 'girl', 'G' => 'G',
                default => '?',
            },
            'father_id' => $father?->id,
        ]);
    }

    protected function afterCreate(): void
    {
        /** @var Person $record */
        $record = $this->record;

        //sync the schools
        $school = School::whereName($this->data['school'] ?? 'אין מוסד')
            ->firstOrCreate([
                'name' => $this->data['school'] ?? 'אין מוסד',
                'gender' => $this->record->gender,
                'type' => 0,
            ]);

        $prevSchool = $this->data['prev_school'] !== $school->name
            ? School::whereName($this->data['prev_school'])->first()
            : $school;


        $record->schools()
            ->sync(array_filter([
                $school->id => ['created_at' => now()->subMonths(6), 'updated_at' => now()->subMonths(6)],
                    $prevSchool?->id ?? null => ['created_at' => now()->subYear(), 'updated_at' => now()->subYear()],
            ], fn ($v) => $v, ARRAY_FILTER_USE_KEY));
    }

    protected function beforeSave(): void
    {
        $rawData = $this->data;
        $recordRawData = $this->record->data_raw ?? [];
        $this->record->data_raw = array_merge($recordRawData, $rawData, [
            'import' => [
                'imported_at' => now()->format('Y-m-d H:i:s'),
                'imported_by' => auth()->id(),
                'imported_from' => 'csv',
                'import_id' => \Str::uuid(),
            ]
        ]);
    }

    protected function afterSave(): void
    {
        $this->syncSchools();
        $this->syncPhoneNumbers();

        if(filled($this->data['married_code'] ?? null) && ! $this->record->spouse_id) {
            $this->syncSpouse();
        }
    }

    private function syncSpouse(): void
    {
        /** @var Person $record */
        $record = $this->record;

        $spouse = Person::where('data_raw->married_code', $this->data['married_code'])
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
            'name' => $husband->last_name,
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
        $record = $this->record;

        $phones = [
            'father' => $this->data['father_phone'],
            'mother' => $this->data['mother_phone'],
            'parentsFamily' => $this->data['phone'],
        ];

        foreach ($phones as $key => $phone) {
            $phone = \Str::replace(['-', ' '], '', $phone);

            if (blank($phone)) {
                continue;
            }

            if(Phone::whereNumber($phone)->exists()) {
                continue;
            }

            $record->{$key}?->phones()
                ?->create([
                    'number' => $phone,
                ]);
        }
    }

    private function syncSchools(): void
    {
        $record = $this->record;

        $school = School::whereName($this->data['school'] ?? 'אין מוסד')
            ->firstOrCreate([
                'name' => $this->data['school'] ?? 'אין מוסד',
                'gender' => $this->record->gender,
                'type' => 0,
            ]);

        $prevSchool = $this->data['prev_school'] !== $school->name
            ? (
            filled($this->data['prev_school'])
                ? School::whereName($this->data['prev_school'])->firstOrCreate([
                'name' => $this->data['prev_school'],
                'gender' => $this->record->gender,
                'type' => 0
            ])
                : null
            )
            : $school;

        $existsSchools = $record->schools()->pluck('id');

        if($existsSchools->contains($school->id) && $existsSchools->contains($prevSchool?->id ?? null)) {
            return;
        }

        if(
            $existsSchools->contains($prevSchool?->id)
            && !$existsSchools->contains($school->id)
            && ($prevSchool?->id ?? null) !== $school->id
        ) {
            $record->schools()
                ->attach([
                    $school->id => ['created_at' => now(), 'updated_at' => now()],
                ]);
        } else {
            $record->schools()
                ->syncWithoutDetaching(array_filter([
                    $school->id => ['created_at' => now()->subMonths(6), 'updated_at' => now()->subMonths(6)],
                        $prevSchool?->id ?? null => ['created_at' => now()->subYear(), 'updated_at' => now()->subYear()],
                ], fn ($v) => $v, ARRAY_FILTER_USE_KEY));
        }
    }

    private function fillRecord(): void
    {
        if($this->data['external_code'] ?? null) {
            $this->record->external_code_students = $this->data['external_code'];
        }

        $father = $this->record->father;

        if ($this->data['mothers_father_code_ichud'] == $father->fatherInLaw?->external_code) {
            $this->record->mother_id = $father?->spouse_id ?? null;
            $this->record->parents_family_id = $father?->current_family_id ?? null;
            if(($data['mother_name'] ?? null) && $father->spouse && blank($father->spouse->first_name)) {
                $father->spouse->update([
                    'first_name' => $this->data['mother_name'],
                ]);
            }
        }

        if($father->current_family_id) {
            $this->record->parents_family_id = $father->current_family_id;
            $this->record->mother_id = $father->spouse_id;
        }

        filled($this->data['city']) && $this->record->city()
            ->associate(City::firstOrCreate(['name' => $this->data['city']]));

        $this->record->address = $this->data['address'];


        if (filled($this->data['born_date'])) {
            $carbon = \Str::contains($this->data['born_date'], '/')
                ? Carbon::createFromFormat('d/m/Y', $this->data['born_date'])
                : Carbon::createFromFormat('Y-m-d', $this->data['born_date']);
            $this->record->born_at = $carbon;
        }
    }
}
