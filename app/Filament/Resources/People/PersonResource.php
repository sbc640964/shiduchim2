<?php

namespace App\Filament\Resources\People;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Repeater;
use Filament\Support\Enums\Width;
use DB;
use Arr;
use Filament\Forms\Components\Placeholder;
use Filament\Support\Enums\Size;
use Filament\Forms\Components\Textarea;
use App\Models\Family;
use App\Models\Matchmaker;
use App\Models\Person;
use App\Models\Proposal;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class PersonResource extends Resource
{
    protected static ?string $model = Person::class;

    protected static ?string $slug = 'people';

    protected static string | \BackedEnum | null $navigationIcon = 'iconsax-bul-house';

    protected static ?string $navigationLabel = 'אנשים';

    protected static ?string $label = 'אדם';

    protected static ?string $pluralLabel = 'אנשים';

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)
                ->schema([
                    Grid::make(1)
                        ->label('פרטים אישיים')
                        ->columnSpan(1)
                        ->schema([
                            Section::make('גור')
                                ->schema([
                                    Person::externalCodeColumn()
                                ]),
                            Section::make('כללי')
                                ->columns(1)
                                ->columnSpan(1)
                                ->schema([
//                                    Forms\Components\ToggleButtons::make('gender')
//                                        ->options([
//                                            'B' => 'בן',
//                                            'G' => 'בת',
//                                        ])
//                                        ->disabledOn('edit')
//                                        ->default('B')
//                                        ->live()
//                                        ->grouped()
//                                        ->label('מין')
//                                        ->required(),
                                    Family::filamentSelect('parents_family_id')
                                        ->label('משפחת הורים'),

                                    TextInput::make('first_name')
                                        ->label('שם פרטי')
                                        ->required(),

                                    TextInput::make('last_name')
                                        ->label('שם משפחה')
                                        ->required(),

                                    TextInput::make('address')
                                        ->label('כתובת'),

                                    Select::make('city_id')
                                        ->relationship('city', 'name')
                                        ->label('עיר')
                                        ->searchable()
                                        ->preload(),

                                    Group::make([
                                        Fieldset::make('פרטי אשה')
                                            ->columns(1)
                                            ->schema([

                                                Family::filamentSelect('spouse_parents_family_id')
                                                    ->label('משפחת הורים')
                                                    ->relationship('family'),

                                                TextInput::make('wife_first_name')
                                                    ->label('שם פרטי')
                                                    ->required(),
                                            ])
                                    ])
                                        ->visibleOn('create')

                                    //                                    Forms\Components\TextInput::make('phone_number')
                                    //                                        ->label('טלפון')
                                    //                                        ->required(),
                                ]),
                        ]),
                    Grid::make(1)
                        ->columnSpan(2)
                        ->schema([
                            static::familiesCard()->columnSpanFull()->visibleOn('edit'),
                            static::phonesCard()->columnSpanFull(),
                        ]),
                    Section::make('פעולות')
                        ->visibleOn('edit')
                        ->columnSpanFull()
                        ->schema([
                            Actions::make([
                                Action::make('data-raw')
                                    ->action(fn () => null)
                                    ->schema(function (Schema $schema, $record) {
                                        return $schema
                                            ->record($record)
                                            ->components([
                                                KeyValueEntry::make('data_raw'),
                                            ]);
                                    }),
                            ]),
                        ])
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns(static::tableColumns())
            ->modifyQueryUsing(function ($query) {
                $query
                    ->with([
                        'family',
                        'spouse',
                        'families' => fn ($query) => $query->withCount('children')
                    ]);
            })
            ->filters([
                TernaryFilter::make('without_families')
                    ->label('סינון מצב רשומה')
                    ->default(true)
                    ->selectablePlaceholder(false)
                    ->trueLabel('רק נשואים בעבר או בהווה')
                    ->falseLabel('הצג גם מי שלא היה נשוי')
                    ->visible(fn () => auth()->user()->can('management_people_without_families'))
                    ->queries(
                        true: fn ($query) => $query->whereHas('families')
                            ->where(fn ($query) => $query
                                ->whereRelation('family', fn (Builder $query) => $query->where('status', '!=', 'married'))
                                ->orWhere('gender', 'B')
                            ),
                        false: fn ($query) => $query
                    ),
                SelectFilter::make('city')
                    ->relationship('city', 'name')
                    ->label('עיר')
                    ->placeholder('בחר עיר')
                    ->preload()
                    ->searchable(),
                SelectFilter::make('status_family')
                    ->options([
                        'single' => 'רווק/ה',
                        'married' => 'נשוי/ה',
                        'divorced' => 'גרוש/ה',
                        'widower' => 'אלמן/ה',
                    ])
                    ->label('מצב משפחתי')
                    ->native(false)
                    ->modifyQueryUsing(function ($query, $state) {
                        if ($state['value'] === 'died') {
                            $query->whereNotNull('died_at');
                        } else {
                            $query->whereNull('died_at');

                            if ($state['value'] === 'single') {
                                $query->whereDoesntHave('family');
                            } elseif ($state['value']) {
                                $query->whereRelation('family', 'status', $state['value']);
                            }
                        }
                    })
                    ->placeholder('הכל'),
                SelectFilter::make('gender')
                    ->options([
                        'B' => 'בן',
                        'G' => 'בת',
                    ])
                    ->label('בן/בת')
                    ->native(false)
                    ->placeholder('הכל'),
            ])
            ->paginationPageOptions([10, 25, 50, 100, 250, 500, 1000])
            ->defaultSort(
                fn (Builder $query) => $query
                    ->orderBy('last_name')
                    ->orderBy('first_name')
            )
            ->headerActions([
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //            RelativesNuclearRelationManager::class,
            //            RelativesFatherRelationManager::class,
            //            RelativesMatherRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPeople::route('/'),
            'create' => Pages\CreatePerson::route('/create'),
            'edit' => Pages\EditPerson::route('/{record}/edit'),
            'family' => Pages\Family::route('/{record}/family'),
            'proposals' => Pages\Proposals::route('/{record}/proposals'),
            'cards' => Pages\CreditCards::route('/{record}/cards'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            EditPerson::class,
            Pages\Family::class,
            Proposals::class,
            CreditCards::class,
        ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }

    public static function tableColumns(): array
    {
        return [
            TextColumn::make('external_code')
                ->label('קוד איחוד')
                ->searchable()
                ->toggleable()
                ->toggledHiddenByDefault()
                ->sortable(),

            Person::nameColumn()->searchable(['first_name', 'last_name'], isIndividual: true),

            TextColumn::make('phones.number')
                ->label('טלפון נייד')
                ->searchable(),

            TextColumn::make('family.phones.number')
                ->label('טלפון בבית')
                ->searchable(),

            ...Person::baseColumns(callback: [
                'father' => fn (TextColumn $col) => $col->searchable(isIndividual: true),
                'father_in_law' => fn (TextColumn $col) => $col->searchable(['first_name', 'last_name'], isIndividual: true),
            ]),

            Person::childrenColumn(),

            TextColumn::make('family.status')
                ->badge()
                ->sortable()
                ->color(fn ($state) => match ($state) {
                    'single' => 'danger',
                    'married' => 'success',
                    'divorced' => 'info',
                    'widower' => 'info',
                    default => 'gray',
                })
                ->formatStateUsing(fn ($state, Person $record) => match ($state) {
                    'married' => $record->gender === 'G' ? 'נשואה' : 'נשוי',
                    'divorced' => $record->gender === 'G' ? 'גרושה' : 'גרוש',
                    'widower' => $record->gender === 'G' ? 'אלמנה' : 'אלמן',
                    default => $record->gender === 'G' ? 'רווקה' : 'רווק',
                })
                ->label('מצב משפחתי'),
        ];
    }

    private static function familiesCard()
    {
        return Group::make([
            Repeater::make('families')
                ->hintAction(Action::make('add_family')
                    ->label('נישואים שניים')
                    ->visible(fn ($record) => ! $record->isMarried())
                    ->schema(function (Schema $schema) {
                        return $schema->components([
                            static::spouseSelect(),
                            DatePicker::make('engagement_at')
                                ->label('תאריך אירוסין')
                                ->required(),

                            TextInput::make('address')
                                ->label('כתובת'),

                            Select::make('city_id')
                                ->relationship('city', 'name')
                                ->label('עיר')
                                ->searchable()
                                ->preload(),

                            Select::make('matchmaker_id')
                                ->relationship('matchmaker.person', modifyQueryUsing: fn ($query) => $query->whereIn('id', Matchmaker::pluck('person_id')->toArray()))
                                ->label('שדכן')
                                ->getOptionLabelFromRecordUsing(fn (Person $record) => $record->select_option_html)
                                ->allowHtml()
                                ->searchable(['first_name', 'last_name']),
                        ]);
                    })
                    ->visible(fn ($record) => ! $record->isMarried())
                    ->modalWidth(Width::ExtraSmall)
                    ->action(function ($record, array $data, Action $action) {

                        $inserted = DB::transaction(function () use ($record, $data) {
                            $spouse = Person::find($data['spouse_id']);

                            $family = Family::create(array_merge(Arr::except($data, 'spouse_id'), [
                                'status' => 'married',
                                'name' => $record->gender === 'B' ? $record->last_name : $spouse->last_name,
                            ]));

                            $family->people()->attach([$record->id, $data['spouse_id']]);

                            return Person::whereIn('id', [$record->id, $data['spouse_id']])
                                ->update(['current_family_id' => $family->id]);
                        });

                        if ($inserted)
                            $action->success();
                    })
                )
                ->relationship('families', modifyQueryUsing: fn ($query) => $query->with(['people.father', 'people.fatherInLaw']))
                ->label('משפחות')
                ->hiddenLabel()
                ->columns(4)
                ->addActionLabel('הוסף משפחה')
                ->addable(false)
                ->deletable(false)
                ->hiddenOn('create')
                ->schema(fn (Person $person) => [
                        Placeholder::make('wife')
                            ->content(fn (Family $record) => new HtmlString($record->people->firstWhere('id', '!=', $person->id)->select_option_html))
                            ->label('בן/בת זוג'),
                        Placeholder::make('marriage_date')
                            ->label('תאריך אירוסין')
                            ->content(fn (Family $record) => $record->engagement_at?->hebcal()->hebrewDate(false, true) ?? '-'),
                        Placeholder::make('status')
                            ->label('מצב משפחתי')
                            ->content(fn (Family $record) => $record->status_label),
                        Fieldset::make('פעולות')
                            ->columns(1)
                            ->columnSpan(1)
                            ->extraAttributes(['class' => '!p-2'])
                            ->schema([
                            Actions::make([
                                Action::make('edit')
                                    ->label('עריכה')
                                    ->icon('heroicon-o-pencil')
                                    ->size(Size::ExtraSmall)
                                    ->url(fn (Family $record) =>
                                        PersonResource::getUrl('edit', ['record' => $record->people->firstWhere('gender', $person->gender === 'B' ? 'G' : 'B')->id]),
                                        true
                                    ),
                                Action::make('cancel_close_proposal')
                                    ->label('בטל סגירת שידוך')
                                    ->icon('heroicon-o-x-mark')
                                    ->size(Size::ExtraSmall)
                                    ->disabled(fn (Family $record) => !$record->proposal?->canReopen())
                                    ->schema(fn (Schema $schema, Family $record) => $schema->components([
                                        Proposal::make()->statusField(true, 'status')
                                            ->default($record->proposal->lastDiary->data['statuses']['proposal'] ?? null)
                                            ->helperText($record->proposal->lastDiary->data['statuses']['proposal'] ?? null
                                                ? 'הסטטוס ברירת המחדל הינו הסטטוס האחרון שהיה לפני הסגירה'
                                                : null
                                            )
                                            ->required(),
                                        Textarea::make('reason_status')
                                            ->label('הערה')
                                            ->default('נפתח מחדש ע"י '.auth()->user()->name),
                                    ]))
                                    ->modalWidth('sm')
                                    ->modalSubmitActionLabel('ביטול סגירה')
                                    ->action(fn (Family $record, array $data) => $record->proposal->reopen($data['status'], $data['reason_status'] ?? null)),
                                Action::make('divorce')
                                    ->label('גירושין')
                                    ->disabled(fn ($record) =>
                                        !auth()->user()->can('update_divorce')
                                        || $record->status !== 'married'
                                    )
                                    ->color(fn ($record) =>
                                        !auth()->user()->can('update_divorce')
                                        || $record->status !== 'married'
                                            ? Color::Blue
                                            : Color::Red
                                    )
                                    ->size(Size::ExtraSmall)
                                    ->requiresConfirmation()
                                    ->successNotification(function (Notification $notification) use ($person) {
                                        return $notification
                                            ->actions([
                                                Action::make('cancel')
                                                    ->label('ביטול')
                                                    ->action(function () use ($person){
                                                        $person->rollbackDivorces();
                                                    })
                                            ])
                                            ->title('הגירושין נרשם בהצלחה')
                                            ->body('הגירושין נרשם בהצלחה ונשמר במערכת');
                                    })
                                    ->label('עדכון גירושין')
                                    ->outlined()
                                    ->icon('iconsax-bul-arrow-square')
                                    ->action(function ($record, Action $action, $livewire): void {
                                        if( $record && $record->divorce()) {
                                            $action->success();
                                            $livewire->refreshFormDataB(['father_in_law_id', 'spouse_id', 'families']);
                                        }
                                    }),
                                Action::make('death')
                                    ->label('עדכון פטירה')
                                    ->schema(function (Schema $schema) {
                                        return $schema->components([
                                            DatePicker::make('died_at')
                                                ->label('תאריך פטירה')
                                                ->helperText('ניתן להזין תאריך פטירה, במקרה ואינך יודע השאר ריק בבקשה!'),
                                        ]);
                                    })
                                    ->disabled(fn ($record) =>
                                        ! $record->people->firstWhere('gender', $person->gender === 'B' ? 'G' : 'B')->isAlive()
                                        || ! auth()->user()->can('update_death')
                                    )
                                    ->color('danger')
                                    ->outlined()
                                    ->size(Size::ExtraSmall)
                                    ->action(function (array $data, $record) use ($person) {
                                        $data['died_at'] = $data['died_at'] . '1970-01-02 00:00:00';
                                        $record->people->firstWhere('gender', $person->gender === 'B' ? 'G' : 'B')->update($data);
                                    })
                                    ->requiresConfirmation()
                            ])
                        ]),
//                        Forms\Components\Select::make('wife')
//                            ->relationship(
//                                'wife',
////                            modifyQueryUsing: fn ($query) => $query->with('father', 'fatherInLaw')
//                            )
//                            ->label('אישה')
////                        ->getOptionLabelFromRecordUsing(fn (Person $record) => $record->select_option_html)
//                            ->allowHtml()
//                            ->searchable(['first_name', 'last_name'])

                ]),
        ]);
    }

    private static function phonesCard()
    {
        return Section::make('טלפונים')
            ->columnSpan(2)
            ->columns(2)
            ->schema([
                Group::make(fn (?Person $record = null) => [
                    Select::make('phone_default_id')
                        ->label('טלפון ברירת מחדל')
                        ->hiddenOn('create')
                        ->options((! $record) ? [] : $record->phones()
                            ->when($record->family, fn ($query) => $query->unionAll($record->family->phones()->select('number', 'id')))
                            ->pluck('number', 'id')
                            ->toArray()
                        )
                        ->searchable(),
                ])->columns(3)->columnSpanFull(),


                Repeater::make('phones')
                    ->relationship('phones')
                    ->addActionLabel('הוסף טלפון')
                    ->deleteAction(fn (Action $action) => $action
                        ->icon('heroicon-o-trash')
                    )
                    ->label('טלפונים ישירים')
                    ->simple(
                        TextInput::make('number')
                            ->label('טלפון')
                            ->unique('phones', 'number', ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'טלפון זה כבר קיים במערכת',
                            ])
                    ),

                Group::make([
                    Repeater::make('phones')
                        ->relationship('phones')
                        ->label('טלפונים בבית')
                        ->helperText('שים לב: טלפונים אלו מתעדכנים במשפחה שייכים לכלל הקרובים (אישה/ ילדים)')
                        ->addActionLabel('הוסף טלפון')
                        ->deleteAction(fn (Action $action) => $action
                            ->icon('heroicon-o-trash')
                        )
                        ->simple(
                            TextInput::make('number')
                                ->label('טלפון')
                                ->unique('phones', 'number', ignoreRecord: true)
                                ->validationMessages([
                                    'unique' => 'טלפון זה כבר קיים במערכת',
                                ])
                        ),
                ])
                    ->hiddenOn('create')
                    ->relationship('family'),

            ]);
    }

    private static function parentsAndSpouseCard()
    {
        return Section::make('משפחה')
            ->columns()
            ->schema([
                Select::make('father_id')
                    ->relationship(
                        'father',
                        modifyQueryUsing: fn ($query) => $query->with('father', 'fatherInLaw')
                    )
                    ->label('אב')
                    ->getOptionLabelFromRecordUsing(fn (Person $record) => $record->select_option_html)
                    ->allowHtml()
                    ->searchable(['first_name', 'last_name']),
            ]);
    }

    private static function spouseSelect()
    {
        return Select::make('spouse_id')
            ->relationship('spouse')
            ->label('בן/בת זוג')
            ->getOptionLabelFromRecordUsing(fn (Person $record) => $record->select_option_html)
            ->getSearchResultsUsing(fn ($search, $record) => Person::query()
                ->searchName($search, $record->gender === 'B' ? 'G' : 'B')
                ->single()
                ->with('father')
                ->get()
                ->pluck('select_option_html', 'id')
            )
            ->searchable()
            ->required()
            ->allowHtml();
    }
}
