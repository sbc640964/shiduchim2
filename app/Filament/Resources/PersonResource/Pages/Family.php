<?php

namespace App\Filament\Resources\PersonResource\Pages;

use App\Filament\Resources\PersonResource\Widgets\FathersOverview;
use App\Filament\Resources\PersonResource\Widgets\SelfFamilyOverview;
use App\Filament\Resources\PersonResource\Widgets\FathersInLawOverview;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\PersonResource;
use App\Models\Person;
use Filament\Forms;
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

    protected static string | \BackedEnum | null $navigationIcon = 'iconsax-bul-data-2';

    protected static string | \UnitEnum | null $navigationGroup = 'קשרים';

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

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }

    protected function getHeaderWidgets(): array
    {
        /** @var Person $record */
        $record = $this->getOwnerRecord();

        if (! $record->family()->exists()) {
            return [
                FathersOverview::make(),
            ];
        }

        return [
            SelfFamilyOverview::make(),
            FathersOverview::make(),
            FathersInLawOverview::make(),
        ];
    }

    #[On('set-current-tab')]
    public function setCurrentTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
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
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->size('xs')
                    ->tooltip('צפייה')
                    ->iconButton(),
                EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->tooltip('עריכה')
                    ->size('xs')
                    ->iconButton(),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['family', 'spouse.father', 'families' => fn ($query) => $query->withCount('children')]);
            })
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }
}
