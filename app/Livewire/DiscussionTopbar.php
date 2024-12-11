<?php

namespace App\Livewire;

use App\Models\Discussion;
use Livewire\Attributes\On;
use Livewire\Component;

class DiscussionTopbar extends Component
{

    public int $countUnreadMessages = 0;

    public function mount(): void
    {
        $this->updateCountUnreadMessages();
    }

    #[On('echo:chat.new-message,MessageCreatedEvent')]
    function updateCountUnreadMessages(): void
    {
        $this->countUnreadMessages = $this->getCountUnreadMessages();
    }
    public function getCountUnreadMessages(): int
    {
        return Discussion::whereNotNull('parent_id')
            ->whereHas('parent.usersAssigned', function($query) {
                $query->where('user_id', auth()->id());
            })->whereDoesntHave('usersAsRead', function($query) {
                $query->where('user_id', auth()->id());
            })->count();
    }

    public function render()
    {
        return view('livewire.discussion-topbar');
    }
}
