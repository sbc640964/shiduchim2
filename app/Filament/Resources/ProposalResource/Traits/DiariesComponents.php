<?php

namespace App\Filament\Resources\ProposalResource\Traits;

use App\Infolists\Components\AudioEntry;
use App\Infolists\Components\FileEntry;
use App\Models\Call;
use App\Models\Diary;
use App\Models\Family;
use App\Models\Form;
use App\Models\Person;
use App\Models\Phone;
use App\Models\Proposal;
use App\Models\SettingOld as Setting;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Str;

trait DiariesComponents
{
    #[Locked]
    public ?string $side = null;

    private static function getTasksSchema(Proposal $proposal): array
    {
        return [
            Forms\Components\Repeater::make('tasks')
                ->label('משימות חדשות')
                ->addActionLabel('הוסף משימה')
                ->defaultItems(0)
                ->columns(3)
                ->reorderable(false)
                ->truncateItemLabel(20)
                ->itemLabel(fn (array $state): ?string => $state['description'] ?? null)
                ->deleteAction(fn ($action) => $action->icon('heroicon-o-trash')->color('danger'))
                ->schema([
                    Forms\Components\Textarea::make('description')
                        ->columnSpanFull()
                        ->label('תיאור')
                        ->live()
                        ->default(fn (Forms\Get $get) => $get('description'))
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
                        ->minDate(now()->addDay()->startOfDay())
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
                    Forms\Components\Select::make('contact_to')
                        ->label('ליצור קשר עם (אם יש)')
                        ->searchable()
                        ->allowHtml()
                        ->getSearchResultsUsing(function (string $query) use ($proposal) {
                            return $proposal->contacts()->searchName($query)->get()->pluck('select_option_html', 'id');
                        })
                ]),
            Forms\Components\Repeater::make('completed_tasks')
                ->label('משימות שהושלמו')
                ->addActionLabel('הוסף משימה שהושלמה')
                ->defaultItems(0)
                ->simple(Forms\Components\Select::make('task_id')
                    ->label('משימה')
                    ->options($proposal->tasks()
                        ->whereNull('completed_at')
                        ->pluck('description', 'id'))
                    ->getSearchResultsUsing(function (string $query) use ($proposal) {
                        return $proposal->tasks()
                            ->where('description', 'like', "%$query%")
                            ->whereNull('completed_at')
                            ->pluck('description', 'id');
                    })
                    ->searchable()
                    ->required()
                )
        ];
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return static::getDiaryForm($form, $this->getOwnerRecord(), $this->side);
    }

    public static function getDiaryForm(Forms\Form $form, Proposal $proposal, $side): Forms\Form
    {
        return $form
            ->columns(2)
            ->schema(static::getDiaryFormSchema($proposal, $side, Call::activeCall()));
    }

