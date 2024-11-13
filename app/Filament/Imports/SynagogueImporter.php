<?php

namespace App\Filament\Imports;

use App\Models\Old\Synagogue;
use Carbon\CarbonInterface;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class SynagogueImporter extends Importer
{
    protected static ?string $model = Synagogue::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('city')
                ->relationship(resolveUsing: 'name')
                ->requiredMapping()
                ->rules(['required', 'nullable']),
        ];
    }

    public function resolveRecord(): ?Synagogue
    {
        return Synagogue::query()
            ->where('name', $this->data['name'])
            ->when($data['city'] ?? null, fn ($query, $city) => $query->whereRelation('city', 'name', $city))
            ->firstOrNew();
    }

    public function getJobRetryUntil(): CarbonInterface
    {
        return now()->addSeconds(20);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your synagogue import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
