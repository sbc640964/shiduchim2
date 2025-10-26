<?php

namespace App\Filament\Resources\Proposals\Traits;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\TextSize;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Schemas\Components\Flex;
use Filament\Actions\ViewAction;
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
use Filament\Infolists;
use Filament\Infolists\Components\TextEntry;
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
            Repeater::make('tasks')
                ->label('משימות חדשות')
                ->addActionLabel('הוסף משימה')
                ->defaultItems(0)
                ->columns(3)
                ->reorderable(false)
                ->truncateItemLabel(20)
                ->itemLabel(fn(array $state): ?string => $state['description'] ?? null)
                ->deleteAction(fn($action) => $action->icon('heroicon-o-trash')->color('danger'))
                ->schema([
                    Textarea::make('description')
                        ->columnSpanFull()
                        ->label('תיאור')
                        ->live()
                        ->default(fn(Get $get) => $get('description'))
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
                        ->minDate(now()->addDay()->startOfDay())
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
                    Select::make('contact_to')
                        ->label('ליצור קשר עם (אם יש)')
                        ->searchable()
                        ->allowHtml()
                        ->getSearchResultsUsing(function (string $query) use ($proposal) {
                            return $proposal->contacts()->searchName($query)->get()->pluck('select_option_html', 'id');
                        })
                ]),
            Repeater::make('completed_tasks')
                ->label('משימות שהושלמו')
                ->addActionLabel('הוסף משימה שהושלמה')
                ->defaultItems(0)
                ->simple(Select::make('task_id')
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

    public function form(Schema $schema): Schema
    {
        return static::getDiaryForm($schema, $this->getOwnerRecord(), $this->side);
    }

    public static function getDiaryForm(Schema $schema, Proposal $proposal, $side): Schema
    {
        return $schema
            ->columns(2)
            ->components(static::getDiaryFormSchema($proposal, $side, Call::activeCall()));
    }

    public static function getDiaryFormSchema(?Proposal $proposal = null, ?string $side = null, ?Call $currentCall = null, ?Get $get = null): array
    {
        if (!$proposal && $get) {
            $proposal = Proposal::find($get('proposal'));
        }

        $proposal ??= Proposal::make();

        return [
            Select::make('side')
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
            Group::make([
                Checkbox::make('change_next_dates')
                    ->visible(fn(Get $get) => !$get('side'))
                    ->live()
                    ->label('עדכן תאריכי הטיפול הבא'),
                DatePicker::make('next_date')
                    ->label('תאריך הטיפול הבא')
                    ->helperText(fn(Get $get) => $get('side') ? null : str('<span class="text-red-600"> >>> תאריך הטיפול ישתנה הן אצל הבחור והן אצל הבחורה <<< </span>')->toHtmlString())
                    ->date()
                    ->weekStartsOnSunday()
                    ->hidden(fn(Get $get) => !$get('change_next_dates') && !$get('side'))
                    ->native(false)
                    ->hiddenLabel(fn(Get $get) => !$get('side'))
                    ->displayFormat('d/m/Y')
                    ->disabledDates(function (Get $get) {
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
            Fieldset::make('statuses')
                ->label('עדכון סטטוסים')
                ->columnSpanFull()
                ->columns(2)
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set, ?array $state) use ($proposal) {
                    $side = $get('side');

                    $statusesData = $state['statuses'];

                    $statuses = $get('statuses_info_pack');

                    $set('statuses_info_pack', array_merge($statuses, [
                        'proposal' => $statuses['all_statuses']->firstWhere('name', $statusesData['proposal'] ?? null),
                        $side => $statuses['all_items_statuses']->firstWhere('name', $statusesData[$side] ?? null),
                    ]));

                    $noUpdateSide = !$side || $statusesData[$side] === $proposal->{$side . 'status'};
                    $status = collect($noUpdateSide
                        ? $statuses['all_statuses']
                        : $statuses['all_items_statuses']
                    )
                        ->firstWhere('name', $noUpdateSide ? $statusesData['proposal'] : $statusesData[$side]);

                    if (($status['next_date_delta'] ?? null) === 'none') {
                        $set('next_date', null);
                    } elseif ($status) {
                        $nextDate = now()->add($status['next_date_delta'] ?? 'days', (int)$status['next_date_delta_value'] ?? 1);

                        if ($nextDate->isFriday() || $nextDate->isSaturday()) {
                            $nextDate = $nextDate->next(now()::SUNDAY);
                        }

                        $set('next_date', $nextDate->format('Y-m-d'));
                    }

                })
                ->schema(fn(Get $get) => [
                    $proposal->statusField(),
                    $proposal->itemStatusField($get('side') ?? $side),
                ]),

            Tabs::make('tabs')->columnSpanFull()
                ->extraAttributes(['class' => 'tabs-light-view'])
                ->tabs([
                    Tab::make('info')
                        ->label('מידע')
                        ->columns(2)
                        ->schema([
                            Select::make('type')
                                ->label('סוג')
                                ->options([
                                    'call' => 'שיחה',
                                    'note' => 'הערה',
                                    'other' => 'אחר',
                                ])
                                ->live()
                                ->default(fn() => $currentCall ? 'call' : 'note')
                                ->searchable()
                                ->required(),

                            DateTimePicker::make('data.date')
                                ->label('תאריך')
                                ->date()
                                ->hidden((bool)$currentCall)
                                ->native(false)
                                ->displayFormat('d/m/Y H:i')
                                ->default(now()->format('Y-m-d H:i:s'))
                                ->required(),

                            Fieldset::make('data')
                                ->label('')
                                ->columnSpanFull()
                                ->columns(1)
                                ->hidden(fn(Get $get) => empty($get('type')))
                                ->schema(static::getFieldsByType($currentCall, $proposal)),

                            Hidden::make('statuses_info_pack')
                                ->default(function (Get $get) use ($proposal) {
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
                    Tab::make('tasks')
                        ->label('משימות')
                        ->schema(static::getTasksSchema($proposal)),
                    Tab::make('permissions')
                        ->label('הרשאות')
                        ->hidden()
                        ->schema([
                            Radio::make('confidentiality_level')
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

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(3)
                ->schema([
                    TextEntry::make('label_type')
                        ->hiddenLabel()
                        ->label('סוג')
                        ->badge()
                        ->color(fn(Diary $diary) => $diary->getDiaryTypeColor())
                        ->icon(fn(Diary $diary) => $diary->getDiaryTypeIcon())
                        ->helperText(fn(Diary $diary) => $diary->getCallTypeLabel())
                        ->size(TextSize::Large),

                    TextEntry::make('data.date')
                        ->label('תאריך')
                        ->formatStateUsing(fn($state) => Carbon::make($state)->hebcal()->hebrewDate(withQuotes: true))
                        ->helperText(fn(Diary $diary) => Carbon::make($diary->data['date'])->format('d/m/Y H:i')),

                    TextEntry::make('created_by_user.name')
                        ->label('הוזן ע"י')
                        ->icon('heroicon-o-user'),
                ]),

            Tabs::make('tabs')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('general')
                        ->label('כללי')
                        ->schema([
                            TextEntry::make('data.description')
                                ->label(fn(Diary $diary) => $diary->getLabelDescription())
                                ->size(TextSize::Large),

                            FileEntry::make('file')
                                ->label(fn($record) => $record->type === 'call' ? 'הקלטה' : 'מסמך/תמונה')
                                ->fileAttribute('url')
                                ->prefixPath(''),
                        ]),

                    Tab::make('files')
                        ->label('קבצים מצורפים')
                        ->schema([
                            RepeatableEntry::make('data.files')
                                ->contained(false)
                                ->hintAction(Action::make('add-file')
                                    ->label('הוסף קובץ')
                                    ->modalWidth('sm')
                                    ->schema(fn(Form $form) => $form->schema([
                                        FileUpload::make('file')
                                            ->label('קובץ')
                                            ->afterStateUpdated(function (TemporaryUploadedFile $state, Set $set) {
                                                $set('name', str($state->getClientOriginalName())->beforeLast('.')->value());
                                            })
                                            ->required(),
                                        TextInput::make('name')
                                            ->label('שם')
                                            ->required(),
                                    ]))
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
                                        ->fileAttribute('path')
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

                                                    if (!$state) {
                                                        $action->failure();
                                                        $action->halt();
                                                    }

                                                    $recordData = $record->data;

                                                    data_set($recordData, 'files', array_values(array_filter($recordData['files'] ?? [], fn($file) => $file['file'] !== $state)));

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
            if (request()->is('admin/people/*')) {
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
            Select::make('data.call_type')
                ->label('סוג שיחה')
                ->searchable()
                ->visible(fn(Get $get) => $get('type') === 'call')
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

            Toggle::make('helpers.show_advanced')
                ->label('הצג פרטים מתקדמים')
                ->live()
                ->default(false),

            Textarea::make('data.description')
                ->label(fn(Get $get) => Diary::getLabelDescriptionByType($get('type'))),

            Select::make('call_id')
                ->label('שייך לשיחה')
                ->native(false)
                ->allowHtml()
                ->live()
                ->afterStateUpdated(function (Set $set, $state) {
                    if ($state) {
                        $set(
                            'data.participants',
                            Call::find($state)->phoneModel?->model?->id
                        );
                    }
                })
                ->visible(fn(Get $get) => !$activeCall && $get('type') === 'call')
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
                        ->get()->mapWithKeys(fn(Call $call) => [$call->id => $call->select_option_html])
                ),

            FileUpload::make('data.file')
                ->disabled((bool)$activeCall)
                ->helperText($activeCall ? "קובץ השיחה יאוחזר באופן אוטו' מהשיחה הפעילה" : null)
                ->visible(function (Get $get) use ($activeCall) {
                    if (in_array($get('type'), ['document', 'call']) && !$get('call_id')) {
                        return !$activeCall
                            || $get('helpers.show_advanced');
                    }

                    return false;
                })
                ->label(fn(Get $get) => match ($get('type')) {
                    'call' => 'הלקטה',
                    'document' => 'מסמך/תמונה',
                    'email' => 'קבצים מצורפים',
                    default => 'קבצים קשורים',
                }),

            Repeater::make('data.files')
                ->label('קבצים קשורים')
                ->visible(fn(Get $get) => $get('helpers.show_advanced'))
                ->addActionLabel('הוסף קובץ')
                ->reorderable(false)
                ->collapsible()
                ->itemLabel(fn(array $state): ?string => $state['name'] ?? null)
                ->defaultItems(0)
                ->schema([
                    FileUpload::make('path')
                        ->label('קובץ')
                        ->required(),
                    TextInput::make('name')
                        ->label('שם')
                        ->live(onBlur: true),
                ]),

            Hidden::make('data.call_id')
                ->default($activeCall?->id),

            Select::make('data.participants')
                ->options(
                    $proposal
                        ->contacts
                        ->load('father', 'fatherInLaw')
                        ->mapWithKeys(fn(Person $person) => [$person->id => $person->getSelectOptionHtmlAttribute()])
                )
                ->visible(fn(Get $get) => !$person || $get('helpers.show_advanced'))
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

    public function table(Table $table): Table
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
                SelectFilter::make('type')
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
                SelectFilter::make('model_id')
                    ->placeholder('הכל')
                    ->native(false)
                    ->hidden(fn() => !$this->side)
                    ->options([
                        null => 'כללי',
                        $this->getOwnerRecord()->guy->id => 'בחור',
                        $this->getOwnerRecord()->girl->id => 'בחורה',
                    ]),
            ])
            ->modifyQueryUsing(
                fn(Builder $query) => $query
                    ->when($this->side, fn($query) => $query
                        ->where('model_id', $this->getOwnerRecord()->{$this->side}->id)
                    )
            )
            ->columns([
                TextColumn::make('createdBy.name')
                    ->label('הוזן ע"י')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                IconColumn::make('id')
                    ->label('')
                    ->alignCenter()
                    ->color(fn(Diary $record) => match ($record->model_id) {
                        $this->getOwnerRecord()->guy->id => Color::Blue,
                        $this->getOwnerRecord()->girl->id => Color::Pink,
                        default => Color::Fuchsia,
                    })
                    ->extraAttributes(fn(Diary $record) => ['class' => 'icon-circle ' . (
                        $record->proposal->guy->id === $record->model_id
                            ? 'icon-circle--blue'
                            : ($record->model_id ? 'icon-circle--pink' : 'icon-circle--fuchsia')
                        )])
                    ->size('sm')
                    ->tooltip(fn(Diary $record) => match ($record->model_id) {
                        $this->getOwnerRecord()->guy->id => 'בחור',
                        $this->getOwnerRecord()->girl->id => 'בחורה',
                        default => 'כללי'
                    })
                    ->icon(fn(Diary $diary) => $diary->model_type === Person::class ? 'heroicon-s-user' : 'heroicon-s-star'),
                TextColumn::make('label_type')
                    ->badge()
                    ->label('סוג')
                    ->color(fn(Diary $record) => $record->getDiaryTypeColor())
                    ->icon(fn(Diary $record) => $record->getDiaryTypeIcon())
                    ->description(fn(Diary $record) => $record->type === 'call' ? match ($record->data['call_type'] ?? null) {
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
                TextColumn::make('statuses_html')
                    ->label('סטטוסים')
                    ->html()
                    ->size('sm'),
                TextColumn::make('call.phoneModel.model.full_name')
                    ->label('')
                    ->size('sm')
                    ->description(fn(Diary $record) => $record->call?->phoneModel?->number)
                    ->formatStateUsing(function ($record) {
                        $model = $record->call?->phoneModel?->model;
                        return !$model ? null : ($model::class === Person::class ? $model->full_name : $model->name . " (בית)");
                    }),
                TextColumn::make('data.date')
                    ->label('תאריך')
                    ->formatStateUsing(fn($state) => Carbon::make($state)->hebcal()->hebrewDate(withQuotes: true))
                    ->sortable(query: fn(Builder $query, $direction) => $query->orderBy('data->date', $direction))
                    ->tooltip(fn(Diary $record) => Carbon::make($record->data['date'])->format('d/m/Y H:i'))
                    ->size('xs')
                    ->color('gray')
                    ->weight('semibold'),

                TextColumn::make('data.description')
                    ->description(fn(Diary $record) => match ($record->type) {
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
                TextColumn::make('files_count')
                    ->label('קבצים')
                    ->formatStateUsing(fn($state) => $state == 0 ? '' : "$state קבצים")
                    ->badge()
                    ->alignCenter()
                    ->color(Color::Neutral)
                    ->size('xs'),
            ])
            ->recordActions($this->getTableRowsActions())
            ->defaultSort('data->date', 'desc');
    }

    public function getTableRowsActions(): array
    {
        return [
            Action::make('speaker-recording')
                ->iconButton()
                ->icon('heroicon-o-speaker-wave')
                ->modalWidth('sm')
                ->modalHeading('הקלטת שיחה')
                ->visible(fn(Diary $record) => $record->type === 'call' && !empty($record->data['file']))
                ->color('gray')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('סגור')
                ->tooltip('הקלטת שיחה')
                ->schema(fn(Schema $schema) => $schema->components([
                    Flex::make([
                        TextEntry::make('call.duration')
                            ->label('משך הקלטה')
                            ->formatStateUsing(fn(Diary $record) => $record->data['duration']
                                ? gmdate($record->data['duration'] > 3600 ? 'H:i:s' : 'i:s', $record->data['duration'])
                                . '/' . gmdate($record->call?->duration > 3600 ? 'H:i:s' : 'i:s', $record->call?->duration)
                                : null)
                            ->inlineLabel(),
                    ]),
                    AudioEntry::make('file')
                        ->autoplay()
                        ->hiddenLabel(),
                ])),
            ViewAction::make()
                ->modalHeading('פרטי תיעוד')
                ->extraModalFooterActions(fn(Diary $record) => [
                    EditAction::make('type')
                        ->record($record)
                        ->modalHeading('עריכת תיעוד')
                        ->slideOver()
                        ->action(fn(Diary $record, array $data) => $record->update([
                            'data' => array_merge($record->data, $data['data'] ?? []),
                        ]))
                        ->schema(fn(Schema $schema) => static::getEditDiaryForm($schema))
                ])
                ->schema(fn(Schema $schema) => $this->infolist($schema))
                ->icon('heroicon-o-eye')
                ->slideOver()
                ->iconButton(),
        ];
    }

    public static function getEditDiaryForm(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('data.description')
                ->autosize()
                ->label('תיאור')
                ->required(),
        ]);
    }
}
