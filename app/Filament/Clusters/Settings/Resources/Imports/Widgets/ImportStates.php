<?php

namespace App\Filament\Clusters\Settings\Resources\Imports\Widgets;

use App\Models\ImportBatch;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ImportStates extends BaseWidget
{
    public ImportBatch $record;

    protected function getPollingInterval(): ?string
    {
        return $this->record->status === 'running' ? '5s' : null;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('רשומות', $this->record->rows()->count())
                ->description('סה"כ רשומות לייבוא'),
            Stat::make('עברו בהצלחה', $this->record->rows()
                ->where('status', 'success')
                ->count()
            )
                ->color('success')
                ->description('כמות רשומות שעברו בהצלחה'),
            Stat::make('נכשלו', $this->record->rows()
                ->where('status', 'failed')
                ->count()
            )
                ->color('danger')
                ->description('כמות רשומות שנכשלו'),
        ];
    }
}
