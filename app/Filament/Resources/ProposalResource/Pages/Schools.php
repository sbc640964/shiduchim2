<?php

namespace App\Filament\Resources\ProposalResource\Pages;

use App\Filament\Actions\Call;
use App\Filament\Resources\ProposalResource;
use App\Models\Person;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\Locked;

class Schools extends ManageRelatedRecords
{
    protected static string $resource = ProposalResource::class;

    protected static string $relationship = 'contacts';

    protected static ?string $navigationIcon = 'iconsax-bul-book';

    protected static ?string $title = 'בתי חינוך';

    #[Locked]
    public ?string $side = null;

    public bool $doesntHaveSchools = false;

    public function mount(int|string $record): void
    {
        $arguments = func_get_args();

        $this->side = $arguments[1] ?? $this->side;

        parent::mount($record);
    }

    public function getRelationship(): Relation|Builder
    {
        return $this->getRelationshipByTabName($this->activeTab);
    }

    public function getRelationshipByTabName($name): Relation|Builder|null
    {
        $this->doesntHaveSchools = false;

        $schools = $this->getOwnerRecord()->{$this->side}->schools;

        if ($relationship = $schools->firstWhere('id', $name)?->contacts()) {
            return $relationship;
        }
        $this->doesntHaveSchools = true;

        return $this->getOwnerRecord()->contacts()->where('contacts.id', 0);
    }

    public function getTabs(): array
    {
        $schools = $this->getOwnerRecord()->{$this->side}->schools;

        return $schools->mapWithKeys(function ($school) {
            return [$school->id => Tab::make($school->name)];
        })->toArray();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->emptyStateHeading(fn () => $this->getOwnerRecord()->{$this->side}->schools()->exists() ? 'לא נמצאו אנשי קשר' : 'לא נמצאו בתי חינוך')
            ->columns([
                Person::nameColumn(withSpouse: false),
                Tables\Columns\TextInputColumn::make('pivot.type')
                    ->updateStateUsing(function ($state, Person $person) {
                        $person->pivot->update(['type' => $state]);
                    })
                    ->label('סוג קשר'),
            ])
            ->actions([
                Call::tableActionDefaultPhone($this->getOwnerRecord(), $this->side),
                Call::tableAction($this->getOwnerRecord(), $this->side),
            ])
            ->filters([
                //
            ]);
    }
}
