<?php

namespace App\Filament\Resources\People\Pages;

use App\Models\User;
use Filament\Support\Enums\Width;
use DB;
use Filament\Schemas\Schema;
use App\Filament\Resources\People\PersonResource;
use App\Filament\Resources\Students\StudentResource;
use App\Models\Call;
use App\Models\Person;
use App\Models\Phone;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\EditRecord;

class EditPerson extends EditRecord
{
    protected static string $resource = PersonResource::class;

    protected static string | \BackedEnum | null $navigationIcon = 'iconsax-bul-edit-2';

    protected static ?string $title = 'עריכה';

    protected function getActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('activities')
                    ->label('היסטוריית פעולות')
                    ->icon('heroicon-o-clock')
                    ->slideOver()
                    ->modalHeading('היסטוריית פעולות')
                    ->modalContent(function () {
                        $activities = $this->getRecord()->activities()->with('user')->get();
                        $people = Person::findMany([
                            ...$activities->pluck('data.spouse_id'),
                        ]);
                        return view('filament.resources.person-resource.widgets.person-activities', [
                            'activities' => $activities,
                            'people' => $people,
                        ]);
                    }),
                Action::make('merge_people')
                    ->modalWidth(Width::Small)
                    ->label('מיזוג אנשים')
                    ->action(function (array $data, Action $action) {
                        $basePerson = $data['base_person'] === 'this' ? $this->record : Person::find($data['person_id']);
                        if($basePerson->mergePerson($data['base_person'] === 'this' ? Person::find($data['person_id']) : $this->record, $data['delete_person'])){
                            $action->success();
                            $data['base_person'] !== 'this' && $action->redirect(PersonResource::getUrl('edit', ['record' => $basePerson->id]));
                        } else {
                            $action->failure();
                        }
                    })
                    ->schema([
                        Select::make('person_id')
                            ->label('אדם למיזוג')
                            ->placeholder('בחר אדם...')
                            ->searchable()
                            ->allowHtml()
                            ->getOptionLabelUsing(fn ($value) => Person::find($value)?->getSelectOptionHtmlAttribute() ?? '')
                            ->getSearchResultsUsing(fn (string $search) =>
                                Person::query()->searchName($search, inParents: true)
                                    ->with('father', 'father.city')
                                    ->where('id', '!=', $this->getRecord()->getKey())
                                    ->limit(60)
                                    ->get()
                                    ->mapWithKeys(fn (Person $person) => [$person->id => $person->getSelectOptionHtmlAttribute()]),
                            )
                            ->required(),
                        Select::make('base_person')
                            ->label('אדם בסיס')
                            ->options([
                                'this' => 'האדם הנוכחי',
                                'person_id' => 'האדם שנבחר',
                            ]),
                        Toggle::make('delete_person')
                            ->label('מחיקת אדם')
                            ->default(true),
                    ]),
                Action::make('update_prev_wife')
                    ->label('עדכון אשה מנישואים קודמים')
                    ->modalWidth(Width::Small)
                    ->hidden($this->getRecord()->gender === 'G')
                    ->schema([
                        Select::make('prev_wife_id')
                            ->label('אשה קודמת')
                            ->placeholder('בחר אשה...')
                            ->searchable()
                            ->allowHtml()
                            ->getSearchResultsUsing(fn (string $search) =>
                                Person::query()->searchName($search, gender: 'G', inParents: true)
                                    ->with('father', 'father.city')
                                    ->limit(60)
                                    ->get()
                                    ->mapWithKeys(fn (Person $person) => [$person->id => $person->getSelectOptionHtmlAttribute()]),
                            )
                            ->required(),
                    ])
                    ->action(function (array $data, Action $action) {
                        /** @var Person $record */
                        $record = $this->getRecord();

                        $transaction = DB::transaction(function () use ($data, $record, $action) {

                            $otherRecord = Person::find($data['prev_wife_id']);

                            $family = $record->families()->create([
                                'status' => 'divorced',
                                'name' => $record->last_name,
                            ]);

                            if ($family) {
                                $family->people()->attach([
                                    $record->id,
                                    $data['prev_wife_id'],
                                ]);

                                if( !$record->current_family_id )  {
                                    $record->update(['current_family_id' => $family->id]);
                                }

                                if( !$otherRecord->current_family_id ) {
                                    $otherRecord->update(['current_family_id' => $family->id]);
                                }
                            }

                            return true;
                        });

                        if($transaction) {
                            $action->success();
                            return;
                        }

                        $action->failure();

                    }),

                Action::make('death')
                    ->label('עדכון פטירה')
                    ->schema(function (Schema $schema) {
                        return $schema->components([
                            DatePicker::make('died_at')
                                ->label('תאריך פטירה')
                                ->helperText('ניתן להזין תאריך פטירה, במקרה ואינך יודע השאר ריק בבקשה!'),
                        ]);
                    })
                    ->visible(fn (Person $person) => $person->isAlive() && auth()->user()->can('update_death'))
                    ->action(function (array $data, Person $person) {
                        $data['died_at'] = filled($data['died_at']) ? $data['died_at'] : '1970-01-02 00:00:00';
                        $person->update($data);
                    })
                    ->requiresConfirmation(),

                Action::make('add_to_students')
                    ->label('הוספה למערכת תלמידים')
                    ->schema(function (Schema $schema) {
                        //change the students_external_code with numeric code
                        return $schema->components([
                            TextInput::make('external_code_students')
                                ->label('קוד תלמידים חיצוני')
                                ->numeric()
                                ->unique('people')
                                ->required(),
                        ]);
                    })
                    ->visible(fn (Person $person) => !$person->external_code_students
                        && auth()->user()->can('management_people_without_families')
                        && $person->family?->status !== 'married'
                    )
                    ->action(function (array $data, Person $person, Action $action) {
                        if($person->update(['external_code_students' => $data['external_code_students']])) {
                            $action->success();
                            $action->redirect(StudentResource::getUrl('edit', ['record' => $person->id]));
                            return;
                        }

                        $action->failure('לא ניתן להוסיף תלמיד למערכת תלמידים, אנא נסה שנית.');
                    })
                    ->requiresConfirmation(),
            ])
        ];
    }

    public function refreshFormDataB(array $attributes): void
    {
        $this->refreshFormData($attributes);
    }

    public function beforeSave(): void
    {
        $sourcePhones = ($this->record->phones?->merge($this->record->family?->phones ?? collect()) ?? collect());

        $updatePhones = collect($this->data['phones'] ?? [])->merge($this->data['family']['phones'] ?? []);

        $deletedPhones = $sourcePhones->pluck('id')->diff($updatePhones->pluck('id'));

        $deletedPhones->isNotEmpty() && Call::query()->whereIn('phone_id', $deletedPhones)->update([
            'phone_id' => null,
        ]);
    }

    public function afterSave(): void
    {
        $phones = ($this->record->phones?->merge($this->record->family?->phones ?? collect()) ?? collect());

        $phones->filter(fn (Phone $phone) => $phone->wasRecentlyCreated)->each(function (Phone $phone) {
            Call::query()->where('phone', $phone->number)->update([
                'phone_id' => $phone->id,
            ]);
        });
    }
}
