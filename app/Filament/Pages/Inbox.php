<?php

namespace App\Filament\Pages;

use App\Events\MessageCreatedEvent;
use App\Models\Discussion;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Attributes\Url;

class Inbox extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'תיבת הודעות';

    protected static string $view = 'filament.pages.inbox';

    #[Url]
    public ?int $discussion = null;

    public ?int $perPage = 10;


    #[Computed]
    public function list(): LengthAwarePaginator
    {
        return $this->getDiscussions();
    }

    public function getListeners()
    {
        return array_merge([

        ], auth()->user()->chatRooms->mapWithKeys(
            fn(Discussion $room) => ["echo-private:chat.room.$room->id,MessageCreatedEvent" => 'refreshList']
        )->toArray());
    }

    public function refreshList(?array $data = null): void
    {
        unset($this->list);
    }

    public ?array $answerData = [
        'content' => null,
        'rich_content' => null,
        'mode' => 'normal'
    ];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add')
                ->label('הודעה חדשה')
                ->action(function (Action $action, array $data) {
                    $newDiscussion = Discussion::create([
                        'title' => $data['title'],
                        'content' => $data['content'],
                        'user_id' => auth()->id(),
                        'is_popup' => $data['is_popup'] ?? false,
                        'image_hero' => $data['image_hero'] ?? null,
                    ]);

                    $newDiscussion->usersAssigned()->attach(array_merge($data['users'], [auth()->id()]));
                    $newDiscussion->usersAsRead()->attach(auth()->id(), ['read_at' => now()]);

                    $action->success();
                })
                ->form(fn (Form $form) =>
                    $form
                        ->schema([
                            Components\Select::make('users')
                                ->label('נמענים')
                                ->options(
                                    User::all()->mapWithKeys(fn ($user) => [$user->id => $user->name])
                                )
                                ->multiple()
                                ->searchable()
                                ->required()
                                ->placeholder('בחר נמען'),
                            Components\RichEditor::make('content')
                                ->label('תוכן ההודעה')
                                ->required()
                                ->placeholder('הקלד כאן את תוכן ההודעה'),

                            Components\TextInput::make('title')
                                ->label('כותרת')
                                ->placeholder('הקלד כאן את כותרת ההודעה'),

                            Components\Fieldset::make('הודעות מנהל')
                                ->visible(fn () => auth()->user()->can('allowed_send_messages'))
                                ->schema([
                                    Components\Checkbox::make('is_popup')
                                        ->label('הודעה קופצת'),
                                    Components\FileUpload::make('image_hero')
                                        ->label('תמונה')
                                        ->image()
                                        ->maxFiles(1)
                                        ->placeholder('בחר תמונה'),
                                ])
                                ->columns(1)
                        ])
                        ->columns(1)
                ),
        ];
    }

    public function selectDiscussion(int $discussion): void
    {
        if($discussion === $this->discussion) {
            return;
        }
        $this->discussion = $discussion;
        $this->dispatch('prepare-discussion-selected', $this->discussion);

    }

    public function getDiscussions(): LengthAwarePaginator|array
    {
        return Discussion::query()
            ->whereNull('parent_id')
            ->whereHas('usersAssigned', fn ($query) => $query->where('user_id', auth()->id()))
            ->with(['user', 'lastChildren' => fn ($query) => $query->with('user')->readAt()])
            ->withCount('children')
            ->select('discussions.*')
            ->latest('updated_at')
            ->readAt()
            ->paginate($this->perPage, page: 1);
    }

    public function loadMore(): void
    {
        $this->perPage += 10;
    }

    protected function getForms(): array
    {
        return [
            'answerForm'
        ];
    }

    public function sendMessage(): void
    {
        $this->answerForm->validate();

        $discussion = Discussion::find($this->discussion);

        if(!$discussion) {
            return;
        }

        $newMessage = $discussion->children()->create([
            'content' => $this->answerData['mode'] === 'rich'
                ? $this->answerData['rich_content']
                : $this->answerData['content'],
            'user_id' => auth()->id(),
        ]);

        $newMessage->usersAsRead()->attach(auth()->id(), ['read_at' => now()]);

        Notification::make()
            ->title('ההודעה נשלחה בהצלחה')
            ->success()
            ->send();

        $this->dispatch('message.created',
            room: $discussion->id,
            message: $newMessage->getKey()
        );

        broadcast(
            new MessageCreatedEvent($newMessage, 'new')
        );

        $this->answerData = [
            'content' => null,
            'rich_content' => null,
            'mode' => 'normal'
        ];
    }

    public function answerForm(\Filament\Forms\Form $form): Form
    {
        return $form
            ->statePath('answerData')
            ->schema([
            \Filament\Forms\Components\RichEditor::make('rich_content')
                ->label('תוכן ההודעה')
                ->visible(fn () => $this->answerData['mode'] === 'rich')
                ->required()
                ->autofocus()
                ->hintAction($this->toggleModeContentAction())
                ->placeholder('הקלד כאן את תוכן ההודעה'),
            \Filament\Forms\Components\Textarea::make('content')
                ->label('תוכן ההודעה')
                ->hintAction($this->toggleModeContentAction())
                ->visible(fn () => $this->answerData['mode'] === 'normal')
                ->required()
                ->autosize()
                ->rows(1)
                ->autofocus()
                ->placeholder('הקלד כאן את תוכן ההודעה'),
        ]);
    }

    public function toggleModeContentAction()
    {
        return \Filament\Forms\Components\Actions\Action::make('normal')
            ->label(fn () => $this->answerData['mode'] === 'normal' ? 'עבור לעורך עשיר' : 'עבור לעורך פשוט')
            ->visible(auth()->user()->can('write_rich_messages'))
            ->action(fn () => $this->answerData['mode'] = $this->answerData['mode'] === 'normal' ? 'rich' : 'normal');
    }
}
