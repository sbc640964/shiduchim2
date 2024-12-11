<?php

namespace App\Livewire;

use App\Models\Discussion;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class DiscussionMessages extends Component
{
    public Discussion $discussion;

    public int $discussionId;

    public Collection $messages;

    public ?int $lastReadMessageId = null;

    public function mount()
    {
        $this->fill([
            'messages' => $this->discussion->children()
                ->with('user')
                ->readAt()
                ->oldest()
                ->take(100)
                ->get(),
        ]);

        $this->updateLastReadMessageId();
    }

    public function updateLastReadMessageId(): void
    {
        $this->lastReadMessageId = $this->messages->reverse()->firstWhere('read_at', '!=', null)?->id ?? null;
    }

    #[On('message.created')]
    public function prependMessage($id): void
    {
        $this->messages->push(Discussion::with('user')->readAt()->find($id));
        $this->updateLastReadMessageId();
        $this->dispatch('win-message-created');
    }

    #[On('discussion.selected')]
    public function selectDiscussion(Discussion $discussion): void
    {
        if($this->discussion->id === $discussion->id) {
            return;
        }

        $this->discussionId = $discussion->id;

        $this->discussion = $discussion;
        $this->mount();
    }

    #[On('echo-private:chat.room.{discussion.id},MessageCreatedEvent')]
    public function prependMessageFromBroadcast(array $payload): void
    {
        $this->prependMessage($payload['message']['id']);
    }


    public function render()
    {
        return view('livewire.discussion-messages');
    }

    public function markAsRead($id): void
    {
        $model = $this->messages->firstWhere('id', $id);

        $model?->markAsRead();
    }
}
