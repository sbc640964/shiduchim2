<?php

namespace App\Filament\Resources\Students;

use App\Filament\Tables\StudentsTable;
use App\Models\User;
use Closure;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Forms\Components\TableSelect;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Schema;
use App\Filament\Resources\Students\Pages\Subscription;
use App\Filament\Resources\Students\Pages\ViewStudent;
use Filament\Actions\Action;
use App\Filament\Resources\Students\Pages\AddProposal;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\TextSize;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\Pages\ManageProposals;
use App\Filament\Resources\Students\Pages\Family;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Enums\Width;
use Filament\Tables\Filters\Filter;
use Filament\Schemas\Components\Group;
use App\Filament\Clusters\Settings\Resources\Matchmakers\MatchmakerResource;
use App\Filament\Resources\Students\Forms\CreateForm;
use App\Models\City;
use App\Models\Form as FormModel;
use App\Models\Person;
use App\Models\School;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\SpatieTagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Kirschbaum\Commentions\Filament\Actions\CommentsAction;
use Kirschbaum\Commentions\Filament\Infolists\Components\CommentsEntry;
use Spatie\Tags\Tag;

class StudentResource extends Resource
{
    use ExposesTableToWidgets;

    protected static ?string $model = Person::class;

    protected static ?string $recordRouteKeyName = 'people.id';

    protected static ?string $slug = 'students';

    protected static ?string $label = 'תלמיד';

    protected static ?string $pluralLabel = 'תלמידים';

