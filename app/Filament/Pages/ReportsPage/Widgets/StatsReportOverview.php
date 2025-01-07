<?php

namespace App\Filament\Pages\ReportsPage\Widgets;

use App\Filament\Widgets\FilterReportsTrait;
use App\Models\Diary;
use App\Models\Person;
use App\Models\Proposal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class StatsReportOverview extends BaseWidget
{
    use FilterReportsTrait;

    protected function getStats(): array
    {
        $matchmaker = $this->getFilter('matchmaker');

        [$dateStart, $dateEnd] = $this->getFilter('dates_range');

        $subscription = $this->getFilter('person');

        $diaries = Diary::query()
            ->whereIn('created_by', $matchmaker)
            ->whereHas('proposal', fn (Builder $query) =>
                $query->whereHas('people', fn (Builder $query) =>
                    $query->whereIn('id', $subscription)
                )
            )
            ->with('call')
            ->when($dateStart, fn (Builder $query) => $query->whereBetween('created_at', [$dateStart, $dateEnd]))
            ->get();

        $proposals = Proposal::query()
            ->whereHas('people', fn (Builder $query) => $query->whereIn('id', $subscription))
            ->whereIn('created_by', $matchmaker)
            ->get();

        return [

            Stat::make('מנויים' ,
                Person::whereIn('billing_matchmaker', $matchmaker)
                    ->where('billing_status', 'active')
                    ->count()
            )
                ->icon('heroicon-o-users'),

            Stat::make('שעות שיחה' ,
                gmdate('H:i', $diaries->pluck('call')
                    ->filter(fn ($call) => $call?->duration ?? null)
                    ->sum('duration')
                )
            )
                ->description("{$diaries->filter(fn ($diary) => $diary->call)->count()} תיעודים")
                ->icon('heroicon-o-clock'),

            Stat::make('תיעודים' , $diaries->count())
                ->icon('heroicon-o-document'),

            Stat::make('שידוכים' , $proposals->count())
                ->description("מתוך {$proposals->count()} הצעות")
                ->icon('heroicon-o-document'),
        ];
    }
}
