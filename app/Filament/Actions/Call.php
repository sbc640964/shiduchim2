<?php

namespace App\Filament\Actions;

use App\Models\Family;
use App\Models\Person;
use App\Models\Phone;
use App\Models\Proposal;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\MountableAction;
use Filament\Forms;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;

class Call
{
    public static function tableActionDefaultPhone(?Proposal $proposal = null, ?string $side = null)
    {
        return Tables\Actions\Action::make('call-direct')
            ->label(fn (Person $person) => $person->defaultPhone?->number)
            ->modalWidth(MaxWidth::Small)
            ->button()
            ->size('xs')
            ->hidden(fn (Person $person) => ! $person->defaultPhone)
            ->color('success')
            ->icon('iconsax-bul-call')
            ->action(function (Person $person, array $data, Tables\Actions\Action $action, $livewire) use ($side, $proposal) {
                static::handleCall(
                    $person, [
                        'phone' => $person->defaultPhone,
                    ], $action, $proposal, $side);

                $livewire->dispatch('refresh-calls-box');
            });
    }

    public static function taskActionDefaultPhone()
    {
        return Action::make('call-direct')
            ->visible(fn (Task $task) => $task->proposal_id
                && ($task->data['contact_to'] ?? null)
                && $task->contact->defaultPhone?->number
            )
            ->label(fn (Task $task) => 'חייג ל '.$task->contact->defaultPhone?->number)
            ->button()
            ->size('xs')
            ->color('success')
            ->icon('iconsax-bul-call')
            ->action(function (array $data, Action $action, Task $task) {
                static::handleCall($task->contact, [
                    'phone' => $task->contact->defaultPhone,
                ], $action, $task->proposal, '');
            });
    }

    public static function tableAction(?Proposal $proposal = null, ?string $side = null, $action = null)
    {
        return ($action ?? Tables\Actions\Action::make('call'))
            ->name('call')
            ->label('צור קשר')
            ->tooltip('צור קשר')
            ->iconButton()
            ->modalWidth(MaxWidth::Small)
            ->modalSubmitActionLabel('חייג')
            ->color('gray')
            ->icon('iconsax-bul-call')
            ->modalHeading(fn (Person $person) => 'צור קשר עם '.$person->full_name)
            ->form(fn (Person $person, Forms\Form $form) => $form
                ->schema(static::getPhoneFormSchema($person, $proposal->contacts()->where('person_id', $person->id)->exists())),
            )
            ->action(function (Person $person, array $data, Tables\Actions\Action $action) use ($side, $proposal) {
                static::handleCall($person, $data, $action, $proposal, $side);
            });
    }

    /**
     * @description Call action for infolist
     *
     * @param  string<"girl"|"guy">  $side
     * @return InfolistAction
     */
    public static function infolistAction(Proposal $proposal, string $side, Person $person)
    {
        return InfolistAction::make('call-'.$person->id)
            ->label('צור קשר')
            ->tooltip('צור קשר')
            ->button()
            ->icon('iconsax-bul-call')
            ->modalWidth(MaxWidth::Small)
            ->modalSubmitActionLabel('חייג')
            ->iconButton()
            ->modalHeading(fn () => 'צור קשר עם '.$person->full_name)
            ->form(fn (Forms\Form $form, array $arguments, InfolistAction $action) => $form
                ->schema(static::getPhoneFormSchema(
                    $person,
                    $proposal
                        ->contacts()
                        ->where('person_id', $person->id)
                        ->exists()
                )),
            )
            ->action(function (array $data, InfolistAction $action) use ($person, $proposal, $side) {
                static::handleCall($person, $data, $action, $proposal, $side);
            });
    }

