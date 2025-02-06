<?php

namespace App\Filament\Widgets;

use App\Models\Proposal;
use App\Models\Subscriber;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;

class OpenProposalsOverview extends Widget
{
    const ACTIVITY_MATCHMAKER = 'שדכנים פעילים';

    protected static ?int $sort = -10;

    protected static string $view = 'filament.widgets.open-proposals-overview';

    public ?int $currentUserId = null;

    #[Computed]
    public function openProposals(): Collection
    {
        return User::query()
            ->role(static::ACTIVITY_MATCHMAKER)
            ->withCount(['proposals as open_proposals' =>
                    fn (Builder $query) => $query
                        ->withoutGlobalScope('accessByUser')
                        ->whereNotNull('opened_at')
                        ->whereNull('closed_at')
            ])->get();
    }

    #[Computed]
    public function currentUserOpenProposals()
    {
        return $this->getUserProposals(auth()->user());
    }

    public function currentUser()
    {
        if($this->currentUserId && auth()->user()->can('open_proposals_manager')) {
            return $this->openProposals->firstWhere('id', $this->currentUserId);
        }

        return auth()->user();
    }

    public function getUserProposals(User $user)
    {
        return $user
            ->proposals()
            ->whereNotNull('opened_at')
            ->whereNull('closed_at')
            ->with('people.lastSubscription')
            ->get();
    }

    public function otherUsersProposals()
    {
        return $this->openProposals->where('id', '!=', auth()->id());
    }

    public function currentUserProposals($my = false)
    {
        if($this->currentUserId && ! $my) {
            $user = $this->openProposals->firstWhere('id', $this->currentUserId);
            if($user) {
                return $this->getUserProposals($user);
            }
        }

        return $this->currentUserOpenProposals;
    }

    public function setCurrentUser(?int $userId = null): void
    {
        if(! auth()->user()->can('open_proposals_manager')) {
            return;
        }

        $this->currentUserId = $userId > 0 ? $userId : null;
    }

    public function isHasInGoldList(Proposal $proposal)
    {

        return collect([
            $proposal->guy->lastSubscription,
            $proposal->girl->lastSubscription
        ])
            ->filter()
            ->map(function (Subscriber $subscriber) {
                if($subscriber->status === 'active' && $subscriber->user_id == $this->currentUser()->getKey()){
                    return true;
                }

                return false;
            })
            ->contains(true);
    }
}
