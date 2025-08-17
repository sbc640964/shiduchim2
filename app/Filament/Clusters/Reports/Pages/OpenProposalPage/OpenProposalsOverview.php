<?php

namespace App\Filament\Clusters\Reports\Pages\OpenProposalPage;

use App\Models\Proposal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OpenProposalsOverview extends BaseWidget
{
    use FiltersOpenProposalsPage;
    protected function getStats(): array
    {
        $dates = $this->normalizeDates($this->pageFilters['dates'] ?? null);

        return [
            Stat::make(
                label: 'פתוחות עכשיו',
                value: $this->baseQuery()
                    ->whereNull('closed_at')
                    ->count()
            )
                ->color('success'),
            Stat::make(
                label: 'נפתחו',
                value: $this->baseQueryWithClosed()->when($dates, fn($query) => $query->whereBetween('opened_at', $dates))->count()
            ),
            Stat::make(
                label: 'נסגרו',
                value: $this->baseQueryWithClosed()
                    ->when($dates, fn($query) => $query->whereBetween('closed_at', $dates))->count()
            ),
            Stat::make(
                label: 'הסתיימו',
                value: $this->baseQueryWithClosed()
                    ->whereNotNull('family_id')
                    ->when($dates, fn($query) => $query->whereRelation('family', 'created_at', 'between', $dates))
                    ->count(),
            ),
        ];
    }
}