    protected static string | \BackedEnum | null $navigationIcon = 'iconsax-bul-personalcard';

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Schema $schema): Schema
    {
        return (new CreateForm)($schema);
    }

    static public function withRelationship(): array
    {
        return [
            'father.father',
            'father.mother',
            'mother.father',
            'mother.mother',
            'schools',
            'parentsFamily.city',
            'father.school',
            'lastSubscription.matchmaker',
            'city',
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return static::modifyTableQuery(parent::getEloquentQuery());
    }

    public static function table(Table $table): Table
    {
        $extraColumns = [];

        return $table
            ->when(method_exists($table->getLivewire(), 'getExtraColumns'), function (Table $table) use (&$extraColumns) {
                $extraColumns = $table->getLivewire()->getExtraColumns();
            })
            ->modifyQueryUsing(fn ($query) => $query
                ->with(static::withRelationship())
                ->where(function (Builder $query) {
                    $query->whereNull('families.id')
                        ->orWhere('families.status', '!=', 'married');
                })
            )
            ->recordUrl(fn (Model $record, $livewire) =>
                $livewire->activeTab === 'subscriptions'
                    ? Subscription::getUrl(['record' => $record])
                    : ViewStudent::getUrl(['record' => $record])
            )
            ->columns(static::getTableColumns($extraColumns))
            ->paginationPageOptions([5, 10, 25, 50, 100, 250])
            ->recordActions([
               Person::commentsAction(),
                Action::make('go-to-search-proposal')
                    ->tooltip('לחיפוש הצעה')
                    ->label('חיפוש הצעה')
                    ->iconButton()
                    ->url(fn (Person $record) => AddProposal::getUrl(['record' => $record]))
                    ->icon('iconsax-bul-user-search'),
                ...FormModel::getActions('students'),
                Action::make('marriage')
                    ->label('נישואין')
                    ->iconButton()
                    ->icon('iconsax-bul-crown')
                    ->modalWidth(Width::TwoExtraLarge)
                    ->schema(fn (Schema $schema) => $schema->components([
                        TableSelect::make('with')
                            ->label('עם')
                            ->hiddenLabel()
                            ->tableConfiguration(StudentsTable::class)
                            ->tableArguments(fn (Person $record) => [
                                'gender' => $record->gender === 'B' ? 'G' : 'B'
                            ])
//                            ->relationshipName($this->getRelationshipName())
//                            ->multiple()
//                            ->maxItems($this->getMaxItems())
//                            ->tableArguments($this->getTableArguments())
                        ,
//                        ModalTableSelect::make('with')
////                            ->relationship('anywhereWith', 'name')
////                            ->model(Person::class)
////                            ->getSearchResultsUsing(fn (string $search, Person $person) => Person::searchName($search, $person->gender === 'B' ? 'G' : 'B')
////                                ->single()
////                                ->with('father')
////                                ->limit(50)
////                                ->get()
////                                ->pluck('select_option_html', 'id')
////                            )
//                            ->tableConfiguration(StudentsTable::class)
//                            ->getOptionLabelFromRecordUsing(fn (Person $person) => $person->full_name)
////                            ->label('עם')
////                            ->required()
//                        ,

//                        Select::make('with')
//                            ->searchable()
//                            ->allowHtml()
//                            ->getSearchResultsUsing(fn (string $search, Person $person) => Person::searchName($search, $person->gender === 'B' ? 'G' : 'B')
//                                ->single()
//                                ->with('father')
//                                ->limit(50)
//                                ->get()
//                                ->pluck('select_option_html', 'id')
//                            )
//                            ->label('עם')
//                            ->required(),

                        Select::make('matchmaker')
                            ->searchable()
                            ->allowHtml()
                            ->getOptionLabelUsing(fn (Person $person) => $person->select_option_html)
                            ->getSearchResultsUsing(fn (string $search) => Person::searchName($search)
                                ->whereRelation('matchmaker', 'active', true)
                                ->with(['father', 'matchmaker'])
                                ->limit(50)
                                ->get()
                                ->pluck('select_option_html', 'matchmaker.id')
                            )
                            ->exists('matchmakers', 'id')
                            ->createOptionAction(fn (Action $action) => $action
                                ->modalWidth('sm')
                            )
                            ->createOptionForm(fn (Schema $form) => MatchmakerResource::form($schema))
                            ->label('שדכן'),

                        DatePicker::make('date')
                            ->label('תאריך')
                            ->default(now())
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->required(),
                    ]))
                    ->action(function (array $data, Person $person){

                        dump($data);

                        $person->marriedExternal(
                            $data['with'],
                            Carbon::make($data['date']),
                            $data['matchmaker']
                        );
                    }),
            ])
            ->filters(static::filters())
            ->filtersLayout(FiltersLayout::AboveContent)
            ->defaultSort(fn ($query) => $query
                ->orderBy('last_name')
                ->orderBy('first_name')
            );
    }

    static public function modifyTableQuery(Builder $query)
    {
        return $query
            ->whereNotNull('external_code_students')
            ->leftJoin('family_person', 'people.id', '=', 'family_person.person_id')
            ->leftJoin('families', 'family_person.family_id', '=', 'families.id')
            ->select('people.*');
    }

    public static function infolist(Schema $schema): Schema
    {
        $classPage = $schema->getLivewire()::class;

        return parent::infolist($schema)
            ->columns(1)
            ->components([
                TextEntry::make('proposals_exists')
                    ->visible(fn (Person $record) => $classPage === AddProposal::class && $record->proposals_exists === true)
                    ->hiddenLabel()
                    ->size(TextSize::Large)
                    ->formatStateUsing(fn ($state) => $state ? 'יש הצעה' : null)
                    ->badge()
                    ->color(fn ($state) => $state ? Color::Green : null),

                Actions::make([
                    Action::make('add-proposal')
                        ->label('הוסף הצעה')
                        ->visible(function ($livewire, Person $record) {
                            return $livewire::class === AddProposal::class
                                && $record->proposals_exists === false;
                        })
                        ->action(function ($livewire, Person $record) {
                            $livewire->addProposal($record);
                        }),
                ]),
                Grid::make(2)->schema([
                    Grid::make(1)
                        ->schema([
                            TextEntry::make('full_name')
                                ->label('שם מלא')
                                ->weight(FontWeight::Bold)
                                ->size(TextSize::Large),

                            TextEntry::make('father_name')
                                ->label('שם האב')
                                ->weight(FontWeight::Bold)
                                ->size(TextSize::Large),

                            TextEntry::make('mother_name')
                                ->label('שם האם')
                                ->weight(FontWeight::Bold)
                                ->size(TextSize::Large),
                        ]),

                    Person::commentsEntry(),
                ]),
            ]); // TODO: Change the autogenerated stub
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudents::route('/'),
            'create' => CreateStudent::route('/create'),
            'edit' => EditStudent::route('/{record}/edit'),
            'view' => ViewStudent::route('/{record}'),
            //            'proposals_guy' => Pages\ManageProposalsGuy::route('/{record}/proposals'),
            'proposals' => ManageProposals::route('/{record}/proposals'),
            'family' => Family::route('/{record}/family'),
            'add_proposal' => AddProposal::route('/{record}/add_proposal'),
            'subscription' => Subscription::route('/{record}/subscription'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewStudent::class,
            ManageProposals::class,
            EditStudent::class,
            Family::class,
            Subscription::class,
            AddProposal::class,
        ]);
    }

    private static function filters()
    {
        return [
            Filter::make('table_columns')
                ->columnSpanFull()
                ->columns(1)
                ->query(function (Builder $query, array $data) {
                    return $query->filterStudent($data);
                })
                ->schema([
                    Group::make([
                        TextInput::make('external_code_students')
                            ->placeholder('מספר תלמיד')
                            ->numeric()
                            ->label('מספר תלמיד'),
                        TextInput::make('last_name')
                            ->placeholder('שם משפחה')
                            ->label('שם משפחה'),
                        TextInput::make('first_name')
                            ->placeholder('שם פרטי')
                            ->label('שם פרטי'),
                        TextInput::make('father_first_name')
                            ->placeholder('שם האב')
                            ->label('שם האב'),
                        TextInput::make('father_mother_name')
                            ->placeholder('שם החותן')
                            ->label('שם החותן'),
                        Select::make('school')
                            ->label('מוסד')
                            ->options(School::pluck('name', 'id')->where('type', 0)->toArray())
                            ->searchable(),
                        Select::make('synagogue')
                            ->label('בית כנסת')
                            ->options(School::where('type', 10)->pluck('name', 'id')->toArray())
                            ->searchable(),
                        Select::make('city')
                            ->label('עיר')
                            ->multiple()
                            ->options(City::orderBy('name')->pluck('name', 'id')),
                        FusedGroup::make([
                            Select::make('tags')
                                ->multiple()
                                ->hidden(fn ($get) => $get('tags_operator') === 'like')
                                ->options(Tag::whereType(Person::studentTagsKey())
                                    ->take(50)
                                    ->get()
                                    ->mapWithKeys(fn (Tag $tag) => [$tag->name => $tag->name]))
                                ->getSearchResultsUsing(function ($search) {
                                    return Tag::whereType(Person::studentTagsKey())
                                        ->where('name->he', 'like', "%$search%")
                                        ->take(50)
                                        ->get()
                                        ->mapWithKeys(fn (Tag $tag) => [$tag->name => $tag->name]);
                                })
                                ->columnSpan(3)
                                ->searchable(),

                            TextInput::make('tags')
                                ->hidden(fn ($get) => $get('tags_operator') !== 'like')
                                ->placeholder('חפש...')
                                ->columnSpan(3),

                            Select::make('tags_operator')
                                ->default('and')
                                ->selectablePlaceholder(false)
                                ->native(false)
                                ->options([
                                    'and' => 'גם',
                                    'or' => 'או',
                                    'like' => 'כולל',
                                ])
                                ->default('and'),
                        ])
                            ->columns(4)
                            ->label('תיוגים'),

                        FusedGroup::make([
                            ToggleButtons::make('gender')
                                ->grouped()
                                ->default('all')
                                ->options([
                                    'all' => 'הכל',
                                    'B' => 'בן',
                                    'G' => 'בת',
                                ]),
                            TextInput::make('age')
                                ->placeholder('גיל')
                                ->numeric(),
                            TextInput::make('class')
                                ->placeholder('כיתה'),
                        ])
                            ->columns(3)
                            ->label('מין / גיל / כתה')

                    ])->columns(6),
                ]),
        ];
    }

    public static function getTableColumns(?array $extraColumns = []): array
    {
        return [
            ...$extraColumns,
            TextColumn::make('external_code_students')
                ->label('מספר')
                ->toggleable()
                ->toggledHiddenByDefault()
                ->searchable()
                ->sortable(),
            TextColumn::make('last_name')
                ->label('שם משפחה')
                ->weight('bold')
                ->searchable()
                ->sortable(),
            TextColumn::make('first_name')
                ->label('שם פרטי')
                ->weight('bold')
                ->searchable()
                ->sortable(),
            TextColumn::make('current_subscription_matchmaker')
                ->label('שדכן מטפל')
                ->badge(),
            TextColumn::make('city')
                ->state(fn (Person $person) => $person->city?->name ?? $person->parentsFamily?->city?->name)
                ->label('עיר')
                ->sortable(),

            TextColumn::make('father.first_name')
                ->description(fn (Person $person) => $person->father?->parents_info)
                ->label('שם האב')
                ->searchable()
                ->sortable(),

            TextColumn::make('mother.first_name')
                ->description(fn (Person $person) => $person->mother?->parents_info)
                ->label('שם האם')
                ->searchable()
                ->sortable(),

            TextColumn::make('schools.name')
                ->label('בית ספר')
                ->searchable(),

            TextColumn::make('data_raw.class')
                ->label('כיתה')
                ->sortable(),

            TextColumn::make('father.schools.name')
                ->state(fn (Person $person) => $person->father?->schools?->last()?->name)
                ->label('בית כנסת')
                ->description(fn (Person $person) => $person->parentsFamily?->city?->name)
                ->searchable()
                ->sortable(),

            SpatieTagsColumn::make('tags')
                ->type(Person::studentTagsKey())
                ->label('תיוגים')
                ->toggleable()
                ->toggledHiddenByDefault(),

            TextColumn::make('born_at')
                ->formatStateUsing(fn (?Carbon $state) => $state ? $state->hebcal()->hebrewDate(withQuotes: true) : null)
                ->label('תאריך לידה')
                ->sortable(),

            //            TextColumn::make('prevSchool.name')
            //                ->label('בית ספר קודם')
            //                ->searchable()
            //                ->sortable(),

            TextColumn::make('gender')
                ->label('מין')
                ->formatStateUsing(fn (string $state) => match ($state) {
                    'B' => 'בן',
                    'G' => 'בת',
                    default => '?',
                })
                ->suffix(fn (Person $person) => ' '.$person->age)
                ->badge()
                ->color(fn (string $state) => match ($state) {
                    'B' => Color::Blue,
                    'G' => Color::Pink,
                    default => Color::Gray,
                })
                ->sortable(),
        ];
    }
}