    public static function getDiaryFormSchema(?Proposal $proposal = null, ?string $side = null, ?Call $currentCall = null, ?Forms\Get $get = null): array
    {
        if(!$proposal && $get) {
            $proposal = Proposal::find($get('proposal'));
        }

        $proposal ??= Proposal::make();

        return [
            Forms\Components\Select::make('side')
                ->selectablePlaceholder(false)
                ->label('תיעוד עבור')
                ->placeholder('כללי')
                ->live()
                ->afterStateUpdated(function (?string $state, Set $set) use ($proposal) {
                    $state && $set("statuses.$state", $proposal->{"status_$state"});
                })
                ->options([
                    null => 'כללי',
                    'guy' => 'בחור',
                    'girl' => 'בחורה',
                ])
                ->native(false)
                ->default($side),
            Forms\Components\Group::make([
                Forms\Components\Checkbox::make('change_next_dates')
                    ->visible(fn (Forms\Get $get) => ! $get('side'))
                    ->live()
                    ->label('עדכן תאריכי הטיפול הבא'),
                Forms\Components\DatePicker::make('next_date')
                    ->label('תאריך הטיפול הבא')
                    ->helperText(fn (Forms\Get $get) => $get('side') ? null : str('<span class="text-red-600"> >>> תאריך הטיפול ישתנה הן אצל הבחור והן אצל הבחורה <<< </span>')->toHtmlString())
                    ->date()
                    ->weekStartsOnSunday()
                    ->hidden(fn (Forms\Get $get) => ! $get('change_next_dates') && ! $get('side'))
                    ->native(false)
                    ->hiddenLabel(fn (Forms\Get $get) => ! $get('side'))
                    ->displayFormat('d/m/Y')
                    ->disabledDates(function (Forms\Get $get) {
                        $to = $proposal->{"{$get('side')}_next_time"} ?? now();

                        if (now()->isAfter($to)) {
                            $to = now();
                        }

                        $from = $to->copy()->subMonths(2);

                        $dates = [];

                        for ($d = $from; $d->lt($to); $d->addDay()) {
                            $dates[] = $d->format('Y-m-d');
                        }

                        return $dates;
                    })
                    ->default(
                        now()->isFriday()
                            ? now()->next(now()::SUNDAY)->format('Y-m-d')
                            : now()->addDay()->format('Y-m-d')
                    ),
            ]),
            Forms\Components\Fieldset::make('statuses')
                ->label('עדכון סטטוסים')
                ->columnSpanFull()
                ->columns(2)
                ->live()
                ->afterStateUpdated(function (Forms\Get $get, Set $set, ?array $state) use ($proposal) {
                    $side = $get('side');

                    $statusesData = $state['statuses'];

                    $statuses = $get('statuses_info_pack');

                    $set('statuses_info_pack', array_merge($statuses, [
                        'proposal' => $statuses['all_statuses']->firstWhere('name', $statusesData['proposal'] ?? null),
                        $side => $statuses['all_items_statuses']->firstWhere('name', $statusesData[$side] ?? null),
                    ]));

                    $noUpdateSide = ! $side || $statusesData[$side] === $proposal->{$side.'status'};
                    $status = collect($noUpdateSide
                        ? $statuses['all_statuses']
                        : $statuses['all_items_statuses']
                    )
                        ->firstWhere('name', $noUpdateSide ? $statusesData['proposal'] : $statusesData[$side]);

                    if (($status['next_date_delta'] ?? null) === 'none') {
                        $set('next_date', null);
                    } elseif ($status) {
                        $nextDate = now()->add($status['next_date_delta'] ?? 'days', $status['next_date_delta_value'] ?? 1);

                        if ($nextDate->isFriday() || $nextDate->isSaturday()) {
                            $nextDate = $nextDate->next(now()::SUNDAY);
                        }

                        $set('next_date', $nextDate->format('Y-m-d'));
                    }

                })
                ->schema(fn (Forms\Get $get) => [
                    $proposal->statusField(),
                    $proposal->itemStatusField($get('side') ?? $side),
                ]),

            Forms\Components\Tabs::make('tabs')->columnSpanFull()
                ->extraAttributes(['class' => 'tabs-light-view'])
                ->tabs([
                    Forms\Components\Tabs\Tab::make('info')
                        ->label('מידע')
                        ->columns(2)
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->label('סוג')
                                ->options([
                                    'call' => 'שיחה',
                                    'note' => 'הערה',
                                    'other' => 'אחר',
                                ])
                                ->live()
                                ->default(fn () => $currentCall ? 'call' : 'note')
                                ->searchable()
                                ->required(),

                            Forms\Components\DateTimePicker::make('data.date')
                                ->label('תאריך')
                                ->date()
                                ->hidden((bool) $currentCall)
                                ->native(false)
                                ->displayFormat('d/m/Y H:i')
                                ->default(now()->format('Y-m-d H:i:s'))
                                ->required(),

                            Forms\Components\Fieldset::make('data')
                                ->label('')
                                ->columnSpanFull()
                                ->columns(1)
                                ->hidden(fn (Forms\Get $get) => empty($get('type')))
                                ->schema(static::getFieldsByType($currentCall, $proposal)),

                            Forms\Components\Hidden::make('statuses_info_pack')
                                ->default(function (Forms\Get $get, Proposal $proposal) {
                                    $statuses = [
                                        'all_statuses' => collect(Setting::firstOrNew(['key' => 'statuses_proposal'], ['value' => []])->value ?? []),
                                        'all_items_statuses' => collect(Setting::firstOrNew(['key' => 'statuses_proposal_person'], ['value' => []])->value ?? []),
                                    ];

                                    if ($proposal->status) {
                                        $statuses['proposal'] = $statuses['all_statuses']
                                            ->firstWhere('name', $proposal->status);
                                    }

                                    if ($proposal->status_guy) {
                                        $statuses['guy'] = $statuses['all_items_statuses']
                                            ->firstWhere('name', $proposal->status_guy);
                                    }

                                    if ($proposal->status_girl) {
                                        $statuses['girl'] = $statuses['all_items_statuses']
                                            ->firstWhere('name', $proposal->status_girl);
                                    }

                                    return $statuses;
                                }),
                        ]),
                    Forms\Components\Tabs\Tab::make('tasks')
                        ->label('משימות')
                        ->schema(static::getTasksSchema($proposal)),
                    Forms\Components\Tabs\Tab::make('permissions')
                        ->label('הרשאות')
                        ->hidden()
                        ->schema([
                            Forms\Components\Radio::make('confidentiality_level')
                                ->label('רמת הסיווג')
                                ->extraAttributes([
                                    'class' => '[&_p]:text-xs [&_label]:text-sm flex [&_label]:items-center [&_input]:mt-0 flex-col gap-2.5 mt-2 hover:[&>div]:bg-gray-900/10 ![&>div]:cursor-pointer [&>div]:transition [&>div]:rounded-xl [&>div]:p-1.5 [&>div]:ps-3 [&>div]:ring-1 [&>div]:ring-gray-900/10',
                                ])
                                ->default('1')
                                ->options([
                                    '1' => 'פתוח',
                                    '2' => 'שדכן',
                                    '3' => 'שדכן בשידוך מתקדם',
                                    '4' => 'דורש אישור',
                                ])->descriptions([
                                    '1' => 'כולם יכולים לראות',
                                    '2' => 'רק שדכן מטפל יכול לראות',
                                    '3' => 'רק שדכן בשידוך מתקדם יכולים לראות',
                                    '4' => 'דורש אישור מנהל מוקצב זמן',
                                ]),
                        ]),
                ]),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make()
                ->columns(3)
                ->schema([
                    TextEntry::make('label_type')
                        ->hiddenLabel()
                        ->label('סוג')
                        ->badge()
                        ->color(fn (Diary $diary) => $diary->getDiaryTypeColor())
                        ->icon(fn (Diary $diary) => $diary->getDiaryTypeIcon())
                        ->helperText(fn (Diary $diary) => $diary->getCallTypeLabel())
                        ->size(TextEntrySize::Large),

                    TextEntry::make('data.date')
                        ->label('תאריך')
                        ->formatStateUsing(fn ($state) => Carbon::make($state)->hebcal()->hebrewDate(withQuotes: true))
                        ->helperText(fn (Diary $diary) => Carbon::make($diary->data['date'])->format('d/m/Y H:i')),

                    TextEntry::make('createdBy.name')
                        ->label('הוזן ע"י')
                        ->icon('heroicon-o-user'),
                ]),

