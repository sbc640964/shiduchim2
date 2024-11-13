<?php

namespace App\Filament\Imports;

use App\Models\Old\Contact;
use App\Models\Old\Student;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ContactImporter extends Importer
{
    protected static ?string $model = Contact::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('person')
                ->label('מזהה מטבלת האנשים')
                ->relationship()
                ->rules(['nullable']),
            ImportColumn::make('model')
                ->label('סטודנט')
                ->requiredMapping()
                ->relationship(),
            ImportColumn::make('name')
                ->label('שם')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('phone_number')
                ->label('מספר טלפון')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('email')
                ->label('אימייל')
                ->rules(['email', 'max:255']),
            ImportColumn::make('address')
                ->label('כתובת')
                ->rules(['max:255']),
            ImportColumn::make('not')
                ->label('הערות')
                ->rules(['max:65535']),
            ImportColumn::make('model_relation_type')
                ->label('סוג הקשר')
                ->rules(['max:255']),
        ];
    }

    public function resolveRecord(): ?Contact
    {
        return Contact::firstOrNew([
            'phone_number' => $this->data['phone_number'],
            'model_id' => $this->data['model_id'],
            'model_type' => Student::class,
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your contact import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
