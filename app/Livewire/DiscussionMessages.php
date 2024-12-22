<?php

namespace App\Livewire;

use App\Events\MessageCreatedEvent;
use App\Models\Discussion;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

#[Lazy]
class DiscussionMessages extends Component implements Forms\Contracts\HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    #[Reactive]
    public int $discussionId;

    public array $usersTyping = [];

    public function getListeners()
    {
        return array_merge([
            'prepare-discussion-selected' => 'selectDiscussion',
            'message.created' => 'updateLastReadMessageId',
        ], auth()->user()->chatRooms->mapWithKeys(
            fn(Discussion $room) => ["echo-private:chat.room.$room->id,MessageCreatedEvent" => 'prependMessageFromBroadcast']
        )->toArray());
    }

    function placeholder(): string
    {
        return <<<'Blade'
            <div class="flex-grow flex justify-center items-center">
                <x-filament::loading-indicator class="w-8 h-8 " />
            </div>
        Blade;
    }

    #[Computed(persist: true)]
    public function discussionMessages(): Collection
    {
        return $this->discussion->children()
            ->with('user', 'otherUsersAsRead')
            ->readAt()
            ->oldest()
//            ->take(100)
            ->get()
            ->prepend(
                $this->discussion
            );
    }

    #[Computed(persist: true)]
    public function lastReadMessageId(): ?int
    {
        return $this->discussion->children()
            ->with('user')
            ->readAt()
            ->latest()
            ->havingNull('read_at')
            ->first()?->id ?? null;
    }

    #[Computed(persist: true )]
    public function discussion(): Discussion
    {
        return Discussion::readAt()
            ->with('user')
            ->find($this->discussionId);
    }

    public function updateLastReadMessageId(): void
    {
        unset($this->lastReadMessageId);
    }

    public function prependMessage($room, $userId = null): void
    {
        $this->dispatch('win-message-created',
            room: $room,
            userId: $userId,
        );
    }

    public function selectDiscussion(int $discussion): void
    {
        if($this->discussion->id === $discussion) {
            return;
        }

        $this->discussionId = $discussion;

        $this->updateLastReadMessageId();
        unset($this->discussionMessages, $this->discussion);
        $this->dispatch('discussion-selected', $discussion);
    }

    public function prependMessageFromBroadcast(array $payload): void
    {
        $this->prependMessage($payload['discussion']['id'], $payload['user']['id'] ?? null);

        unset($this->discussionMessages);
    }

    public function updateMessage($id, $content): void
    {
        $this->discussion->children()
            ->when(! auth()->user()->can('change_other_messages'), fn ($q) => $q->whereUserId(auth()->id()))
                ->findOrFail($id)->update([
                'content' => $content,
            ]);

        unset($this->discussionMessages);
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
            : $this->discussionMessages->firstWhere('id', $id);

        $model?->markAsRead();
    }

    public function userTyping(int $id, bool $bool): void
    {
        if(! $bool) {
            $this->usersTyping = array_filter($this->usersTyping, fn($userName, $userId) => $userId !== $id);
        }
        $this->usersTyping[$id] = $bool;
    }

    public function editRoomAction(): Action
    {
        return EditAction::make('editRoom')
            ->label('ערוך חדר')
            ->tooltip('עריכת חדר')
            ->record($this->discussion)
            ->iconButton()
            ->icon('heroicon-o-pencil')
            ->color('gray')
            ->modalHeading('עריכת חדר')
            ->visible(fn() =>
                auth()->user()->can('change_other_messages')
                || $this->discussion->user_id === auth()->id()
            )
            ->after(function () {
                unset($this->discussion);
            })
            ->form([
                TextInput::make('title')
                    ->label('כותרת')
                    ->placeholder('כותרת'),
                Forms\Components\Select::make('usersAssigned')
                    ->label('נמענים')
                    ->live()
                    ->preload()
                    ->rule(fn ($state) => function ($value, $attribute, $fail) use ($state) {
                        if(! in_array(auth()->id(), $state) ) {
                            $fail('אתה חייב להיות נמען בחדר');
                        }
                    })
                    ->relationship('usersAssigned', 'name')
                    ->multiple()
                    ->searchable()
                    ->required()
                    ->placeholder('בחר נמען'),
            ]);
    }
}
