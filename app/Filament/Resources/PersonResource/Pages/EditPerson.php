<?php

namespace App\Filament\Resources\PersonResource\Pages;

use App\Filament\Resources\PersonResource;
use App\Models\Person;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Resources\Pages\EditRecord;

class EditPerson extends EditRecord
{
    protected static string $resource = PersonResource::class;

    protected static ?string $navigationIcon = 'iconsax-bul-edit-2';

    protected static ?string $title = 'עריכה';

    protected function getActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('death')
                    ->label('עדכון פטירה')
                    ->form(function (Form $form) {
                        return $form->schema([
                            DatePicker::make('died_at')
                                ->label('תאריך פטירה')
                                ->helperText('ניתן להזין תאריך פטירה, במקרה ואינך יודע השאר ריק בבקשה!'),
                        ]);
                    })
                    ->visible(fn (Person $person) => $person->isAlive() && auth()->user()->can('update_death'))
                    ->action(function (array $data, Person $person) {
                        $data['died_at'] = $data['died_at'] . '1970-01-02 00:00:00';
                        $person->update($data);
                    })
                    ->requiresConfirmation(),
            ])
        ];
    }

    public function refreshFormDataB(array $attributes): void
    {
        $this->refreshFormData($attributes);
    }
}