    private static function getPhoneFormSchema(Person $person, ?bool $personIsAttached = true): array
    {
        $phones = $person->phones
            ->merge($person->family ? $person->family->phones->each(fn ($phone) => $phone->number = $phone->number.' (בבית)') : [])
            ->each(fn ($phone) => $phone->number = $phone->id === $person->defaultPhone?->id ? $phone->number.' (ברירת מחדל)' : $phone->number);

        return [
            Forms\Components\Radio::make('phone')
                ->options($phones->pluck('number', 'id')->toArray())
                ->default($phones->first()?->id ?? null)
                ->live()
                ->required(fn (Forms\Get $get) => ! $get('use_new_phone'))
                ->validationMessages([
                    'required' => 'יש לבחור טלפון או להזין טלפון חדש',
                ])
                ->label('טלפון'),

            Forms\Components\Toggle::make('use_new_phone')
                ->label('השתמש בטלפון חדש')
                ->live()
                ->default(false),

            Forms\Components\Toggle::make('add_contact')
                ->label('הוסף לאנשי קשר')
                ->live()
                ->visible(! $personIsAttached)
                ->default(! $personIsAttached),

            Forms\Components\Fieldset::make('הוספת טלפון')
                ->columns(3)
                ->visible(fn (Forms\Get $get) => $get('use_new_phone'))
                ->schema([
                    Forms\Components\TextInput::make('new_phone')
                        ->hiddenLabel()
                        ->required()
                        ->columnSpan(2)
                        ->unique('phones', 'number')
                        ->validationMessages([
                            'unique' => 'טלפון זה כבר קיים במערכת',
                        ])
                        ->placeholder('מספר')
                        ->label('טלפון חדש'),

                    Forms\Components\Select::make('type_phone')
                        ->native(false)
                        ->label('סוג טלפון')
                        ->hiddenLabel()
                        ->required()
                        ->placeholder('סוג')
                        ->options($person->family ? [
                            'personal' => 'אישי',
                            'home' => 'בבית',
                        ] : [
                            'personal' => 'אישי',
                        ]),
                ]),

            Forms\Components\Toggle::make('set_it_as_default_phone')
                ->label('הגדר כטלפון ברירת מחדל')
                ->visible(fn (Forms\Get $get) => $get('use_new_phone') || ($get('phone') !== $person->defaultPhone?->id))
                ->default(false),

            Forms\Components\Fieldset::make('הוספת איש קשר')
                ->visible($personIsAttached)
                ->columns(1)
                ->visible(fn (Forms\Get $get) => $get('add_contact'))
                ->schema([
                    Forms\Components\TextInput::make('type_contact')
                        ->label('סוג איש קשר')
                        ->placeholder('למשל: אח שאחראי על השידוכים...')
                        ->required(),
                ]),
        ];
    }

    public static function handleCall(Person $person, array $data, MountableAction $action, Proposal $proposal, ?string $side = null)
    {
        if ($data['use_new_phone'] ?? false) {
            $phone = Phone::create([
                'number' => $data['new_phone'],
                'model_type' => $data['type_phone'] === 'personal' ? Person::class : Family::class,
                'model_id' => $data['type_phone'] === 'personal' ? $person->id : $person->family->id,
            ]);
        } elseif ($data['phone'] instanceof Phone) {
            $phone = $data['phone'];
        } elseif ($data['is_direct_number'] ?? false) {
            $phone = Phone::whereNumber($data['phone'])->first();
        } else {
            $phone = Phone::find($data['phone']);
        }

        if($data['set_it_as_default_phone'] ?? false) {
            $person->defaultPhone()->associate($phone);
            $person->save();
        }

        if ($data['add_contact'] ?? false) {
            $proposal->contacts()->syncWithoutDetaching([$person->id => [
                'type' => $data['type_contact'],
                'side' => $side,
            ]]);
        }

        if($phone->call($proposal, $person)) {
            $action->success();
        } else {
            $action->failure();
        }
    }

    public static function getInfolistActionForProposal(Proposal $proposal, string $side): array
    {
        $actions = [];

        $person = $proposal->{$side};

        if ($person->father ?? null) {
            $actions[] = static::infolistAction($proposal, $side, $person->father);

            if ($person->father->father ?? null) {
                $actions[] = static::infolistAction($proposal, $side, $person->father->father);
            }
        }

        if ($person->mother ?? null) {
            $actions[] = static::infolistAction($proposal, $side, $person->mother);

            if ($person->mother->father ?? null) {
                $actions[] = static::infolistAction($proposal, $side, $person->mother->father);
            }
        }

        clock($actions);

        return $actions;
    }
}
