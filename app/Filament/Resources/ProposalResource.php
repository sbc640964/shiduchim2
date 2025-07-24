<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Settings\Pages\Statuses;
use App\Filament\Resources\ProposalResource\Pages;
use App\Filament\Resources\ProposalResource\Pages\Diaries;
use App\Models\City;
use App\Models\Diary;
use App\Models\Person;
use App\Models\Proposal;
use App\Models\School;
use App\Models\SettingOld as Setting;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Navigation\NavigationGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\DeleteAction as TableDeleteAction;
use Filament\Tables\Columns;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Guava\FilamentClusters\Forms\Cluster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class ProposalResource extends Resource
{
    protected static ?string $model = Proposal::class;

    protected static ?string $slug = 'proposals';

    protected static ?string $label = 'הצעה';

    protected static ?string $pluralLabel = 'הצעות';

    protected static ?string $navigationIcon = 'iconsax-bul-lamp-charge';

    protected static ?string $recordTitleAttribute = 'families_names';

    protected static ?string $recordRouteKeyName = 'proposals.id';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withIndividualPeople()
            ->with(
                'lastGuyDiary',
                'lastGirlDiary',
                'guy.father', 'guy.mother', 'guy.parentsFamily', 'guy.schools', 'guy.lastSubscription.matchmaker',
                'girl.father', 'girl.mother', 'girl.parentsFamily', 'girl.schools', 'girl.lastSubscription.matchmaker',
            );
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('not')
                ->label('הערה'),

            Forms\Components\Section::make('מתקדם')
                ->collapsed()
                ->schema([
                    Forms\Components\Select::make('offered_by')
                        ->label('מציע')
                        ->relationship('offeredBy', modifyQueryUsing: fn (Builder $query) => $query->orderBy('first_name')->orderBy('last_name'))
                        ->getOptionLabelFromRecordUsing(fn (Person $record) => $record->full_name)
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $query) => $this->searchPerson($query))
                        ->optionsLimit(60),
                ]),

        ])->columns(1);
    }

    public static function tableFilters(Table $table): Table
    {
        return $table
            ->filters([
                Filters\Filter::make('show-finished')
                    ->label('הצג הצעות סגורות')
                    ->toggle()
                    ->baseQuery(function (Builder $query, array $data) {
                        return $query->when($data['isActive'] ?? false, fn (Builder $query) => $query
                            ->withoutGlobalScope('withoutClosed')
                        );
                    }),
                Filters\Filter::make('shares-proposals')
                    ->label('הצג רק שיתופי פעולה')
                    ->toggle()
                    ->baseQuery(function (Builder $query, array $data) {
                        return $query->when($data['isActive'] ?? false, fn (Builder $query) =>
                            $query->whereIn('id', function ($sub) {
                                $sub->select('proposal_id') // שנה ל-id של הטבלה הראשית שלך
                                    ->from('user_proposal') // טבלת הפיבוט
                                    ->groupBy('proposal_id')
                                    ->havingRaw('COUNT(user_id) >= 2');
                            })
                        );
                    }),
                Filters\Filter::make('show-hidden')
                    ->label('הצג הצעות מוסתרות')
                    ->toggle()
                    ->default(fn ($livewire) => ! ( $livewire instanceof Pages\ListProposals ))
                    ->baseQuery(function (Builder $query, array $data) {
                        return $query->when($data['isActive'] ?? false, fn (Builder $query) => $query
                            ->withoutGlobalScope('withoutHidden')
                        );
                    }),
                Filters\Filter::make('f')
                    ->columnSpanFull()
                    ->columns(1)
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['status'] ?? null, fn (Builder $query, $value) => $query
                                ->{($data['mode'] ?? 'show') === 'show' ? 'whereIn' : 'whereNotIn'}('status', $value)
                            )
                            ->when($data['status_guy'] ?? null, fn (Builder $query, $value) => $query
                                ->{($data['mode_guy'] ?? 'show') === 'show' ? 'whereIn' : 'whereNotIn'}('status', $value)
                            )
                            ->when($data['status_girl'] ?? null, fn (Builder $query, $value) => $query
                                ->{($data['mode_girl'] ?? 'show') === 'show' ? 'whereIn' : 'whereNotIn'}('status_girl', $value)
                            )
                            ->when($data['guy'] ?? null, fn (Builder $query, $value) => $query
                                ->searchNameInPeople($data['guy'], 'B')
                            )
                            ->when($data['city'] ?? null, fn (Builder $query, array $value) => $query
                                ->whereRelation('people.parentsFamily', function (Builder $query) use ($value) {
                                    $query->whereIn('city_id', $value);
                                })
                            )
                            ->when($data['created_at'] ?? null, fn (Builder $query, string $value) => $query
                                ->whereBetween('proposals.created_at', collect(explode(' - ', $value))
                                    ->map(fn ($date, $index) => Carbon::createFromFormat('d/m/Y', $date)
                                        ->{$index ? 'endOfDay' : 'startOfDay'}())
                                    ->toArray()
                                )
                            )
                            ->when($data['synagogue'] ?? null, fn (Builder $query, $value) => $query
                                ->whereRelation('people.father.schools', 'id', $value)
                            )
                            ->when($data['school'] ?? null, fn (Builder $query, $value) => $query
                                ->whereRelation('people.schools', 'id', $value)
                            )
                            ->when($data['matchmaker'] ?? null, fn (Builder $query, $value) => $query
                                ->whereRelation('users', 'id', $value)
                            )
                            ->when($data['girl'] ?? null, fn (Builder $query, $value) => $query
                                ->searchNameInPeople($data['girl'], 'G')
                            );
                    })
                    ->form([
                        Group::make([
                            Cluster::make([
                                Forms\Components\Select::make('status')
                                    ->columnSpan(2)
                                    ->multiple()
                                    ->options(collect(Setting::find('statuses_proposal')?->value ?? [])
                                        ->mapWithKeys(function ($status) {
                                            return [
                                                $status['name'] => \View::make('components.status-option-in-select', [
                                                    'status' => $status,
                                                ])->render(),
                                            ];
                                        })
                                        ->toArray())
                                    ->searchValues()
                                    ->searchable()
                                    ->allowHtml()
                                    ->label('סטטוס הצעה'),
                                Forms\Components\Select::make('mode')
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->default('show')
                                    ->options([
                                        'show' => 'להציג',
                                        'hidden' => 'להסתיר',
                                    ]),
                            ])
                                ->label('סטטוס הצעה')
                                ->columns(3),

                            Cluster::make([
                                TextInput::make('guy')
                                    ->placeholder('שם בחור')
                                    ->columnSpan(4)
                                    ->label('בחור'),
                                Forms\Components\Select::make('status_guy')
                                    ->multiple()
                                    ->options(collect(Setting::find('statuses_proposal_person')?->value ?? [])
                                        ->mapWithKeys(function ($status) {
                                            return [
                                                $status['name'] => \View::make('components.status-option-in-select', [
                                                    'status' => $status,
                                                ])->render(),
                                            ];
                                        })
                                        ->toArray())
                                    ->searchValues()
                                    ->columnSpan(2)
                                    ->placeholder('סטטוס')
                                    ->searchable()
                                    ->allowHtml()
                                    ->label('סטטוס הצעה'),
                                Forms\Components\Select::make('mode_guy')
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->default('show')
                                    ->options([
                                        'show' => 'להציג',
                                        'hidden' => 'להסתיר',
                                    ]),
                            ])
                                ->columns(7)
                                ->columnSpan(2)
                                ->label('בחור'),

                            Cluster::make([
                                TextInput::make('girl')
                                    ->columnSpan(4)
                                    ->placeholder('שם בחורה')
                                    ->label('בחורה'),
                                Forms\Components\Select::make('status_girl')
                                    ->multiple()
                                    ->columnSpan(2)
                                    ->options(collect(Setting::find('statuses_proposal_person')?->value ?? [])
                                        ->mapWithKeys(function ($status) {
                                            return [
                                                $status['name'] => \View::make('components.status-option-in-select', [
                                                    'status' => $status,
                                                ])->render(),
                                            ];
                                        })
                                        ->toArray())
                                    ->searchValues()
                                    ->placeholder('סטטוס')
                                    ->searchable()
                                    ->allowHtml()
                                    ->label('סטטוס הצעה'),
                                Forms\Components\Select::make('mode_girl')
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->default('show')
                                    ->options([
                                        'show' => 'להציג',
                                        'hidden' => 'להסתיר',
                                    ]),
                            ])
                                ->columnSpan(2)
                                ->columns(7)
                                ->label('בחורה'),

                            Forms\Components\Select::make('city')
                                ->label('עיר')
                                ->multiple()
                                ->options(City::orderBy('name')->pluck('name', 'id')),

                            Forms\Components\Select::make('synagogue')
                                ->native(false)
                                ->options(School::with('city')
                                    ->orderBy('name')
                                    ->whereType(10)
                                    ->get()
                                    ->mapWithKeys(fn (School $school) => [$school->id => $school->name.' - '.$school->city?->name])
                                )
                                ->searchable()
                                ->preload()
                                ->label('בית כנסת'),

                            Forms\Components\Select::make('school')
                                ->native(false)
                                ->options(School::with('city')
                                    ->orderBy('name')
                                    ->where('type', '!=', 10)
                                    ->get()
                                    ->mapWithKeys(fn (School $school) => [$school->id => $school->name.' - '.$school->city?->name])
                                )
                                ->searchable()
                                ->preload()
                                ->label('מוסד לימודים'),

                            DateRangePicker::make('created_at')
                                ->label('תאריך יצירה')
                                ->icon('heroicon-s-x-mark')
                                ->disableClear(false)
                                ->firstDayOfWeek(0)
                                ->format('d/m/Y'),

                            Forms\Components\Select::make('matchmaker')
                                ->visible(fn () => auth()->user()?->canAccessAllTimeSheets())
                                ->label('שדכן')
                                ->options(User::whereRelation('roles', 'name', config('app.matchmaker_role_name'))->pluck('name', 'id'))
                                ->searchable(),

                        ])->columns(6)->hiddenLabel(),
                    ]),

            ], FiltersLayout::AboveContent);
    }

    public static function table(Table $table): Table
    {
        return static::tableFilters($table)
            ->defaultSort(fn ($query, $direction) => $query->orderByRaw('LEAST(
                IF(`proposals`.`guy_next_time`, `proposals`.`guy_next_time`, "9999-12-31"),
                IF(`proposals`.`girl_next_time`, `proposals`.`girl_next_time`, "9999-12-31")
            )'))
            ->paginationPageOptions([10, 50, 100, 200])
//            ->contentGrid([1])
            ->actions([
                ActionGroup::make([
                    static::getAddDiaryAction(),
                    static::getAddDiaryAction('guy'),
                    static::getAddDiaryAction('girl'),
                ]),
                ...static::showHideActions(),
                static::getCloseProposalAction(),
                static::getOpenProposalAction(),
                TableDeleteAction::make()
                    ->label('מחק')
                    ->iconButton()
                    ->icon('iconsax-bul-trash')
                    ->before(fn (Proposal $record) => $record->deleteDependencies())
                    ->tooltip('מחק הצעה'),
            ])
            ->bulkActions(static::getBulkActions())
            ->recordClasses(fn (Proposal $proposal) => [
                "bg-red-50 hover:bg-red-100" => $proposal->hidden_at,
//                "relative before:content-[''] before:border-s-[6px] before:z-20 before:border-red-600 before:h-full before:absolute before:start-0" => $proposal->hidden_at,
            ])
            ->columns(static::getColumns());
    }

    static public function getBulkActions(): array
    {
        return [
            BulkAction::make('hidden')
                ->label('הסתר הצעות')
                ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->hide()),
            BulkAction::make('re-hidden')
                ->label('ביטול הסתרה להצעות')
                ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->show()),
            BulkAction::make('share')
                ->label('שתף הצעות')
                ->modalWidth(MaxWidth::Small)
                ->form([
                    Forms\Components\Select::make('users')
                        ->label('שדכנים')
                        ->options(User::orderBy('name')->pluck('name', 'id'))
                        ->multiple()
                ])
                ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                    $users = $records->map->share($data['users'])
                        ->flatten(1)->unique('id');

                    Notification::make()
                        ->title(auth()->user()->name . " שיתף איתך {$records->count()} הצעות")
                        ->icon('iconsax-bul-notification-bing')
                        ->iconColor('primary')
                        ->body('עבור כל אחת מהם קיבלת הודעה בתיבת ההתראות, על מנת להציג אותם עליך ללחוץ על הפעמון בצד שמאל של המסך למעלה')
                        ->broadcast($users);
                }),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }

    public static function getColumns(?bool $innerStudent = false): array
    {
        return [
            Columns\TextColumn::make('created_at')
                ->sortable(['proposals.created_at'])
                ->toggleable()
                ->toggledHiddenByDefault()
                ->size('xs')
                ->description(fn (Proposal $proposal) => $proposal->created_at->hebcal()->hebrewDate(withQuotes: true))
                ->label('נוצר בתאריך')
                ->date('d/m/Y'),
            Columns\TextColumn::make('users')
                ->toggleable()
                ->toggledHiddenByDefault()
                ->formatStateUsing(fn ($state) => $state->you_or_name)
                ->size('xs')
                ->listWithLineBreaks()
                ->limitList(2)
                ->expandableLimitedList()
                ->label('שדכנ/ים'),
            Columns\TextColumn::make('status')
                ->tooltip(fn (Proposal $proposal) => $proposal->reason_status ?? null)
                ->label('סטטוס')
                ->action(fn (Proposal $proposal) => $proposal->userCanAccess() ? static::getAddDiaryAction() : null)
                ->formatStateUsing(fn (Proposal $proposal) => $proposal->userCanAccess() ? $proposal->columnStatus() : null)
                ->html()
                ->sortable(),
            ...static::sideGroupColumns('guy', $innerStudent),
            Columns\TextColumn::make('divider')
                ->label('')
                ->extraAttributes(['class' => 'after:content-[""] after:absolute after:inset-y-0 after:right-0 after:w-1 after:bg-gray-200'])
                ->width(50)
                ->state('|'),
            ...static::sideGroupColumns('girl', $innerStudent),
        ];
    }

    public static function getAddDiaryAction(?string $side = null)
    {
        $label = match ($side) {
            'guy' => 'הוסף תיעוד בחור',
            'girl' => 'הוסף תיעוד בחורה',
            default => 'הוסף תיעוד',
        };

        return Action::make("create-diary-$side")
            ->label($label)
            ->icon('iconsax-bul-additem')
            ->mergeArguments(['side' => $side])
            ->model(Diary::class)
            ->action(fn ($data, $record) => Diaries::createNewDiary($data, $record, $data['side'] ?? null))
//            ->action(function (Form $form) {
//                dd($form->getState());
//            })
            ->hidden(fn (Proposal $proposal) => ! $proposal->userCanAccess())
            ->form(fn ($form, $arguments, $record) => Diaries::getDiaryForm($form, $record, $arguments['side'] ?? null));
    }

    private function searchPerson(string $query): Collection
    {
        $words = explode(' ', $query);
        $columns = ['first_name', 'last_name'];

        $query = Person::query();

        foreach ($words as $word) {
            $query->where(function (Builder $query) use ($columns, $word) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'LIKE', "%{$word}%");
                }
            });
        }

        return $query
            ->take(60)
            ->get()
            ->pluck('full_name', 'id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProposals::route('/'),
            //            'create' => Pages\CreateProposal::route('/create'),
            'edit' => Pages\EditProposal::route('/{record}/edit'),
            'view' => Pages\ViewProposal::route('/{record}'),
            //            'families' => Pages\ManageFamilies::route('/{record}/families'),
            'families' => Pages\Family::route('/{record}/{side}/families'),
            'diaries' => Pages\Diaries::route('/{record}/{side}/diaries'),
            //            'diaries' => Pages\ManageDiaries::route('/{record}/diaries'),
            'contacts' => Pages\ManageContacts::route('/{record}/{side}/contacts'),
            'documents' => Pages\ManageFiles::route('/{record}/documents'),
            //            'overView' => Pages\OverViewProposal::route('/{record}'),

            'schools' => Pages\Schools::route('/{record}/{side}/schools'),
            'assignment-users' => Pages\UserAssignmentManagement::route('/{record}/assignment-users'),
        ];
    }

    //    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        $parameters = $page->getSubNavigationParameters();

        return collect([
            ...Pages\ViewProposal::getNavigationItems($parameters),
            NavigationGroup::make('בחור')
                ->items(static::getNavigationItemBySide($parameters, 'guy')),
            NavigationGroup::make('בחורה')
                ->items(static::getNavigationItemBySide($parameters, 'girl')),

            Pages\UserAssignmentManagement::canAccess($parameters)
                ? NavigationGroup::make('הרשאת גישה')
                    ->items([
                        Pages\UserAssignmentManagement::getNavigationItems($parameters)[0],
                    ])
                : null,
        ],

        )
            ->filter()
            ->toArray();
    }

    public static function getNavigationItemBySide($parameters, $side)
    {
        $pages = [
            Pages\Family::class,
            Pages\Diaries::class,
            Pages\ManageContacts::class,
            Pages\Schools::class,
        ];

        $items = [];

        foreach ($pages as $page) {
            $items[] = $page::getNavigationItems($parameters + ['side' => $side])[0]
                ->isActiveWhen(fn () => request()->side === $side && request()->routeIs($page::getRouteName()));
        }

        return $items;
    }

    public static function sideGroupColumns($side, ?bool $innerStudent = false): array
    {
        if (! in_array($side, ['guy', 'girl'])) {
            return [];
        }

        $label = $side === 'guy' ? 'בחור' : 'בחורה';
        $ucFirst = Str::ucfirst($side);

        return [
            Columns\ViewColumn::make("$side.full_name")
                ->view('filament.resources.proposal-resource.columns.student-name-and-status-column', [
                    'side' => $side,
                    'innerStudent' => $innerStudent,
                ])
                ->extraAttributes(fn (Proposal $proposal) => [
                    'class' => $proposal->{$side}->current_family_id ? 'bg-red-100' : 'bg-green-100',
                ])
                ->label($label)
                ->url('#')
                ->searchable(['first_name', 'last_name']),
            Columns\TextColumn::make("$side.age")
                ->label('גיל'),
            Columns\TextColumn::make("last{$ucFirst}Diary.data.description")
                ->label('יומן אחרון')
                ->badge()
                ->prefix(fn (Proposal $proposal) => $proposal->userCanAccess() ? 'המשך טיפול: ' : null)
                ->color(fn (Proposal $proposal) => match (true) {
                    $proposal->nextDateIsPast($side) => 'danger',
                    $proposal->nextDateIsToday($side) => 'success',
                    now()->addDay()->isSameDay($proposal->{"{$side}_next_time"}) => 'warning',
                    default => 'gray',
                })
                ->icon(fn (Proposal $proposal) => $proposal->userCanAccess() ? 'iconsax-bul-timer-1' : null)
                ->width('150px')
                ->description(fn (Proposal $proposal) => $proposal->userCanAccess() ? (! $proposal->{"last{$ucFirst}Diary"} ? null :
                    $proposal->{"last{$ucFirst}Diary"}->label_type.': '.$proposal->{"last{$ucFirst}Diary"}->data['description'] ?? null) : null)
                ->state(fn (Proposal $proposal) => $proposal->userCanAccess() ? $proposal->getNextDate($side) : null)
                ->wrap(),
            Columns\TextColumn::make("$side.parentsFamily.city.name")
                ->label('עיר'),
            Columns\TextColumn::make("$side.schools.name")
                ->state(fn (Proposal $proposal) => $proposal->{$side}->schools->first()?->name)
                ->label('בית ספר')
                ->description(fn (Proposal $proposal) => $proposal->{$side}->schools->count() > 1
                    ? 'קודם: '.$proposal->{$side}->schools->last()?->name
                    : null),
        ];
    }

    private static function getCloseProposalForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('finished_at')
                ->label('תאריך סגירה')
                ->default(now())
                ->displayFormat('d/m/Y')
                ->native(false)
                ->required(),
            Forms\Components\Textarea::make('reason_status')
                ->label('הערה')
                ->helperText('שתף את התרשמותך מהמהלך, איך היית מדרג את הביצוע קל או קשה וכו\''),
        ]);
    }

    public static function getCloseProposalAction(): Action
    {
        return Action::make('close-proposal')
            ->label('סגור הצעה')
            ->form(fn (Form $form) => static::getCloseProposalForm($form))
            ->modalWidth('sm')
            ->tooltip('סגור הצעה')
            ->modalSubmitActionLabel('סגור')
            ->icon('iconsax-bul-copy-success')
            ->color('success')
            ->iconButton()
            ->hidden(fn (Proposal $proposal) => $proposal->status === Statuses::getClosedProposalStatus())
            ->modalHeading('מזל טוב!!!')
            ->action(function (Proposal $proposal, Action $action, array $data) {
                try {
                    $proposal->close($data);

                    $action->success();
                } catch (\Throwable $e) {

                    $action->failureNotification(fn (Notification $notification) => $notification
                        ->title('סגירת הצעה נכשלה')
                        ->body($e->getMessage() . " - " . $e->getFile() . ':' . $e->getLine())
                    );

                    $action->failure();
                }
            });
    }

    public static function getOpenProposalAction(): Action
    {
        return Action::make('reopen-proposal')
            ->label('פתח הצעה סגורה')
            ->form(fn (Form $form) => $form->schema([
                Proposal::make()->statusField(true, 'status')
                    ->default(fn (Proposal $proposal) => $proposal->lastDiary->data['statuses']['proposal'] ?? null)
                    ->helperText(fn (Proposal $proposal) => ($proposal->lastDiary->data['statuses']['proposal'] ?? null)
                        ? 'הסטטוס ברירת המחדל הינו הסטטוס האחרון שהיה לפני הסגירה'
                        : null
                    )
                    ->required(),
                Forms\Components\Textarea::make('reason_status')
                    ->label('הערה')
                    ->default('נפתח מחדש ע"י '.auth()->user()->name),
            ]))
            ->modalWidth('sm')
            ->modalSubmitActionLabel('ביטול סגירה')
            ->icon('iconsax-bul-refresh')
            ->color('success')
            ->iconButton()
            ->visible(fn (Proposal $proposal) => $proposal->canReopen())
            ->action(fn (Proposal $proposal, array $data) => $proposal->reopen($data['status'], $data['reason_status'] ?? null));
    }

    public static function showHideActions(): array
    {
        return [
            Action::make('hide-proposal-to-list')
                ->visible(fn ($record) => ! $record->hidden_at)
                ->tooltip('הסתר')
                ->icon('heroicon-o-eye-slash')
                ->iconButton()
                ->action(fn ($record) => $record->hide())
                ->color('gray'),
            Action::make('show-proposal-to-list')
                ->visible(fn ($record) => $record->hidden_at)
                ->tooltip('הצג')
                ->icon('heroicon-s-eye')
                ->iconButton()
                ->color('danger')
                ->action(fn ($record) => $record->show()),
        ];
    }

    public static function resolveRecordRouteBinding(int | string $key): ?Model
    {
        return app(static::getModel())
            ->resolveRouteBindingQuery(static::getEloquentQuery(), $key, static::getRecordRouteKeyName())
            ->withoutGlobalScope('withoutHidden')
            ->first();
    }
}
