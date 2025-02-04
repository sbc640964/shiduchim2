<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;

class OpenProposalsOverview extends Widget
{
    const ACTIVITY_MATCHMAKER = 'שדכנים פעילים';

    protected static ?int $sort = -10;

    protected static string $view = 'filament.widgets.open-proposals-overview';

    #[Computed]
    public function openProposals(): Collection
    {
        return User::query()
            ->role(static::ACTIVITY_MATCHMAKER)
            ->withCount(['proposals as open_proposals' =>
                    fn ($query) => $query
                        ->whereNotNull('opened_at')
                        ->whereNull('closed_at')
            ])->get();
    }

    #[Computed]
    public function currentUserOpenProposals()
    {
        return auth()->user()->proposals()
            ->whereNotNull('opened_at')
            ->whereNull('closed_at')
            ->with('people')
            ->get();
    }

    public function otherUsersProposals()
    {
        return $this->openProposals->where('id', '!=', auth()->id());
    }

    public function currentUserProposals()
    {
        return $this->currentUserOpenProposals;
    }
}
