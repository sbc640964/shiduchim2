<?php

namespace App\Livewire;

use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\FileUpload;
use Filament\Infolists\Components\RepeatableEntry;
use Blade;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use App\Infolists\Components\FileEntry;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Infolists;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DiaryItem extends Component implements HasForms, HasActions, HasInfolists
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithInfolists;

    public ?Diary $diary;

    public $data = [];

    public function mount(?Diary $diary = null): void
    {
        $this->diary = $diary;

        $this->descriptionForm->fill($diary->toArray());
    }

    public function render()
    {
        return view('livewire.diary-item');
    }

    public function addDiaryAction()
    {
        return Action::make('addDiary')
            ->label('הוסף יומן')
            ->view('livewire.add-diary-modal');
    }

    public function diaryInfolist(Schema $schema): Schema
    {
        return $schema
            ->record($this->diary)
            ->components([
                Grid::make(3)
                    ->schema([
                        TextEntry::make('data.call_type')
                            ->label('סוג שיחה')
                            ->formatStateUsing(fn (string $state) => match ($state) {
                                'inquiry_about' => 'בירור',
                                'proposal' => 'הצעה',
                                'heating' => 'חימום',
                                'status_check' => 'בדיקת סטטוס',
                                'assistance' => 'עזרה',
                                'general' => 'כללי',
                            }),
                        TextEntry::make('date_diary')
                            ->formatStateUsing(fn (Carbon $state) => $state->format((! $state->isToday() ? ($state->isCurrentYear() ? 'd/m' : 'd/m/y') : '').' H:i'))
                            ->extraAttributes(['class' => '!gap-y-0'])
                            ->label(match ($this->diary->type) {
                                'call' => 'תאריך שיחה',
                                'document' => 'תאריך מסמך',
                                'email' => 'תאריך דוא"ל',
                                'meeting' => 'תאריך פגישה',
                                'message' => 'תאריך הודעה',
                                default => 'תאריך',
                            }),
                        TextEntry::make('file.duration')
                            ->formatStateUsing(fn (int $state) => gmdate($state > 3600 ? 'H:i:s' : 'i:s', $state))
                            ->extraAttributes(['class' => '!gap-y-0'])
                            ->label('משך התקשרות'),
                    ]),
            ]);
    }

    public function addFileAction()
    {
        return Action::make('addFile')
            ->label('הוסף קובץ')
            ->schema([
                FileUpload::make('file')
                    ->label('קובץ')
                    ->required()
                    ->acceptedTypes(['image/*', 'video/*', 'audio/*', 'application/pdf'])
                    ->maxSize(1024 * 1024 * 10)
                    ->placeholder('בחר קובץ'),
            ]);
    }

    public function diaryBottomInfolist(Schema $schema): Schema
    {
        return $schema
            ->record($this->diary)
            ->components([
                FileEntry::make('data.file')
                    ->visible(fn () => in_array($this->diary->type, ['document', 'call']))
                    ->placeholder(fn ($record) => $record->type === 'call' ? 'אין הקלטה' : 'אין מסמך')
                    ->label(fn ($record) => $record->type === 'call' ? 'הקלטה' : 'מסמך'),

                RepeatableEntry::make('participants')
                    ->visible(fn () => in_array($this->diary->type, ['call', 'meeting', 'email']))
                    ->extraAttributes(['class' => 'mt-2'])
                    ->placeholder('אין משתתפים')
                    ->label(str(Blade::render(<<<'HTML'
                            <div class="flex gap-1">
                                <div>משתתפים</div>
                                <x-filament::badge size="sm">
                                    {{ $count }}
                                </x-filament::badge>
                            </div>
                        HTML
                        , ['count' => $this->diary->participants->count()]))->toHtmlString())
                    ->grid()
                    ->schema([
                        TextEntry::make('option_phone')
                            ->hiddenLabel()
                            ->html()
                            ->label('שם'),
                    ]),

                RepeatableEntry::make('data.files')
                    ->contained(false)
                    ->hintAction(Action::make('add-file')
                        ->label('הוסף קובץ')
                        ->modalWidth('sm')
                        ->schema(fn (Schema $schema) => $schema->components([
                            FileUpload::make('file')
                                ->label('קובץ')
                                ->afterStateUpdated(function (TemporaryUploadedFile $state, Set $set) {
                                    $set('name', str($state->getClientOriginalName())->beforeLast('.')->value());
                                })
                                ->required(),
                            TextInput::make('name')
                                ->label('שם')
                                ->required(),
                        ])
                            ->model($this->diary)
                        )
                        ->action(function (Diary $record, array $data, Action $action) {
                            $recordData = $record->data;

                            data_set($recordData, 'files', array_merge($recordData['files'] ?? [], [
                                [
                                    'file' => $data['file'],
                                    'name' => $data['name'],
                                ],
                            ]));

                            $record->data = $recordData;

                            $record->save();

                            $action->success();
                        }))
                    ->label('קבצים')
                    ->schema([
                        FileEntry::make('')
                            ->hiddenLabel()
                            ->registerActions([
                                Action::make('delete')
                                    ->label('מחק')
                                    ->requiresConfirmation()
                                    ->iconButton()
                                    ->icon('heroicon-o-trash')
                                    ->color('danger')
                                    ->tooltip('מחק קובץ')
                                    ->action(function (Diary $record, $component, Action $action) {

                                        $state = $component->getState('file', true);

                                        if (! $state) {
                                            $action->failure();
                                            $action->halt();
                                        }

                                        $recordData = $record->data;

                                        data_set($recordData, 'files', array_values(array_filter($recordData['files'] ?? [], fn ($file) => $file['file'] !== $state)));

                                        $record->data = $recordData;

                                        $record->save();

                                        $action->success();
                                    }),
                            ])
                            ->label('קובץ'),
                    ]),
            ]);
    }

    protected function getForms(): array
    {
        return [
            'descriptionForm',
        ];
    }

    public function descriptionForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->model($this->diary)
            ->components([
                Textarea::make('data.description')
                    ->label('תיאור')
                    ->extraAttributes([
                        'class' => 'textarea-infolist-forge hidden-label',
                    ])
                    ->placeholder('הוסף כאן תיאור')
                    ->rows(1)
                    ->extraInputAttributes([
                        'x-on:focus' => 'render()',
                        'wire:input.debounce.500ms' => 'saveDescription',
                        'x-on:saved.window' => '$nextTick(() => render())',
                    ])
                    ->autosize(),
            ]);
    }

    public function saveDescription(): void
    {
        $data = $this->descriptionForm->getState('data.description');

        $this->diary->update([
            'data' => array_merge($this->diary->data, $data['data'] ?? []),
        ]);

        $this->dispatch('saved');
    }
}
