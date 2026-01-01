<?php

namespace App\Filament\Widgets;

use App\Enums\NoteCategory;
use App\Enums\NoteVisibility;
use App\Models\Note;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class QuickNoteComposer extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.quick-note-composer';

    public ?Model $record = null;

    /**
     * @var array{category?: string, content?: string}|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'category' => NoteCategory::Note,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                ToggleButtons::make('category')
                    ->label('סוג')
                    ->options(NoteCategory::class)
                    ->default(NoteCategory::Note)
                    ->grouped()
                    ->required(),

                RichEditor::make('content')
                    ->label('תוכן')
                    ->required(),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        Gate::authorize('create', Note::class);

        if (! $this->record) {
            return;
        }

        $data = $this->form->getState();

        $note = new Note([
            'owner_id' => Auth::id(),
            'visibility' => NoteVisibility::Private,
            'category' => $data['category'] ?? NoteCategory::Note,
            'content' => $data['content'],
        ]);

        $note->documentable()->associate($this->record);
        $note->save();

        $this->form->fill([
            'category' => $data['category'] ?? NoteCategory::Note,
            'content' => null,
        ]);

        $this->dispatch('note-created');

        Notification::make()
            ->title('התיעוד נשמר')
            ->success()
            ->send();
    }
}
