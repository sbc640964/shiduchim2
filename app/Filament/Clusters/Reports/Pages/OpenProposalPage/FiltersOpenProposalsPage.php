<?php

namespace App\Filament\Clusters\Reports\Pages\OpenProposalPage;

use App\Models\Proposal;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

trait FiltersOpenProposalsPage
{
    use InteractsWithPageFilters;

    protected function normalizeDates(string|null $dates): ?array
    {
        if(blank($dates)) return null;

        $dates = explode(' - ', $dates);

        return [
            Carbon::createFromFormat("d/m/Y", $dates[0]), //start
            Carbon::createFromFormat("d/m/Y", $dates[1]), //end
        ];
    }

    /**
     *
     * Get the base query for the widget.
     * @return Builder<Proposal>
     */
    public function baseQuery(): Builder
    {
        $matchmaker = $this->filters['matchmaker'] ?? null;

        return Proposal::query()
            ->withoutGlobalScope('withoutClosed')
            ->whereNotNull('opened_at')
            ->when(filled($matchmaker), fn ($query) => $query->where('created_by', $matchmaker));
    }
}
