<?php

namespace App\Filament\Pages;

use App\Events\MessageCreatedEvent;
use App\Models\Discussion;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
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

    #[Url]
    public ?int $page = 1;

    public ?array $answerData = [
        'content' => null,
    ];

    public ?Discussion $currentDiscussion = null;

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
        $this->discussion = $discussion;

        $this->currentDiscussion = Discussion::query()
            ->readAt()
            ->where('id', $discussion)
            ->whereHas('usersAssigned', fn ($query) => $query->where('user_id', auth()->id()))
            ->with('user')
            ->first();

        $this->dispatch('discussion.selected', $this->currentDiscussion);
    }

    public function getDiscussions(): LengthAwarePaginator|array
    {
        return Discussion::query()
            ->whereNull('parent_id')
            ->whereHas('usersAssigned', fn ($query) => $query->where('user_id', auth()->id()))
            ->with(['user', 'lastChildren' => fn ($query) => $query->with('user')->readAt()])
            ->select('discussions.*')
            ->readAt()
            ->paginate(25, page: $this->page);
    }

    protected function getForms(): array
    {
        return [
            'answerForm'
        ];
    }

    public function sendMessage(): void
    {
        $newMessage = $this->currentDiscussion->children()->create([
            'content' => $this->answerData['content'],
            'user_id' => auth()->id(),
        ]);

        $newMessage->usersAsRead()->attach(auth()->id(), ['read_at' => now()]);

        $this->answerData = [
            'content' => null,
        ];

        Notification::make()
            ->title('ההודעה נשלחה בהצלחה')
            ->success()
            ->send();

        $this->dispatch('message.created', $newMessage->getKey());
        broadcast(new MessageCreatedEvent($this->currentDiscussion, $newMessage))->toOthers();

    }

    public function answerForm(\Filament\Forms\Form $form): Form
    {
        return $form
            ->statePath('answerData')
            ->schema([
            \Filament\Forms\Components\RichEditor::make('content')
                ->label('תוכן ההודעה')
                ->required()
                ->placeholder('הקלד כאן את תוכן ההודעה'),
        ]);
    }
}
