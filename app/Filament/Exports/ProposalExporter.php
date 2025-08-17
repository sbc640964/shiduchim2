<?php

namespace App\Filament\Exports;

use DB;
use App\Models\Proposal;
use Carbon\CarbonInterface;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class ProposalExporter extends Exporter
{
    protected static ?string $model = Proposal::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('matchmaker')
                ->label('שדכן'),
            ExportColumn::make('guy_id')
                ->label('מזהה בחור'),
            ExportColumn::make('girl_id')
                ->label('מזהה בחורה'),
            ExportColumn::make('created_at')
                ->label('תאריך יצירה'),
            ExportColumn::make('total_calls')
                ->counts([
                    'diaries' => fn (Builder $query) => $query->where('type', 'call'),
                ])
                ->label('סה"כ שיחות'),
            ExportColumn::make('total_time')
                ->label('סה"כ זמן'),
            ExportColumn::make('next_time')
                ->label('זמן הבא'),
            ExportColumn::make('status')
                ->label('סטטוס'),
            ExportColumn::make('total_diaries')
                ->counts([
                    'diaries' => fn (Builder $query) => $query->whereNull('model_id'),
                ])
                ->label('סה"כ יומנים'),
            ExportColumn::make('total_diaries_guy')
                ->label('סה"כ יומנים בחור')
                ->counts([
                    'diaries' => fn (Builder $query) => $query->whereColumn('model_id', DB::raw('MAX(CASE WHEN `p-0005`.`gender` = "B" THEN `p-0005`.`id` ELSE NULL END)')),
                ]),
            ExportColumn::make('total_diaries_girl')
                ->label('סה"כ יומנים בחורה')
                ->counts([
                    'diaries' => fn (Builder $query) => $query->whereColumn('model_id', DB::raw('MAX(CASE WHEN `p-0005`.`gender` = "G" THEN `p-0005`.`id` ELSE NULL END)')),
                ]),
            ExportColumn::make('last_diary_created_at')
                ->label('תאריך יומן אחרון'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your proposal export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }

    public function getJobRetryUntil(): ?CarbonInterface
    {
        return null;
    }
}
