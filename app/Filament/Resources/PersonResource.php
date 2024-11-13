<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PersonResource\Pages;
use App\Filament\Resources\PersonResource\RelationManagers\RelativesFatherRelationManager;
use App\Filament\Resources\PersonResource\RelationManagers\RelativesMatherRelationManager;
use App\Filament\Resources\PersonResource\RelationManagers\RelativesNuclearRelationManager;
use App\Models\Family;
use App\Models\Matchmaker;
use App\Models\Person;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class PersonResource extends Resource
{
    protected static ?string $model = Person::class;

    protected static ?string $slug = 'people';

    protected static ?string $navigationIcon = 'iconsax-bul-house';

    protected static ?string $navigationLabel = 'אנשים';

    protected static ?string $label = 'אדם';

    protected static ?string $pluralLabel = 'אנשים';

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(3)
                ->schema([
                    Forms\Components\Grid::make(1)
                        ->label('פרטים אישיים')
                        ->columnSpan(1)
                        ->schema([
                            Forms\Components\Section::make('גור')
                                ->schema([
                                    Person::externalCodeColumn()
                                ]),
                            Forms\Components\Section::make('כללי')
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
                                        ->label('משפחת הורים')
                                        ->relationship('parentsFamily'),

                                    Forms\Components\TextInput::make('first_name')
                                        ->label('שם פרטי')
                                        ->required(),

                                    Forms\Components\TextInput::make('last_name')
                                        ->label('שם משפחה')
                                        ->required(),

                                    Forms\Components\TextInput::make('address')
                                        ->label('כתובת'),

                                    Forms\Components\Select::make('city_id')
                                        ->relationship('city', 'name')
                                        ->label('עיר')
                                        ->searchable()
                                        ->preload(),

                                    Forms\Components\Group::make([
                                        Forms\Components\Fieldset::make('פרטי אשה')
                                            ->columns(1)
                                            ->schema([

                                                Family::filamentSelect('spouse_parents_family_id')
                                                    ->label('משפחת הורים')
                                                    ->relationship('family'),

                                                Forms\Components\TextInput::make('wife_first_name')
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
                    Forms\Components\Grid::make(1)
                        ->columnSpan(2)
                        ->schema([
                            static::familiesCard()->columnSpanFull()->visibleOn('edit'),
                            static::phonesCard()->columnSpanFull(),
                        ]),
                    Forms\Components\Section::make('פעולות')
                        ->visibleOn('edit')
                        ->columnSpanFull()
                        ->schema([
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('data-raw')
                                    ->action(fn () => null)
                                    ->infolist(function (Infolist $infolist, $record) {
                                        return $infolist
                                            ->record($record)
                                            ->schema([
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
                    ->with(['family', 'spouse', 'families' => fn ($query) => $query->withCount('children')])
                    ->where(fn ($query) => $query
                        ->whereHas('families')
                        ->where(fn ($query) => $query
                            ->whereRelation('family', fn (Builder $query) => $query->where('status', '!=', 'married'))
                            ->orWhere('gender', 'B')
                        ));
            })
            ->filters([
                Tables\Filters\SelectFilter::make('city')
                    ->relationship('city', 'name')
                    ->label('עיר')
                    ->placeholder('בחר עיר')
                    ->preload()
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status_family')
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
                Tables\Filters\SelectFilter::make('gender')
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

    public static function getRecordSubNavigation(\Filament\Resources\Pages\Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\EditPerson::class,
            Pages\Family::class,
            Pages\Proposals::class,
            Pages\CreditCards::class,
        ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }

    public static function tableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('external_code')
                ->label('קוד איחוד')
                ->searchable()
                ->toggleable()
                ->toggledHiddenByDefault()
                ->sortable(),

            Person::nameColumn()->searchable(['first_name', 'last_name'], isIndividual: true),

            Tables\Columns\TextColumn::make('phones.number')
                ->label('טלפון נייד')
                ->searchable(),

            Tables\Columns\TextColumn::make('family.phones.number')
                ->label('טלפון בבית')
                ->searchable(),

            ...Person::baseColumns(callback: [
                'father' => fn (Tables\Columns\TextColumn $col) => $col->searchable(isIndividual: true),
                'father_in_law' => fn (Tables\Columns\TextColumn $col) => $col->searchable(['first_name', 'last_name'], isIndividual: true),
            ]),

            Person::childrenColumn(),

            Tables\Columns\TextColumn::make('status_family')
                ->badge()
                ->color(fn ($state) => match ($state) {
                    'single' => 'danger',
                    'married' => 'success',
                    'divorced' => 'info',
                    'widower' => 'info',
                    default => 'gray',
                })
                ->formatStateUsing(fn ($state, Person $record) => match ($state) {
                    'single' => $record->gender === 'G' ? 'רווקה' : 'רווק',
                    'married' => $record->gender === 'G' ? 'נשואה' : 'נשוי',
                    'divorced' => $record->gender === 'G' ? 'גרושה' : 'גרוש',
                    'widower' => $record->gender === 'G' ? 'אלמנה' : 'אלמן',
                    default => 'לא ידוע',
                })
                ->label('מצב משפחתי'),
        ];
    }

    private static function familiesCard()
    {
        return Forms\Components\Group::make([
            Forms\Components\Repeater::make('families')
                ->hintAction(Forms\Components\Actions\Action::make('add_family')
                    ->label('נישואים שניים')
                    ->visible(fn ($record) => ! $record->isMarried())
                    ->form(function (Form $form) {
                        return $form->schema([
                            static::spouseSelect(),
                            Forms\Components\DatePicker::make('engagement_at')
                                ->label('תאריך אירוסין')
                                ->required(),

                            Forms\Components\TextInput::make('address')
                                ->label('כתובת'),

                            Forms\Components\Select::make('city_id')
                                ->relationship('city', 'name')
                                ->label('עיר')
                                ->searchable()
                                ->preload(),

                            Forms\Components\Select::make('matchmaker_id')
                                ->relationship('matchmaker.person', modifyQueryUsing: fn ($query) => $query->whereIn('id', Matchmaker::pluck('person_id')->toArray()))
                                ->label('שדכן')
                                ->getOptionLabelFromRecordUsing(fn (Person $record) => $record->select_option_html)
                                ->allowHtml()
                                ->searchable(['first_name', 'last_name']),
                        ]);
                    })
                    ->visible(fn ($record) => ! $record->isMarried())
                    ->modalWidth(MaxWidth::ExtraSmall)
                    ->action(function ($record, array $data, Forms\Components\Actions\Action $action) {

                        $inserted = \DB::transaction(function () use ($record, $data) {
                            $spouse = Person::find($data['spouse_id']);

                            $family = Family::create(array_merge(\Arr::except($data, 'spouse_id'), [
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
                        Forms\Components\Placeholder::make('wife')
                            ->content(fn (Family $record) => new HtmlString($record->people->firstWhere('id', '!=', $person->id)->select_option_html))
                            ->label('בן/בת זוג'),
                        Forms\Components\Placeholder::make('marriage_date')
                            ->label('תאריך אירוסין')
                            ->content(fn (Family $record) => $record->engagement_at?->hebcal()->hebrewDate(false, true) ?? '-'),
                        Forms\Components\Placeholder::make('status')
                            ->label('מצב משפחתי')
                            ->content(fn (Family $record) => $record->status_label),
                        Forms\Components\Fieldset::make('פעולות')
                            ->columns(1)
                            ->columnSpan(1)
                            ->extraAttributes(['class' => '!p-2'])
                            ->schema([
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('edit')
                                    ->label('עריכה')
                                    ->icon('heroicon-o-pencil')
                                    ->size(ActionSize::ExtraSmall)
                                    ->url(fn (Family $record) =>
                                        PersonResource::getUrl('edit', ['record' => $record->people->firstWhere('gender', $person->gender === 'B' ? 'G' : 'B')->id]),
                                        true
                                    ),
                                Forms\Components\Actions\Action::make('divorce')
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
                                    ->size(ActionSize::ExtraSmall)
                                    ->requiresConfirmation()
                                    ->successNotification(function (Notification $notification) use ($person) {
                                        return $notification
                                            ->actions([
                                                NotificationAction::make('cancel')
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
                                    ->action(function ($record, Forms\Components\Actions\Action $action, $livewire): void {
                                        if( $record && $record->divorce()) {
                                            $action->success();
                                            $livewire->refreshFormDataB(['father_in_law_id', 'spouse_id', 'families']);
                                        }
                                    }),
                                Forms\Components\Actions\Action::make('death')
                                    ->label('עדכון פטירה')
                                    ->form(function (Form $form) {
                                        return $form->schema([
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
                                    ->size(ActionSize::ExtraSmall)
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
        return Forms\Components\Section::make('טלפונים')
            ->columnSpan(2)
            ->columns(2)
            ->schema([
                Forms\Components\Group::make(fn (?Person $record = null) => [
                    Forms\Components\Select::make('phone_default_id')
                        ->label('טלפון ברירת מחדל')
                        ->hiddenOn('create')
                        ->options((! $record) ? [] : $record->phones()
                            ->when($record->family, fn ($query) => $query->unionAll($record->family->phones()->select('number', 'id')))
                            ->pluck('number', 'id')
                            ->toArray()
                        )
                        ->searchable(),
                ])->columns(3)->columnSpanFull(),


                Forms\Components\Repeater::make('phones')
                    ->relationship('phones')
                    ->addActionLabel('הוסף טלפון')
                    ->deleteAction(fn (Forms\Components\Actions\Action $action) => $action
                        ->icon('heroicon-o-trash')
                    )
                    ->label('טלפונים ישירים')
                    ->simple(
                        Forms\Components\TextInput::make('number')
                            ->label('טלפון')
                            ->unique('phones', 'number', ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'טלפון זה כבר קיים במערכת',
                            ])
                    ),

                Forms\Components\Group::make([
                    Forms\Components\Repeater::make('phones')
                        ->relationship('phones')
                        ->label('טלפונים בבית')
                        ->helperText('שים לב: טלפונים אלו מתעדכנים במשפחה שייכים לכלל הקרובים (אישה/ ילדים)')
                        ->addActionLabel('הוסף טלפון')
                        ->deleteAction(fn (Forms\Components\Actions\Action $action) => $action
                            ->icon('heroicon-o-trash')
                        )
                        ->simple(
                            Forms\Components\TextInput::make('number')
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
        return Forms\Components\Section::make('משפחה')
            ->columns()
            ->schema([
                Forms\Components\Select::make('father_id')
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
        return Forms\Components\Select::make('spouse_id')
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
