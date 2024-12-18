<?php

namespace App\Models\Traits;

use App\Filament\Resources\PersonResource\Pages\EditPerson;
use App\Models\Person;
use Filament\Forms;

trait HasPersonFormFields
{
    public static function externalCodeColumn(?string $column = 'external_code', ?string $label = 'קוד איחוד')
    {
        return Forms\Components\TextInput::make($column)
            ->readOnlyOn('edit')
            ->unique('people', $column, fn (Person $person) => $person)
            ->helperText(fn (?Person $record) => $record?->exists ? 'לשינוי הקוד יש ללחוץ על אייקון העריכה.' : null)
            ->suffixAction(
                Forms\Components\Actions\Action::make('edit_id_' . $column)
                    ->hidden(fn (?Person $record) => !$record?->exists)
                    ->icon('heroicon-o-pencil')
                    ->label('')
                    ->modalWidth('sm')
                    ->fillForm(fn (Person $record) => [
                        $column => $record->{$column},
                    ])
                    ->modalSubmitActionLabel('עדכן')
                    ->action(function (Person $record, array $data, $livewire) use ($column) {
                        $record->update([
                            $column => $data[$column],
                        ]);
                        $livewire->mount($record->id);
                    })
                    ->form([
                        Forms\Components\TextInput::make($column)
                            ->label($label)
                            ->unique('people', $column, fn (Person $person) => $person),
                    ])
            , true)
            ->label($label);
    }

}
