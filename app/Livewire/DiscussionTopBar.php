<?php

namespace App\Livewire;

use App\Models\Discussion;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class DiscussionTopBar extends Component
{
    public function getListeners()
    {
        return array_merge([

        ], auth()->user()->chatRooms->mapWithKeys(
            fn(Discussion $room) => ["echo-private:chat.room.$room->id,MessageCreatedEvent" => '$refresh']
        )->toArray());
    }

    #[Computed]
    public function getUnreadMessages(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getUnreadMessagesQuery()
            ->with('parent', 'user')
            ->latest()
            ->get();
    }

    public function getCountUnreadMessages(): ?int
    {
        $count = $this->getUnreadMessages()->count();
        return $count > 0 ? $count : null;
    }

    public function getUnreadMessagesQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Discussion::query()
            ->whereNotNull('parent_id')
            ->whereHas('parent.usersAssigned', function($query) {
                $query->where('user_id', auth()->id());
            })->whereDoesntHave('usersAsRead', function($query) {
                $query->where('user_id', auth()->id());
            });
    }

    public function render()
    {
        return view('livewire.discussion-topbar');
    }

    public function markAsRead($id): void
    {
        $discussion = Discussion::find($id);
        $discussion->markAsRead();
    }

    public function openViewRoom($id): void
    {
        $this->redirect(\App\Filament\Pages\Inbox::getUrl(['discussion' => $id]));
    }
}
