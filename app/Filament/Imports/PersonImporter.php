<?php

namespace App\Filament\Imports;

use App\Models\Person;
use App\Models\Phone;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class PersonImporter extends Importer
{
    protected static ?string $model = Person::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('external_code')
                ->label('מזהה אישי')
                ->rules(['integer']),
        ];
    }

    public function resolveRecord(): ?Person
    {
        return Person::firstWhere('external_code', $this->data['external_code'])
            ?? null;
    }

    public function beforeSave()
    {
        /** @var Person $record */
        $record = $this->record;

        $record->data_raw = array_merge($record->data_raw, [
            'import_gur_'.now()->format('Ymd') => $this->originalData,
        ]);

        if($this->originalData['is_did'] == 1){
            $record->died_at = Carbon::parse('1970-01-02 00:00:00');
        }

        if($this->isGirl()){
            if($this->originalData['wife_name'] && $record->gender === 'G'){
                $record->first_name = $record->first_name ?? $this->originalData['wife_name'];
            }
        }
    }

    public function isGirl(): bool
    {
        return in_array($this->originalData['suffix'], ["תחי", "תחי'", 'תליט"א']);
    }

    public function afterSave()
    {
        //update wife name
        if(
            $this->originalData['wife_name']
            && $this->record->gender === 'B'
            && $spouse = $this->record->spouse
        ){
            $spouse->first_name = $spouse->first_name ?? $this->originalData['wife_name'];
            $spouse->save();
        }

        //update phones
        if($this->record->current_family_id && $phone = \Str::replace('-', '', $this->originalData['phone'])){
            Phone::updateOrCreate([
                'number' => $phone,
            ], [
                'model_type' => 'App\Models\Family',
                'model_id' => $this->record->current_family_id,
            ]);
        }

        if($phone = \Str::replace('-', '', $this->originalData['phone_a'])){
            Phone::updateOrCreate([
                'number' => $phone,
            ], [
                'model_type' => 'App\Models\Person',
                'model_id' => $this->record->id,
            ]);
        }

        if($this->record->spouse_id && $phone = \Str::replace('-', '', $this->originalData['phone_b'])){
            Phone::updateOrCreate([
                'number' => $phone,
            ], [
                'model_type' => 'App\Models\Person',
                'model_id' => $this->record->spouse_id,
            ]);
        }

    }

    public function getJobRetryUntil(): ?CarbonInterface
    {
        return now()->addHour();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your person import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
