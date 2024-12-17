<?php

namespace App\Livewire;

use App\Events\MessageCreatedEvent;
use App\Models\Discussion;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class DiscussionMessages extends Component
{
    public int $discussionId;

    public function getListeners()
    {
        return array_merge([
            'discussion.selected' => 'selectDiscussion',
        ], auth()->user()->chatRooms->mapWithKeys(
            fn(Discussion $room) => ["echo-private:chat.room.$room->id,MessageCreatedEvent" => 'prependMessageFromBroadcast']
        )->toArray());
    }

    #[Computed]
    public function messages(): Collection
    {
        return $this->discussion->children()
            ->with('user')
            ->readAt()
            ->oldest()
            ->take(100)
            ->get()
            ->prepend(
                $this->discussion
            );
    }

    #[Computed]
    public function lastReadMessageId(): ?int
    {
        return $this->messages->reverse()->firstWhere('read_at', '!=', null)?->id ?? null;
    }

    #[Computed]
    public function discussion(): Discussion
    {
        return Discussion::readAt()
            ->with('user')
            ->find($this->discussionId);
    }

    public function mount(Discussion $discussion): void
    {
        $this->discussionId = $discussion->id;
    }

    public function updateLastReadMessageId(): ?int
    {
        return $this->messages->reverse()->firstWhere('read_at', '!=', null)?->id ?? null;
    }

    public function prependMessage(): void
    {
        $this->dispatch('win-message-created');
    }

    public function selectDiscussion(Discussion $discussion): void
    {
        if($this->discussion->id === $discussion->id) {
            return;
        }

        $this->discussionId = $discussion->id;

        $this->dispatch('$refresh');
    }

    public function prependMessageFromBroadcast(array $payload): void
    {
        $this->prependMessage();
    }


    public function render()
    {
        return view('livewire.discussion-messages');
    }

    public function markAsRead($id): void
    {
        /** @var Discussion $model */
        $model = $this->discussion->id === (int) $id
            ? $this->discussion
            : $this->messages->firstWhere('id', $id);

        $model?->markAsRead();
    }
}
