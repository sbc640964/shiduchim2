<?php

namespace App\Filament\Widgets;

use App\Filament\Actions\Call;
use App\Filament\Resources\Proposals\ProposalResource;
use App\Models\Person;
use App\Models\Proposal;
use App\Models\Task;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\Actions\CreateAction;
use Guava\Calendar\Filament\Actions\DeleteAction;
use Guava\Calendar\Filament\Actions\EditAction;
use Guava\Calendar\Filament\Actions\ViewAction;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\DateClickInfo;
use Guava\Calendar\ValueObjects\EventDropInfo;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class NewCalendarWidget extends CalendarWidget
{
    public static function canView(): bool
    {
        return true;
    }

    public ?string $viewType = 'week';
    protected ?string $locale = 'he';

    protected array $options = [
        'duration' => ['weeks' => 1],
        'firstDay' => 0,
        'buttonText' => [
//            {close: 'Close', dayGridMonth: 'month', listDay: 'list', listMonth: 'list', listWeek: 'list', listYear: 'list', resourceTimeGridDay: 'resources', resourceTimeGridWeek: 'resources', resourceTimelineDay: 'timeline', resourceTimelineMonth: 'timeline', resourceTimelineWeek: 'timeline', timeGridDay: 'day', timeGridWeek: 'week', today: 'today'}
            'close' => 'סגור',
            'dayGridMonth' => 'חודש',
            'listDay' => 'רשימת יום',
            'listMonth' => 'רשימת חודש',
            'listWeek' => 'רשימת שבוע',
            'listYear' => 'רשימת שנה',
            'resourceTimeGridDay' => 'משאבים ביום',
            'resourceTimeGridWeek' => 'משאבים בשבוע',
            'resourceTimelineDay' => 'ציר זמן ביום',
            'resourceTimelineMonth' => 'ציר זמן בחודש',
            'resourceTimelineWeek' => 'ציר זמן בשבוע' ,
            'timeGridDay' => 'יום',
            'timeGridWeek' => 'שבוע',
            'today' => 'היום',
        ]
    ];

    protected string | HtmlString | null | bool $heading = 'לוח משימות';

    protected CalendarViewType $calendarView = CalendarViewType::DayGridMonth;

    public $showCompletedTasks = false;
    protected bool $eventClickEnabled = true;
    protected ?string $defaultEventClickAction = 'viewAction'; // view and edit actions are provided by us, but you can choose any action you want, even your own custom ones
    protected bool $dateClickEnabled = true;

    protected bool $eventDragEnabled = true;

    public function toggleView(string $type)
    {
        $types = ['day', 'week', 'month', 'list'];

        if(!in_array($type, $types)) {
            $type = 'week';
        }

        $this->viewType = $type;

        $this->calendarView = match ($this->viewType) {
            'list', 'day' => CalendarViewType::ListWeek,
            default => CalendarViewType::DayGridMonth,
        };

        $durationUnit = match ($this->viewType) {
            'day' => 'day',
            'month' => 'month',
            default => 'week',
        };

        $this->setOption('duration', [$durationUnit => 1]);
        $this->setOption('view', $this->calendarView->value);
    }

    protected function onEventDrop(EventDropInfo $info, \Illuminate\Database\Eloquent\Model $event): bool
    {
        $event->due_date = $info->event->getStart()->addDay()->startOfDay();
        return $event->save();
    }

    public function getEvents(FetchInfo $info): array|Collection|Builder
    {
        return Task::query()
            ->when(!$this->showCompletedTasks, fn($query) => $query->whereNull('completed_at'))
            ->whereBetween('due_date', [$info->start, $info->end])
            ->where('user_id', auth()->id())
            ->with('proposal.people');
    }


    public function eventContent()
    {
        return (string) str(
            <<<'Html'
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-full" :class="{
                    'bg-danger-500': event.extendedProps.priority == 0,
                    'bg-warning-500': event.extendedProps.priority == 1,
                    'bg-success-500': event.extendedProps.priority == 2,
                }"></div>
                <div class="flex-1 flex-grow-1">
                    <div class="text-xs font-semibold text-gray-700" x-text="event.extendedProps.proposal_names"></div>
                    <div class="text-xs text-gray-500" x-html="event.title.replace(/\n/g, '<br>')"></div>
                </div>
                <div x-show="event.extendedProps.is_completed">
                הושלם ב <span class="font-bold text-success-600" x-text="(new Date(event.extendedProps.is_completed)).toLocaleDateString('he-IL', { day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit' })"></span>
                </div>
            </div>
Html

        )->toHtmlString();
    }

    public function getFormSchema(bool $withProposalId = true): array
    {
        return [
            Grid::make(3)
            ->schema(array_filter([
                Textarea::make('description')
                    ->columnSpanFull()
                    ->label('תיאור')
                    ->live()
                    ->required(),
                DateTimePicker::make('due_date')
                    ->label('תאריך יעד')
                    ->placeholder('בחר תאריך')
                    ->firstDayOfWeek(CarbonInterface::SUNDAY)
                    ->native(false)
                    ->displayFormat('d/m/Y H:i')
                    ->time()
                    ->date()
                    ->seconds(false)
                    ->minDate(now()->startOfDay())
                    ->required(),
                Select::make('priority')
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
                Select::make('proposal_id')
                    ->visible($withProposalId)
                    ->label('הצעה')
                    ->searchable()
                    ->live()
                    ->getSearchResultsUsing(fn (string $search) => Proposal::query()
                        ->searchNameInPeople($search)
                        ->get()
                        ->pluck('families_names', 'id')->toArray()
                    )
                    ->getOptionLabelUsing(fn ($value) => Proposal::find($value)?->families_names ?? null),
                Select::make('contact_to')
                    ->visible(fn (Get $get) => $get('proposal_id'))
                    ->label('ליצור קשר עם (אם יש)')
                    ->searchable()
                    ->allowHtml()
                    ->getSearchResultsUsing(function (string $search, Get $get) {
                        $proposal = Proposal::find($get('proposal_id'));

                        if (! $proposal) return [];

                        return $proposal->contacts()->searchName($search)->get()->pluck('select_option_html', 'id');
                    })
                    ->getOptionLabelUsing(fn ($value) => Person::find($value)?->select_option_html ?? null),
            ], fn ($item) => $item !== null)),
        ];
    }

    public function viewAction(): ViewAction
    {
        return ViewAction::make()
            ->modalWidth(Width::Small)
            ->modalFooterActionsAlignment(Alignment::Justify)
            ->extraModalFooterActions($this->modalActions())
            ->schema(fn (Schema $schema) => $this->infolist($schema))
            ->modalHeading('צפייה במשימה');
    }

    protected function modalActions(): array
    {
        return [
            EditAction::make()
                ->schema(fn (Schema $schema) => $schema->schema($this->getFormSchema()))
                ->mountUsing(
                    function (Task $record, Schema $schema, array $arguments) {
                        $schema->fill([
                            'description' => $record->description,
                            'priority' => $record->priority,
                            'contact_to' => $record->data['contact_to'] ?? null,
                            'proposal_id' => $record->proposal_id,
                            'due_date' => $arguments['event']['start'] ?? $record->due_date,
                        ]);
                    }
                )
                ->modalHeading('עריכת משימה'),
            DeleteAction::make()
                ->modalDescription('האם אתה בטוח שברצונך למחוק את המשימה?')
                ->modalHeading('מחיקת משימה'),

            Action::make('completing')
                ->label('עדכון ביצוע משימה')
                ->hidden(fn ($record) => $record->completed_at || ($record->data['contact_to'] ?? null))
                ->button()
                ->modalWidth(Width::Small)
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

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('description')
                ->label('תיאור'),
            Section::make('הצעה')
                ->hidden(fn ($record) => ! $record->proposal_id)
                ->headerActions([
                    Action::make('go_to_proposal')
                        ->link()
                        ->label('עבור להצעה')
                        ->url(fn ($record) => ProposalResource::getUrl('view', ['record' => $record->proposal_id]))
                ])
                ->schema([
                    TextEntry::make('proposal.guy.full_name')
                        ->label('מועמד'),
                    TextEntry::make('proposal.girl.full_name')
                        ->label('מועמדת'),
                ])
                ->columns(),
            TextEntry::make('contact.full_name')
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

    public function createNewTaskAction()
    {
        return CreateAction::make()
            ->label('משימה חדשה')
            ->mountUsing(fn (Schema $schema, array $arguments) => $schema
                ->fill([
                    'due_date' => $arguments['start'] ?? now()->addDay(),
                    'priority' => '1',
                ])
            )
            ->schema(fn (Schema $schema) => $this->getFormSchema(false))
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
            });
    }

    public function getHeaderActions(): array
    {

        return [
            Action::make('completed-tasks')
                ->iconButton()
                ->tooltip($this->showCompletedTasks ? 'הסתר משימות שהושלמו' : 'הצג משימות שהושלמו')
                ->icon($this->showCompletedTasks ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                ->action(function ($livewire) {
                    $this->showCompletedTasks = ! $this->showCompletedTasks;
                    $this->refreshRecords();
                })
                ->outlined(),
            $this->createNewTaskAction(),

            ActionGroup::make(
                array_map(fn ($type) => Action::make("view_$type")
                    ->label(match ($type) {
                        'day' => 'יום',
                        'month' => 'חודש',
                        'list' => 'רשימה',
                        default => 'שבוע',
                    })
                    ->action(function () use ($type) {
                        $this->toggleView($type);
                    })
                    ->color($this->viewType === $type ? 'primary' : 'gray')
                    , ['day', 'week', 'month', 'list'])
            )
            ->button()

            ->label(fn () => match ($this->viewType) {
                'day' => 'יום',
                'month' => 'חודש',
                'list' => 'רשימה',
                default => 'שבוע',
            }),
        ];
    }

    function onDateClick(DateClickInfo $info): void
    {
        $this->mountAction('createNewTask');
    }
}