            Infolists\Components\Tabs::make('tabs')
                ->columnSpanFull()
                ->tabs([
                    Infolists\Components\Tabs\Tab::make('general')
                        ->label('כללי')
                        ->schema([
                            TextEntry::make('data.description')
                                ->label(fn (Diary $diary) => $diary->getLabelDescription())
                                ->size(TextEntrySize::Large),

                            FileEntry::make('file')
                                ->label(fn ($record) => $record->type === 'call' ? 'הקלטה' : 'מסמך/תמונה')
                                ->fileAttribute('url')
                                ->prefixPath(''),
                        ]),

                    Infolists\Components\Tabs\Tab::make('files')
                        ->label('קבצים מצורפים')
                        ->schema([
                            Infolists\Components\RepeatableEntry::make('data.files')
                                ->contained(false)
                                ->hintAction(Infolists\Components\Actions\Action::make('add-file')
                                    ->label('הוסף קובץ')
                                    ->modalWidth('sm')
                                    ->form(fn (Form $form) => $form->schema([
                                        Forms\Components\FileUpload::make('file')
                                            ->label('קובץ')
                                            ->afterStateUpdated(function (TemporaryUploadedFile $state, Set $set) {
                                                $set('name', str($state->getClientOriginalName())->beforeLast('.')->value());
                                            })
                                            ->required(),
                                        Forms\Components\TextInput::make('name')
                                            ->label('שם')
                                            ->required(),
                                    ]))
                                    ->action(function (Diary $record, array $data, Infolists\Components\Actions\Action $action) {
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
                                        ->fileAttribute('path')
                                        ->hiddenLabel()
                                        ->registerActions([
                                            Infolists\Components\Actions\Action::make('delete')
                                                ->label('מחק')
                                                ->requiresConfirmation()
                                                ->iconButton()
                                                ->icon('heroicon-o-trash')
                                                ->color('danger')
                                                ->tooltip('מחק קובץ')
                                                ->action(function (Diary $record, $component, Infolists\Components\Actions\Action $action) {

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
                                ])
                                ->hiddenLabel(),
                        ]),
                ]),
        ]);
    }

    public static function getFieldsByType(?Call $activeCall, Proposal $proposal): array
    {
        $person = $activeCall?->phoneModel?->model;

        if ($person instanceof Family) {
            if (request()->pageIs('admin/people/*')) {
                $personId = str(Str::before(request()->header('referer'), '?'))
                    ->after('admin/people/')
                    ->before('?')
                    ->before('/')
                    ->value();
                $person = $person->people()->firstWhere('id', $personId);
            } else {
                $person = null;
            }
        }

        return [
            //For call type
            Forms\Components\Select::make('data.call_type')
                ->label('סוג שיחה')
                ->searchable()
                ->visible(fn (Forms\Get $get) => $get('type') === 'call')
                ->options([
                    'inquiry_about' => 'בירור',
                    'proposal' => 'הצעה',
                    'heating' => 'חימום',
                    'status_check' => 'בדיקת סטטוס',
                    'assistance' => 'עזרה',
                    'general' => 'כללי',
                ])
                ->default('general')
                ->hidden(),

            Forms\Components\Toggle::make('helpers.show_advanced')
                ->label('הצג פרטים מתקדמים')
                ->live()
                ->default(false),

            Forms\Components\Textarea::make('data.description')
                ->label(fn (Forms\Get $get) => Diary::getLabelDescriptionByType($get('type'))),

            Forms\Components\Select::make('call_id')
                ->label('שייך לשיחה')
                ->native(false)
                ->allowHtml()
                ->live()
                ->afterStateUpdated(function (Set $set, $state) {
                    if($state) {
                        $set(
                            'data.participants',
                            Call::find($state)->phoneModel?->model?->id
                        );
                    }
                })
                ->visible(fn (Forms\Get $get) => ! $activeCall && $get('type') === 'call')
                ->extraAttributes([
                    'class' => 'option-select-w-full',
                ])
                ->options(
                    Call::query()
                        ->latest('finished_at')
                        // where started & finished in fifteen minutes
                        ->where('finished_at', '>', now()->subMinutes(15))
                        ->whereNotNull('started_at')
                        //where is for current user
                        ->where('extension', auth()->user()->ext)
                        ->get()->mapWithKeys(fn (Call $call) => [$call->id => $call->select_option_html])
                ),

            Forms\Components\FileUpload::make('data.file')
                ->disabled((bool) $activeCall)
                ->helperText($activeCall ? "קובץ השיחה יאוחזר באופן אוטו' מהשיחה הפעילה" : null)
                ->visible(function (Forms\Get $get) use ($activeCall) {
                    if (in_array($get('type'), ['document', 'call']) && ! $get('call_id')) {
                        return ! $activeCall
                            || $get('helpers.show_advanced');
                    }

                    return false;
                })
                ->label(fn (Forms\Get $get) => match ($get('type')) {
                    'call' => 'הלקטה',
                    'document' => 'מסמך/תמונה',
                    'email' => 'קבצים מצורפים',
                    default => 'קבצים קשורים',
                }),

            Forms\Components\Repeater::make('data.files')
                ->label('קבצים קשורים')
                ->visible(fn (Forms\Get $get) => $get('helpers.show_advanced'))
                ->addActionLabel('הוסף קובץ')
                ->reorderable(false)
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                ->defaultItems(0)
                ->schema([
                    Forms\Components\FileUpload::make('path')
                        ->label('קובץ')
                        ->required(),
                    Forms\Components\TextInput::make('name')
                        ->label('שם')
                        ->live(onBlur: true),
                ]),

            Forms\Components\Hidden::make('data.call_id')
                ->default($activeCall?->id),

            Forms\Components\Select::make('data.participants')
                ->options(
                    $proposal
                        ->contacts
                        ->load('father', 'fatherInLaw')
                        ->mapWithKeys(fn (Person $person) => [$person->id => $person->getSelectOptionHtmlAttribute()])
                )
                ->visible(fn (Forms\Get $get) => ! $person || $get('helpers.show_advanced'))
                ->label('משתתפים')
                ->searchable()
//                ->multiple()
                ->default($person ? $person->id : null)
//                ->getOptionLabelFromRecordUsing(fn (Person $person) => $person->select_option_html)
//                ->getSearchResultsUsing(function (string $query, self $livewire) {
//                    return $this->getSearchContacts($livewire, $query);
//                })
//                ->searchable()
                ->allowHtml(),
        ];
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
//            ->defaultGroup(
//                Tables\Grouping\Group::make('created_at')
//                    ->titlePrefixedWithLabel(false)
//                    ->getTitleFromRecordUsing(fn (Diary $diary) => Carbon::make($diary->data['date'])->hebcal()->hebrewDate(withQuotes: true))
//                    ->getKeyFromRecordUsing(fn (Diary $diary) => Carbon::make($diary->data['date'])->format('Y-m-d'))
//                    ->groupQueryUsing(fn (Builder $query) => $query->orderBy('data->date', 'desc'))
//            )
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'call' => 'שיחה',
                        'document' => 'מסמך',
                        'email' => 'דוא"ל',
                        'meeting' => 'פגישה',
                        'message' => 'הודעה',
                        'note' => 'הערה',
                        'other' => 'אחר',
                    ])
                    ->label('סוג')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('model_id')
                    ->placeholder('הכל')
                    ->native(false)
                    ->hidden(fn () => ! $this->side)
                    ->options([
                        null => 'כללי',
                        $this->getOwnerRecord()->guy->id => 'בחור',
                        $this->getOwnerRecord()->girl->id => 'בחורה',
                    ]),
            ])
            ->modifyQueryUsing(
                fn (Builder $query) => $query
                    ->when($this->side, fn ($query) => $query
                        ->where('model_id', $this->getOwnerRecord()->{$this->side}->id)
                    )
            )
            ->columns([
                Tables\Columns\IconColumn::make('id')
                    ->label('')
                    ->alignCenter()
                    ->color(fn (Diary $diary) => match ($diary->model_id) {
                        $diary->proposal->guy->id => Color::Blue,
                        $diary->proposal->girl->id => Color::Pink,
                        default => Color::Fuchsia,
                    })
                    ->extraAttributes(fn (Diary $diary) => ['class' => 'icon-circle '.(
                        $diary->proposal->guy->id === $diary->model_id
                            ? 'icon-circle--blue'
                            : ($diary->model_id ? 'icon-circle--pink' : 'icon-circle--fuchsia')
                    )])
                    ->size('sm')
                    ->tooltip(fn (Diary $diary) => match ($diary->model_id) {
                        $diary->proposal->guy->id => 'בחור',
                        $diary->proposal->girl->id => 'בחורה',
                        default => 'כללי'
                    })
                    ->icon(fn (Diary $diary) => $diary->model_type === Person::class ? 'heroicon-s-user' : 'heroicon-s-star'),
                Tables\Columns\TextColumn::make('label_type')
                    ->badge()
                    ->label('סוג')
                    ->color(fn (Diary $diary) => $diary->getDiaryTypeColor())
                    ->icon(fn (Diary $diary) => $diary->getDiaryTypeIcon())
                    ->description(fn (Diary $diary) => $diary->type === 'call' ? match ($diary->data['call_type'] ?? null) {
                        'inquiry_about' => 'בירור',
                        'proposal' => 'הצעה',
                        'heating' => 'חימום',
                        'status_check' => 'בדיקת סטטוס',
                        'assistance' => 'עזרה',
                        'general' => 'כללי',
                        default => null,
                    } : null)
                    ->sortable(['type', 'data->call_type'])
                    ->searchable(['type']),
                Tables\Columns\TextColumn::make('statuses_html')
                    ->label('סטטוסים')
                    ->html()
                    ->size('sm'),
                Tables\Columns\TextColumn::make('call.phoneModel.model')
                    ->label('')
                    ->size('sm')
                    ->description(fn (Diary $diary) => $diary->call?->phoneModel?->number)
                    ->formatStateUsing(fn ($state) => !$state ? null : ($state::class === Person::class ? $state->full_name : $state->name." (בית)")),
                Tables\Columns\TextColumn::make('data.date')
                    ->label('תאריך')
                    ->formatStateUsing(fn ($state) => Carbon::make($state)->hebcal()->hebrewDate(withQuotes: true))
                    ->sortable(query: fn (Builder $query, $direction) => $query->orderBy('data->date', $direction))
                    ->tooltip(fn (Diary $diary) => Carbon::make($diary->data['date'])->format('d/m/Y H:i'))
                    ->size('xs')
                    ->color('gray')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('data.description')
                    ->description(fn (Diary $diary) => match ($diary->type) {
                        'call' => 'סיכום שיחה',
                        'document' => 'תיאור המסמך',
                        'email' => 'תיאור הדוא"ל',
                        'meeting' => 'סיכום הפגישה',
                        'message' => 'תוכן ההודעה',
                        'note' => 'תוכן ההערה',
                        default => 'תיאור',
                    }, 'above')
                    ->extraCellAttributes(['class' => 'description-diary'])
                    ->label('תיאור')
                    ->size('xs')
                    ->wrap()
                    ->searchable(['data->description']),
                Tables\Columns\TextColumn::make('files_count')
                    ->label('קבצים')
                    ->formatStateUsing(fn ($state) => $state == 0 ? '' : "$state קבצים")
                    ->badge()
                    ->alignCenter()
                    ->color(Color::Neutral)
                    ->size('xs'),
            ])
            ->actions($this->getTableRowsActions())
            ->defaultSort('data->date', 'desc');
    }

    public function getTableRowsActions(): array
    {
        return [
            Tables\Actions\Action::make('speaker-recording')
                ->iconButton()
                ->icon('heroicon-o-speaker-wave')
                ->modalWidth('sm')
                ->modalHeading('הקלטת שיחה')
                ->visible(fn (Diary $diary) => $diary->type === 'call' && ! empty($diary->data['file']))
                ->color('gray')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('סגור')
                ->tooltip('הקלטת שיחה')
                ->infolist(fn (Infolist $infolist) => $infolist->schema([
                    Split::make([
                        TextEntry::make('call.duration')
                            ->label('משך הקלטה')
                            ->formatStateUsing(fn (Diary $diary) => $diary->data['duration']
                                ? gmdate($diary->data['duration'] > 3600 ? 'H:i:s' : 'i:s', $diary->data['duration'])
                                    .'/'.gmdate($diary->call?->duration > 3600 ? 'H:i:s' : 'i:s', $diary->call?->duration)
                                : null)
                            ->inlineLabel(),
                    ]),
                    AudioEntry::make('file')
                        ->autoplay()
                        ->hiddenLabel(),
                ])),
            Tables\Actions\ViewAction::make()
                ->modalHeading('פרטי תיעוד')
                ->extraModalFooterActions( fn (Diary $diary) => [
                    Tables\Actions\EditAction::make('type')
                        ->record($diary)
                        ->modalHeading('עריכת תיעוד')
                        ->slideOver()
                        ->action(fn (Diary $diary, array $data) => $diary->update([
                            'data' => array_merge($diary->data, $data['data'] ?? []),
                        ]))
                        ->form(fn (Forms\Form $form) => static::getEditDiaryForm($form))
                ])
                ->infolist(fn (Infolist $infolist) => $this->infolist($infolist))
                ->icon('heroicon-o-eye')
                ->slideOver()
                ->iconButton(),
        ];
    }

    public static function getEditDiaryForm(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('data.description')
                ->autosize()
                ->label('תיאור')
                ->required(),
        ]);
    }
}
