<?php

namespace App\Filament\Resources\PersonResource\Pages;

use App\Filament\Resources\PersonResource;
use App\Models\Person;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

class Family extends ManageRelatedRecords
{
    protected static string $resource = PersonResource::class;

    protected static string $relationship = 'relatives';

    protected static ?string $navigationIcon = 'iconsax-bul-data-2';

    protected static ?string $navigationGroup = 'קשרים';

    //    protected static bool $shouldSkipAuthorization = true;

    protected static ?string $title = 'משפחה';

    #[Url]
    public ?string $activeTab = 'self';

    public function getRelationship(): Relation|Builder
    {
        $record = match ($this->activeTab) {
            'self' => $this->getOwnerRecord(),
            'spouse' => $this->getOwnerRecord()->spouse,
            'father' => $this->getOwnerRecord()->father,
            'mother' => $this->getOwnerRecord()->mother,
            'father_in_law' => $this->getOwnerRecord()->fatherInLaw,
            'mother_in_law' => $this->getOwnerRecord()->motherInLaw,
            'parentsFamily' => $this->getOwnerRecord()->parentsFamily->children(),
            'spouseParentsFamily' => $this->getOwnerRecord()->spouse->parentsFamily->children(),
        };

        if ($record instanceof Relation) {
            return $record;
        }

        $relationshipName = $record->gender === 'G'
            ? 'childrenM'
            : 'childrenF';

        return $record->{$relationshipName}();
    }

    public function getHeaderWidgetsColumns(): int|string|array
    {
        return 2;
    }

    protected function getHeaderWidgets(): array
    {
        /** @var Person $record */
        $record = $this->getOwnerRecord();

        if (! $record->family()->exists()) {
            return [
                PersonResource\Widgets\FathersOverview::make(),
            ];
        }

        return [
            PersonResource\Widgets\SelfFamilyOverview::make(),
            PersonResource\Widgets\FathersOverview::make(),
            PersonResource\Widgets\FathersInLawOverview::make(),
        ];
    }

    #[On('set-current-tab')]
    public function setCurrentTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('full_name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function getTabsNames(): array
    {
        return [
            'self' => Tab::make('ילדים'),
            'spouse' => Tab::make('ילדי בן/בת זוג'),
            'father' => Tab::make(' ילדי אב'),
            'mother' => Tab::make(' ילדי אם'),
            'father_in_law' => Tab::make('ילדי אב חותן'),
            'mother_in_law' => Tab::make('ילדי אם חותנת'),
            'parentsFamily' => Tab::make('ילדי הורים'),
            'spouseParentsFamily' => Tab::make('ילדי הורי '.($this->getOwnerRecord()->gender === 'G' ? 'בעל' : 'אשה')),
        ];
    }

    public function table(Table $table): Table
    {
        $columns = PersonResource::tableColumns();

        return $table
            ->deferLoading()
            ->heading(fn () => $this->getTabsNames()[$this->activeTab]->getLabel())
            ->recordTitleAttribute('full_name')
            ->columns($columns)
            ->filters([
                //
            ])
            ->headerActions([
                //                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->size('xs')
                    ->tooltip('צפייה')
                    ->iconButton(),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->tooltip('עריכה')
                    ->size('xs')
                    ->iconButton(),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['family', 'spouse.father', 'families' => fn ($query) => $query->withCount('children')]);
            })
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }
}
