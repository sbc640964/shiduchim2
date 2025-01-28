<?php

namespace App\Filament\Widgets;

use App\Filament\Actions\Call;
use App\Filament\Resources\ProposalResource;
use App\Models\Proposal;
use App\Models\Task;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    protected int | string | array $columnSpan = 1;

    public static function canView(): bool
    {
        return request()->path() === 'admin/tasks';
    }

    protected static ?int $sort = -4;
    public string|null|Model $model = Task::class;

    public bool $showCompletedTasks = false;

    protected static string $view = 'filament-fullcalendar::fullcalendar';

    public function fetchEvents(array $info): array
    {
        // You can use $fetchInfo to filter events by date.
        // This method should return an array of event-like objects. See: https://github.com/saade/filament-fullcalendar/blob/3.x/#returning-events
        // You can also return an array of EventData objects. See: https://github.com/saade/filament-fullcalendar/blob/3.x/#the-eventdata-class

        return Task::query()
            ->when(! $this->showCompletedTasks, fn ($query) => $query->whereNull('completed_at'))
            ->whereBetween('due_date', [$info['start'], $info['end']])
            ->where('user_id', auth()->id())
            ->with('proposal.people')
            ->get()
            ->map(function ($task) {
                return EventData::make()
                    ->id($task->id)
                    ->title($task->descriptionToCalendar())
                    ->start($task->due_date)
                    ->borderColor($task->completed_at ? 'green' : 'red')
                    ->end($task->due_date);
            })
            ->toArray();
    }

    public function eventDidMount(): string
    {
        return <<<JS
            function({ event, timeText, isStart, isEnd, isMirror, isPast, isFuture, isToday, el, view }){
                el.setAttribute("x-tooltip.html.max-width.350.theme.light", "tooltip");
                el.setAttribute("x-data", `{ tooltip: "`+event.title+`" }`);
                (timeText === '0') && el.querySelector('.fc-event-time').remove();
            }
        JS;
    }

    public function eventContent(): string
    {
        return <<<JS
            null
        JS;
    }

    /**
     * @param bool $withProposalId
     * @return array
     */
    public function getFormSchema(bool $withProposalId = true): array
    {
        return [
            Forms\Components\Grid::make(3)
            ->schema(array_filter([
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull()
                    ->label('תיאור')
                    ->live()
                    ->required(),
                Forms\Components\DateTimePicker::make('due_date')
                    ->label('תאריך יעד')
                    ->placeholder('בחר תאריך')
                    ->firstDayOfWeek(CarbonInterface::SUNDAY)
                    ->native(false)
                    ->displayFormat('d/m/Y H:i')
                    ->time()
                    ->date()
                    ->seconds(false)
                    ->minDate(now())
                    ->required(),
                Forms\Components\Select::make('priority')
                    ->label('עדיפות')
                    ->selectablePlaceholder(false)
                    ->native(false)
                    ->default('1')
                    ->options([
                        '0' => 'נמוכה',
                        '1' => 'בינונית',
                        '2' => 'גבוהה',
                    ])
                    ->required(),
                $withProposalId ? Forms\Components\Select::make('proposal_id')
                    ->label('הצעה')
                    ->searchable()
                    ->live()
                    ->getSearchResultsUsing(fn (string $search) => Proposal::query()
                        ->searchNameInPeople($search)
                        ->get()
                        ->pluck('families_names', 'id')->toArray()
                    ) : null,
                Forms\Components\Select::make('contact_to')
                    ->visible(fn (Forms\Get $get) => $get('proposal_id'))
                    ->label('ליצור קשר עם (אם יש)')
                    ->searchable()
                    ->allowHtml()
                    ->getSearchResultsUsing(function (string $search, Forms\Get $get) {
                        $proposal = Proposal::find($get('proposal_id'));

                        if (! $proposal) return [];

                        return $proposal->contacts()->searchName($search)->get()->pluck('select_option_html', 'id');
                    })
            ], fn ($item) => $item !== null)),
        ];
    }

    public function viewAction(): Actions\ViewAction
    {
        return Actions\ViewAction::make()
            ->modalWidth(MaxWidth::Small)
            ->modalFooterActionsAlignment(Alignment::Justify)
            ->infolist(fn (Infolists\Infolist $infolist) => $this->infolist($infolist))
            ->modalHeading('צפייה במשימה');
    }

    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make()
                ->mountUsing(
                    function (Task $record, Forms\Form $form, array $arguments) {
                        $form->fill([
                            'description' => $record->description,
                            'priority' => $record->priority,
                            'contact_to' => $record->data['contact_to'] ?? null,
                            'proposal_id' => $record->proposal_id,
                            'due_date' => $arguments['event']['start'] ?? $record->due_date,
                        ]);
                    }
                )
                ->modalHeading('עריכת משימה'),
            Actions\DeleteAction::make()
                ->modalDescription('האם אתה בטוח שברצונך למחוק את המשימה?')
                ->modalHeading('מחיקת משימה'),

            Action::make('completing')
                ->label('עדכון ביצוע משימה')
                ->hidden(fn ($record) => $record->completed_at || ($record->data['contact_to'] ?? null))
                ->button()
                ->modalWidth(MaxWidth::Small)
                ->requiresConfirmation()
                ->color('success')
                ->modalHeading('סיום משימה')
                ->modalDescription('האם אתה בטוח שברצונך לסיים את המשימה?')
                ->action(function (Task $record, $livewire) {
                    $record->completed();
                    $livewire->refreshRecords();
                }),

            Call::taskActionDefaultPhone()
                ->hidden(fn ($record) => $record->completed_at)
                ->icon(null)
                ->size('md'),
        ];
    }

    public function infolist(Infolists\Infolist $infolist): Infolists\Infolist
    {
        return $infolist->schema([
            Infolists\Components\TextEntry::make('description')
                ->label('תיאור'),
            Infolists\Components\Section::make('הצעה')
                ->hidden(fn ($record) => ! $record->proposal_id)
                ->headerActions([
                    Infolists\Components\Actions\Action::make('go_to_proposal')
                        ->link()
                        ->label('עבור להצעה')
                        ->url(fn ($record) => ProposalResource::getUrl('view', ['record' => $record->proposal_id]))
                ])
                ->schema([
                    Infolists\Components\TextEntry::make('proposal.guy.full_name')
                        ->label('מועמד'),
                    Infolists\Components\TextEntry::make('proposal.girl.full_name')
                        ->label('מועמדת'),
                ])
                ->columns(),
            Infolists\Components\TextEntry::make('contact.full_name')
                ->hidden(fn ($record) => ! ($record->data['contact_to'] ?? null))
//                ->suffixActions([
//                    Call::infolistActionDefaultPhone(
//                        person: $this->getRecord()->contact,
//                        proposal: $this->getRecord()->proposal,
//                    )
//                ])
                ->label('ליצור קשר עם'),
        ]);
    }

    protected function headerActions(): array
    {
        return [
            Action::make('completed-tasks')
                ->iconButton()
                ->tooltip($this->showCompletedTasks ? 'הסתר משימות שהושלמו' : 'הצג משימות שהושלמו')
                ->icon($this->showCompletedTasks ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                ->action(function ($livewire) {
                    $this->showCompletedTasks = ! $this->showCompletedTasks;
                    $livewire->refreshRecords();
                })
                ->outlined(),
            Actions\CreateAction::make()
                ->label('משימה חדשה')
                ->mountUsing(fn (Forms\Form $form, array $arguments) => $form
                    ->fill([
                        'due_date' => $arguments['start'] ?? now()->addDay(),
                        'priority' => '1',
                    ])
                )
                ->modalHeading('משימה חדשה')
                ->action(function (array $data, self $livewire, $action) {
                    Task::create($data + [
                        'user_id' => auth()->id(),
                        'data' => [
                            'contact_to' => $data['contact_to'] ?? null,
                        ],
                    ]);
                    $livewire->refreshRecords();
                    $action->success('המשימה נוצרה בהצלחה');
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'יומן משימות';
    }

    public function config(): array
    {
        return [
           'headerToolbar' => [
               'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,dayGridWeek,listDay',
           ],
            'buttonText' => [
                'listDay' => 'היום',
            ],
            'initialView' => 'dayGridWeek',
        ];
    }
}
