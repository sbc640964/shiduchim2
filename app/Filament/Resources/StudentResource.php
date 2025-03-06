<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Settings\Resources\MatchmakerResource;
use App\Filament\Resources\StudentResource\Forms\CreateForm;
use App\Filament\Resources\StudentResource\Pages;
use App\Models\City;
use App\Models\Form as FormModel;
use App\Models\Person;
use App\Models\School;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\SpatieTagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Guava\FilamentClusters\Forms\Cluster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\Tag;

class StudentResource extends Resource
{
    use ExposesTableToWidgets;

    protected static ?string $model = Person::class;

    protected static ?string $slug = 'students';

    protected static ?string $label = 'תלמיד';

    protected static ?string $pluralLabel = 'תלמידים';

    protected static ?string $navigationIcon = 'iconsax-bul-personalcard';

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Form $form): Form
    {
        return (new CreateForm)($form);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'father.father',
            'father.mother',
            'mother.father',
            'mother.mother',
            'schools',
            'parentsFamily.city',
            'father.school',
            'lastSubscription.matchmaker',
        ]);
    }

    public static function table(Table $table): Table
    {
        $extraColumns = [];

        return $table
            ->when(method_exists($table->getLivewire(), 'getExtraColumns'), function (Table $table) use (&$extraColumns) {
                $extraColumns = $table->getLivewire()->getExtraColumns();
            })
            ->recordUrl(fn (Model $record, $livewire) =>
                $livewire->activeTab === 'subscriptions'
                    ? Pages\Subscription::getUrl(['record' => $record])
                    : Pages\ViewStudent::getUrl(['record' => $record])
            )
            ->columns([
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

            TextColumn::make('father.schools')
                ->state(fn (Person $person) => $person->father?->schools?->last()?->name)
                ->label('בית כנסת')
                ->searchable(['name'])
                ->description(fn (Person $person) => $person->parentsFamily?->city?->name)
                ->sortable(),

            SpatieTagsColumn::make('tags')
                ->type(Person::studentTagsKey())
                ->label('תיוגים')
                ->toggleable()
                ->toggledHiddenByDefault()
                ->searchable(['name']),

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
                ->searchable()
                ->sortable(),
        ])
            ->paginationPageOptions([5, 10, 25, 50, 100, 250])
            ->actions([
                Action::make('go-to-search-proposal')
                    ->tooltip('לחיפוש הצעה')
                    ->label('חיפוש הצעה')
                    ->iconButton()
                    ->url(fn (Person $record) => Pages\AddProposal::getUrl(['record' => $record]))
                    ->icon('iconsax-bul-user-search'),
                ...FormModel::getActions('students'),
                Action::make('marriage')
                    ->label('נישואין')
                    ->iconButton()
                    ->icon('iconsax-bul-crown')
                    ->modalWidth('sm')
                    ->form(fn (Form $form) => $form->schema([
                        Select::make('with')
                            ->searchable()
                            ->allowHtml()
                            ->getSearchResultsUsing(fn (string $search, Person $person) => Person::searchName($search, $person->gender === 'B' ? 'G' : 'B')
                                ->single()
                                ->with('father')
                                ->limit(50)
                                ->get()
                                ->pluck('select_option_html', 'id')
                            )
                            ->label('עם')
                            ->required(),

                        Select::make('matchmaker')
                            ->searchable()
                            ->allowHtml()
                            ->getSearchResultsUsing(fn (string $search) => Person::searchName($search)
                                ->whereRelation('matchmaker', 'active', true)
                                ->with(['father', 'matchmaker'])
                                ->limit(50)
                                ->get()
                                ->pluck('select_option_html', 'matchmaker.id')
                            )
                            ->exists('matchmakers', 'id')
                            ->createOptionAction(fn (\Filament\Forms\Components\Actions\Action $action) => $action
                                ->modalWidth('sm')
                            )
                            ->createOptionForm(fn (Form $form) => MatchmakerResource::form($form))
                            ->label('שדכן'),

                        DatePicker::make('date')
                            ->label('תאריך')
                            ->default(now())
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->required(),
                    ]))
                    ->action(fn (array $data, Person $person) => $person->marriedExternal(
                        $data['with'],
                        Carbon::make($data['date']),
                        $data['matchmaker']
                    )),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $query
                    ->whereNotNull('external_code_students')
                    ->leftJoin('family_person', 'people.id', '=', 'family_person.person_id')
                    ->leftJoin('families', 'family_person.family_id', '=', 'families.id')
                    ->select('people.*')
                    ->where(function (Builder $query) {
                        $query->whereNull('families.id')
                            ->orWhere('families.status', '!=', 'married');
                    });
            })
            ->filters(static::filters())
            ->filtersLayout(FiltersLayout::AboveContent)
            ->defaultSort(fn ($query) => $query
                ->orderBy('last_name')
                ->orderBy('first_name')
            );
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        $classPage = $infolist->getLivewire()::class;

        return parent::infolist($infolist)
            ->schema([
                Components\TextEntry::make('proposals_exists')
                    ->visible(fn (Person $record) => $classPage === Pages\AddProposal::class && $record->proposals_exists === true)
                    ->hiddenLabel()
                    ->size(Components\TextEntry\TextEntrySize::Large)
                    ->formatStateUsing(fn ($state) => $state ? 'יש הצעה' : null)
                    ->badge()
                    ->color(fn ($state) => $state ? Color::Green : null),

                Components\Actions::make([
                    Components\Actions\Action::make('add-proposal')
                        ->label('הוסף הצעה')
                        ->visible(function ($livewire, Person $record) {
                            return $livewire::class === Pages\AddProposal::class
                                && $record->proposals_exists === false;
                        })
                        ->action(function ($livewire, Person $record) {
                            $livewire->addProposal($record);
                        }),
                ]),
                Components\Grid::make(2)->schema([

                    Components\Grid::make(1)
                        ->schema([
                            Components\TextEntry::make('full_name')
                                ->label('שם מלא')
                                ->weight(FontWeight::Bold)
                                ->size(Components\TextEntry\TextEntrySize::Large),

                            Components\TextEntry::make('father_name')
                                ->label('שם האב')
                                ->weight(FontWeight::Bold)
                                ->size(Components\TextEntry\TextEntrySize::Large),

                            Components\TextEntry::make('mother_name')
                                ->label('שם האם')
                                ->weight(FontWeight::Bold)
                                ->size(Components\TextEntry\TextEntrySize::Large),
                        ]),
                ]),
            ]); // TODO: Change the autogenerated stub
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
            'view' => Pages\ViewStudent::route('/{record}'),
            //            'proposals_guy' => Pages\ManageProposalsGuy::route('/{record}/proposals'),
            'proposals' => Pages\ManageProposals::route('/{record}/proposals'),
            'family' => Pages\Family::route('/{record}/family'),
            'add_proposal' => Pages\AddProposal::route('/{record}/add_proposal'),
            'subscription' => Pages\Subscription::route('/{record}/subscription'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewStudent::class,
            Pages\ManageProposals::class,
            Pages\EditStudent::class,
            Pages\Family::class,
            Pages\Subscription::class,
            Pages\AddProposal::class,
        ]);
    }

    private static function filters()
    {
        return [
            Filters\Filter::make('table_columns')
                ->columnSpanFull()
                ->columns(1)
                ->query(function (Builder $query, array $data) {
                    return $query->filterStudent($data);
                })
                ->form([
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
                        Cluster::make([
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

                        Cluster::make([
                            ToggleButtons::make('gender')
                                ->grouped()
                                ->default('all')
                                ->extraAttributes(['class' => 'rounded-e-none [&>label:nth-last-child(1_of_.fi-btn)]:rounded-e-none'])
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
                            ->label('מין / גיל / כתה')

                    ])->columns(6)->hiddenLabel(),
                ]),
        ];
    }
}
