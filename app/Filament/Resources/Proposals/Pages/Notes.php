<?php

namespace App\Filament\Resources\Proposals\Pages;

use App\Enums\NoteCategory;
use App\Enums\NoteVisibility;
use App\Filament\Resources\Proposals\ProposalResource;
use App\Filament\Widgets\QuickNoteComposer;
use App\Models\Call;
use App\Models\Note;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;

class Notes extends ManageRelatedRecords
{
    protected static string $resource = ProposalResource::class;

    protected static string $relationship = 'notes';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $title = 'תיעוד אישי/ציבורי';

    protected function getHeaderWidgets(): array
    {
        return [
            QuickNoteComposer::make([
                'record' => $this->getRecord(),
            ]),
        ];
    }

    #[On('note-created')]
    public function refreshNotes(): void
    {
        $this->resetTable();
    }

    public static function getNavigationLabel(): string
    {
        return 'תיעוד אישי/ציבורי';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components($this->getNoteFormComponents());
    }

    /**
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    protected function getNoteFormComponents(bool $includeComments = false): array
    {
        return [
            Hidden::make('owner_id')
                ->default(fn () => Auth::id())
                ->required(),

            Select::make('visibility')
                ->label('נראות')
                ->options([
                    NoteVisibility::Private->value => 'אישי',
                    NoteVisibility::Public->value => 'ציבורי',
                ])
                ->default(NoteVisibility::Private->value)
                ->required()
                ->native(false),

            Select::make('sharedWithUsers')
                ->label('שיתוף עם משתמשים')
                ->helperText('שיתוף אפשרי רק בתיעוד אישי. המשתמשים יכולים לצפות ולהגיב בלבד.')
                ->multiple()
                ->searchable()
                ->preload()
                ->relationship('sharedWithUsers', 'name')
                ->visible(fn (Get $get) => $get('visibility') === NoteVisibility::Private->value),

            ToggleButtons::make('category')
                ->label('סוג')
                ->options(NoteCategory::class)
                ->default(NoteCategory::Note)
                ->grouped()
                ->required(),

            Select::make('call_id')
                ->label('שיחה (Call)')
                ->searchable()
                ->preload()
                ->optionsLimit(50)
                ->allowHtml()
                ->getSearchResultsUsing(function (string $search): array {
                    return Call::query()
                        ->when($search, fn (Builder $query) => $query
                            ->where('phone', 'like', "%{$search}%")
                            ->orWhere('extension', 'like', "%{$search}%")
                        )
                        ->latest('started_at')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Call $call) => [$call->id => $call->select_option_html])
                        ->toArray();
                })
                ->getOptionLabelUsing(function ($value): ?string {
                    if (! $value) {
                        return null;
                    }

                    return Call::query()->find($value)?->select_option_html;
                })
                ->nullable(),

            RichEditor::make('content')
                ->label('תוכן')
                ->required(),

            ...($includeComments ? [
                Section::make('תגובות')
                    ->schema([
                        Placeholder::make('comments_list')
                            ->label('תגובות')
                            ->content(function (?Note $record): HtmlString {
                                if (! $record) {
                                    return new HtmlString('');
                                }

                                $comments = $record->comments()
                                    ->with('author')
                                    ->latest()
                                    ->get();

                                return new HtmlString(
                                    view('filament.notes.comments-list', [
                                        'comments' => $comments,
                                    ])->render(),
                                );
                            }),

                        Textarea::make('new_comment')
                            ->label('תגובה חדשה')
                            ->autosize()
                            ->nullable(),
                    ]),
            ] : []),

            Section::make('קבצים מצורפים')
                ->collapsed()
                ->schema([
                    Repeater::make('files')
                        ->relationship('files')
                        ->label('')
                        ->schema([
                            Hidden::make('user_id')
                                ->default(fn () => Auth::id()),

                            TextInput::make('name')
                                ->label('שם')
                                ->required()
                                ->maxLength(255),

                            Textarea::make('description')
                                ->label('תיאור')
                                ->maxLength(255)
                                ->nullable(),

                            FileUpload::make('path')
                                ->label('קובץ')
                                ->openable()
                                ->previewable()
                                ->required(),
                        ])
                        ->columns(1),
                ]),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Stack::make([
                     ViewColumn::make('card')
                        ->label('')
                        ->view('filament.tables.columns.note-card')
                ]),
            ])
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('הוסף תיעוד')
                    ->slideOver()
                    ->modalWidth('lg'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->iconButton()
                    ->modalHeading('פרטי תיעוד')
                    ->slideOver()
                    ->visible(fn (Note $record): bool => Gate::allows('view', $record) && ! Gate::allows('update', $record))
                    ->schema([
                        Section::make()
                            ->schema([
                                TextEntry::make('owner.name')
                                    ->label('נכתב ע"י'),
                                TextEntry::make('visibility')
                                    ->label('נראות')
                                    ->state(fn (Note $record) => $record->visibility === NoteVisibility::Public ? 'ציבורי' : 'אישי'),
                                TextEntry::make('category')
                                    ->label('סוג')
                                    ->state(fn (Note $record) => $record->category?->getLabel() ?? 'הערה')
                                    ->placeholder('הערה'),
                                TextEntry::make('content')
                                    ->label('תוכן')
                                    ->html(),
                            ]),
                    ]),

                EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->iconButton()
                    ->modalWidth('lg')
                    ->slideOver()
                    ->schema(fn (): array => $this->getNoteFormComponents(includeComments: true))
                    ->using(function (Note $record, array $data): Note {
                        $newComment = trim((string) ($data['new_comment'] ?? ''));
                        unset($data['new_comment']);

                        $record->update($data);

                        if ($newComment !== '') {
                            $record->comments()->create([
                                'author_id' => Auth::id(),
                                'body' => $newComment,
                            ]);
                        }

                        return $record;
                    })
                    ->visible(fn (Note $record) => Gate::allows('update', $record)),

                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->iconButton()
                    ->visible(fn (Note $record) => Gate::allows('delete', $record)),
            ])
            ->modifyQueryUsing(function (Builder $query): Builder {
                $user = Auth::user();

                if (! $user) {
                    return $query->whereRaw('1 = 0');
                }

                return $query
                    ->visibleTo($user)
                    ->with(['owner', 'sharedWithUsers', 'call'])
                    ->withCount(['comments', 'files'])
                    ->latest();
            });
    }
}
