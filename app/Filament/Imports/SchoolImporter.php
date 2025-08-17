<?php

namespace App\Filament\Imports;

use App\Models\School;
use Carbon\CarbonInterface;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class SchoolImporter extends Importer
{
    protected static ?string $model = School::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('city')
                ->relationship(resolveUsing: 'name')
                ->rules(['nullable']),
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('gender')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
        ];
    }

    public function resolveRecord(): ?School
    {
        return School::query()
            ->where('name', $this->data['name'])
            ->when($data['city'] ?? null, fn ($query, $city) => $query->whereRelation('city', 'name', $city))
            ->firstOrNew();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your school import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }

    public function getJobRetryUntil(): CarbonInterface
    {
        return now()->addMinutes(2);
    }
}
