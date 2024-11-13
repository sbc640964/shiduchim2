<?php

namespace App\Filament\Imports;

use App\Models\City;
use App\Models\Person;
use App\Models\Phone;
use App\Models\School;
use App\Models\Family;
use Carbon\CarbonInterface;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StudentImporter extends Importer
{
    protected static ?string $model = Person::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('external_code_students')
                ->label('קוד תלמיד')
                ->exampleHeader('קוד תלמיד')
                ->requiredMapping()
                ->guess(['קוד תלמיד'])
                ->rules(['nullable']),

            ImportColumn::make('external_code')
                ->label('קוד איחוד')
                ->exampleHeader('קוד איחוד')
                ->requiredMapping()
                ->guess(['קוד איחוד', 'external_code'])
                ->rules(['nullable']),

            ImportColumn::make('gender')
                ->label('מין')
                ->exampleHeader('מין')
                ->requiredMapping()
                ->guess(['בן//בת', 'מין'])
                ->rules([
                    Rule::in(['G', 'g', 'girl', 'בת', 'B', 'b', 'buy', 'guy', 'בן']),
                ])
                ->fillRecordUsing(function (Person $record, string $state) {
                    $record->gender = match ($state) {
                        'G','g','girl','בת' => 'G',
                        'B','b','buy','guy','בן' => 'B',
                    };
                }),

            ImportColumn::make('first_name')
                ->label('שם פרטי')
                ->exampleHeader('שם פרטי')
                ->requiredMapping()
                ->guess(['שם פרטי', 'שם', 'first_name'])
                ->rules(['nullable', 'max:255']),

            ImportColumn::make('last_name')
                ->label('שם משפחה')
                ->exampleHeader('שם משפחה')
                ->requiredMapping()
                ->guess(['שם משפחה', 'last_name', 'משפחה'])
                ->rules(['required', 'max:255']),

            ImportColumn::make('father_mother_id')
                ->label('מזהה אבי אם')
                ->exampleHeader('מזהה אבי אם')
                ->requiredMapping()
                ->guess(['מזהה אבי אם'])
                ->rules(['nullable', 'exists:people,external_code'])
                ->fillRecordUsing(function (Person $record, ?string $state, array $data) {
                    //Leave it empty!
                }),

            ImportColumn::make('mother_name')
                ->label('שם אם')
                ->exampleHeader('שם אם')
                ->requiredMapping()
                ->guess(['שם אם', 'mother_name', 'אמא'])
                ->rules(['nullable'])
                ->fillRecordUsing(function (Person $record, ?string $state, array $data) {
                    //Leave it empty!
                }),

            ImportColumn::make('father_id')
                ->label('מזהה אב')
                ->exampleHeader('מזהה אב')
                ->requiredMapping()
                ->guess(['אב', 'father', 'father_code', 'קוד אב'])
                ->rules(['nullable', 'exists:people,external_code'])
                ->fillRecordUsing(function (Person $record, ?string $state, array $data) {
                    $father = Person::whereExternalCode($state)->first();
                    $record->father_id = $father?->id;

                    if ($data['father_mother_id'] == $father->fatherInLaw?->external_code) {
                        $record->mother_id = $father?->spouse_id ?? null;
                        $record->parents_family_id = $father?->current_family_id ?? null;
                        if(($data['mother_name'] ?? null) && $father->spouse && blank($father->spouse->first_name)) {
                            $father->spouse->update([
                                'first_name' => $data['mother_name'],
                            ]);
                        }
                    }
                }),

            ImportColumn::make('born_at')
                ->label('תאריך לידה')
                ->exampleHeader('תאריך לידה')
                ->requiredMapping()
                ->guess(['תאריך לידה', 'birthday', 'ת.ל. לועזי'])
                ->fillRecordUsing(function (Person $record, ?string $state) {
                    if (filled($state)) {
                        $carbon = \Str::contains($state, '/')
                            ? Carbon::createFromFormat('d/m/Y', $state)
                            : Carbon::createFromFormat('Y-m-d', $state);
                        $record->born_at = $carbon;
                    }
                })
                ->rules(['nullable']),

//            ImportColumn::make('spouse_code')
//                ->label('קוד שידוך - זמני')
//                ->requiredMapping()
//                ->guess(['קוד שידוך - זמני', 'spouse_code', 'קוד שידוך'])
//                ->fillRecordUsing(fn () => null)
//                ->rules(['nullable']),

            ImportColumn::make('school')
//                ->relationship(resolveUsing: 'name')
                ->exampleHeader('בית ספר')
                ->label('מזהה בית ספר')
                ->guess(['בית ספר', 'school', 'מוסד'])
                ->fillRecordUsing(fn () => null)
                ->rules(['nullable']),

            ImportColumn::make('prevSchool')
                ->label('בית ספר קודם')
                ->exampleHeader('בית ספר קודם')
                ->guess(['קודם', 'בית ספר קודם'])
                ->fillRecordUsing(fn () => null)
                ->rules(['nullable']),

            ImportColumn::make('fatherSynagogue')
                ->label('מזהה בית כנסת')
                ->exampleHeader('בית כנסת')
                ->fillRecordUsing(fn () => null)
                ->guess(['בית כנסת', 'synagogue', 'בית כנסת אב', 'שטיבל'])
                ->rules(['nullable']),

            ImportColumn::make('class')
                ->label('כיתה')
                ->exampleHeader('כיתה')
                ->guess(['כיתה', 'class'])
                ->fillRecordUsing(fn () => null)
                ->rules(['max:255', 'nullable']),

            ImportColumn::make('father_phone')
                ->label('פלאפון אב')
                ->exampleHeader('פלאפון אב')
                ->fillRecordUsing(fn () => null)
                ->guess(['פלאפון אב', 'father_phone'])
                ->rules(['max:255', 'nullable']),

            ImportColumn::make('mother_phone')
                ->label('פלאפון אם')
                ->exampleHeader('פלאפון אם')
                ->fillRecordUsing(fn () => null)
                ->guess(['פלאפון אם', 'mother_phone'])
                ->rules(['max:255', 'nullable']),

            ImportColumn::make('home_phone')
                ->label('טלפון בית')
                ->exampleHeader('טלפון בית')
                ->fillRecordUsing(fn () => null)
                ->guess(['טלפון בית', 'home_phone'])
                ->rules(['max:255', 'nullable']),

            ImportColumn::make('engagement_date')
                ->label('תאריך אירוסין')
                ->exampleHeader('תאריך אירוסין')
                ->fillRecordUsing(fn () => null)
                ->guess(['תאריך אירוסין', 'engagement_date'])
                ->rules(['date', 'nullable', 'date_format:Y-m-d']),

            ImportColumn::make('city')
                ->relationship(resolveUsing: function ($state) {
                    return City::createOrFirst(['name' => $state]);
                })
                ->label('עיר')
                ->exampleHeader('עיר')
                ->guess(['עיר', 'city', 'city_name'])
                ->fillRecordUsing(fn () => null)
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): ?Person
    {
        $father = Person::whereExternalCode($this->data['father_id'] ?? null)->first();

        $studentExternalId = $this->data['external_code_students'];
        $externalId = $this->data['external_code'];

        if (! blank($studentExternalId) || ! blank($externalId)) {
            return
                Person::query()
                    ->when(
                        ! blank($studentExternalId),
                        fn ($query) => $query
                            ->where('external_code_students', $studentExternalId)
                    )
                    ->when(
                        ! blank($externalId) && blank($studentExternalId),
                        fn ($query) => $query
                            ->where('external_code', $externalId)
                    )
                    ->first() ?? new Person();
        }

        return Person::firstOrNew([
            'first_name' => $this->data['first_name'] ?? null,
            'last_name' => $this->data['last_name'] ?? null,
            'gender' => match ($this->data['gender'] ?? null) {
                'בן', 'boy', 'B' => 'B',
                'בת', 'girl', 'G' => 'G',
                default => '?',
            },
            'father_id' => $father?->id,
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'ייבוא התלמידים שלך הסתיים ו'.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' יובאו בהצלחה.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' לא הצליח לייבא.';
        }

        return $body;
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

        $prevSchool = $this->data['prevSchool'] !== $school->name
            ? School::whereName($this->data['prevSchool'])->first()
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

        if(filled($this->data['spouse_code'] ?? null) && ! $this->record->spouse_id) {
            $this->syncSpouse();
        }
    }

    private function syncSpouse(): void
    {
        /** @var Person $record */
        $record = $this->record;

        $spouse = Person::where('data_raw->spouse_code', $this->data['spouse_code'])
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
            'parentsFamily' => $this->data['home_phone'],
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

        $prevSchool = $this->data['prevSchool'] !== $school->name
            ? (
                filled($this->data['prevSchool'])
                    ? School::whereName($this->data['prevSchool'])->firstOrCreate([
                        'name' => $this->data['prevSchool'],
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

    public function getJobRetryUntil(): ?CarbonInterface
    {
        return now()->addMinutes(5);
    }

    public function getJobTries(): int
    {
        return 1;
    }

    private function addCompleteProposal()
    {
        //  $proposal = new Proposal([
        //    'girl_id' => '',
        //  ]);
    }
}
