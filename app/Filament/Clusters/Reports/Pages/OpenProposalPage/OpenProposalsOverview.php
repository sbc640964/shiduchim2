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
        $dates = $this->normalizeDates($this->filters['dates'] ?? null);

        return [
            Stat::make(
                label: 'נפתחו',
                value: $this->baseQuery()->whereBetween('opened_at', $dates)->count()
            ),
            Stat::make(
                label: 'נסגרו',
                value: $this->baseQuery()->whereBetween('closed_at', $dates)->count()
            ),
            Stat::make(
                label: 'הסתיימו',
                value: $this->baseQuery()
                    ->whereNotNull('family_id')
                    ->whereRelation('family', 'created_at', 'between', $dates)
                    ->count(),
            ),
        ];
    }
}
