<?php

namespace App\Filament\Resources\Proposals\Pages;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Forms\Components\Select;
use Filament\Actions\ActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use App\Filament\Actions\Call;
use App\Filament\Resources\Proposals\ProposalResource;
use App\Models\Person;
use Filament\Forms;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\Locked;

class ManageContacts extends ManageRelatedRecords
{
    protected static string $resource = ProposalResource::class;

    protected static string $relationship = 'contacts';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-identification';

    protected static ?string $title = 'אנשי קשר';

    #[Locked]
    public ?string $side = null;

    public function getRelationship(): Relation|Builder
    {
        return parent::getRelationship()
            ->wherePivot('side', $this->side);
    }

    public function mount(int|string $record): void
    {
        $arguments = func_get_args();

        $this->side = $arguments[1] ?? $this->side;

        parent::mount($record);

        //        $this->getOwnerRecord()->loadMissing('contacts');
    }

    public static function getNavigationLabel(): string
    {
        return 'אנשי קשר';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('type')
                    ->label('סוג קשר')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('איש קשר')
            ->pluralModelLabel('אנשי קשר')
            ->recordTitleAttribute('name')
            ->heading($this->side === 'guy' ? 'אנשי קשר של הבחור' : 'אנשי קשר של הבחורה')
            ->filtersLayout(FiltersLayout::Modal)
            ->columns([
                Person::nameColumn()->searchable(['first_name', 'last_name']),
                TextColumn::make('pivot.type')
                    ->label('סוג קשר'),

            ])
            ->filters([

            ])
            ->headerActions([
                Action::make('create-new')
                    ->label('שייך חדש')
                    ->color('gray')
                    ->modalWidth(Width::Small)
                    ->schema(function (Schema $schema) {
                        return $schema->components([
                            Select::make('person_id')
                                ->searchable()
                                ->getSearchResultsUsing(function ($search) {
                                    $query = Person::query()->limit(60);

                                    foreach (explode(' ', $search) as $word) {
                                        $query->where(function (Builder $query) use ($word) {
                                            $query->where('first_name', 'like', "%$word%")
                                                ->orWhere('last_name', 'like', "%$word%")
                                                ->orWhere('address', 'like', "%$word%")
                                                ->orWhereRelation('city', 'name', 'like', "%$word%");
                                        });
                                    }

                                    return $query
                                        ->with('father', 'fatherInLaw')
                                        ->get()
                                        ->mapWithKeys(fn (Person $person) => [$person->getKey() => $person->select_option_html]);
                                })
                                ->allowHtml()
                                ->label('איש קשר')
                                ->required(),
                            TextInput::make('type')
                                ->label('סוג קשר')
                                ->required(),
                        ]);
                    })
                    ->action(fn (array $data) => $this->getOwnerRecord()
                        ->contacts()
                        ->attach([
                            $data['person_id'] => [
                                'type' => $data['type'],
                                'side' => $this->side,
                            ],
                        ])
                    )
                    ->modalSubmitActionLabel('שייך')
                    ->outlined()
                    ->modalHeading('הוספת איש קשר'),
            ])
            ->recordActions([
                Call::tableActionDefaultPhone($this->getOwnerRecord(), $this->side),
                Call::tableAction($this->getOwnerRecord(), $this->side),
                ActionGroup::make([
                    DetachAction::make()
                        ->icon('iconsax-bul-trash'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->icon('iconsax-bul-trash'),
                ]),
            ]);
    }

    public function getCallSchema(Person $person)
    {
        $phones = $person->phones
            ->each(fn ($phone) => $phone->number.' (אישי)')
            ->merge($person->family ? $person->family->phones->each(fn ($phone) => $phone->number = $phone->number.' (בבית)') : []);

        return [
            Radio::make('phone')
                ->options($phones->pluck('number', 'id')->toArray())
                ->default($phones->first()?->id)
                ->label('טלפון'),

            Toggle::make('use_new_phone')
                ->label('השתמש בטלפון חדש')
                ->live()
                ->default(false),

            Fieldset::make('הוספת טלפון')
                ->columns(3)
                ->visible(fn (Get $get) => $get('use_new_phone'))
                ->schema([
                    TextInput::make('new_phone')
                        ->hiddenLabel()
                        ->required()
                        ->columnSpan(2)
                        ->placeholder('מספר')
                        ->label('טלפון חדש'),

                    Select::make('type_phone')
                        ->native(false)
                        ->label('סוג טלפון')
                        ->hiddenLabel()
                        ->required()
                        ->placeholder('סוג')
                        ->options($person->family ? [
                            'personal' => 'אישי',
                            'home' => 'בבית',
                        ] : [
                            'personal' => 'אישי',
                        ]),
                ]),
        ];
    }
}
