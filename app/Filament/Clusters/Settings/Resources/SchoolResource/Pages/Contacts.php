<?php

namespace app\Filament\Clusters\Settings\Resources\SchoolResource\Pages;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Forms\Components\Select;
use Filament\Actions\DetachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachBulkAction;
use app\Filament\Clusters\Settings\Resources\SchoolResource;
use App\Models\Contact;
use App\Models\Person;
use App\Models\School;
use Filament\Forms;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class Contacts extends ManageRelatedRecords
{
    protected static string $resource = SchoolResource::class;

    protected static string $relationship = 'contacts';

    protected static string | \BackedEnum | null $navigationIcon = 'iconsax-bul-user-square';

    protected static ?string $title = 'אנשי קשר';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->pluralModelLabel('אנשי קשר')
            ->modelLabel('איש קשר')
            ->columns([
                Person::nameColumn(),
                TextInputColumn::make('pivot.type')
                    ->updateStateUsing(function ($state, Person $person) {
                        $person->pivot->update(['type' => $state]);
                    })
                    ->label('סוג קשר'),
            ])
            ->filters([
                //
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
                                ->datalist(Contact::whereModelType(School::class)
                                    ->whereRelation('model', 'type', $this->getOwnerRecord()->type)->pluck('type', 'type')
                                    ->unique()
                                    ->toArray()
                                )
                                ->required(),
                        ]);
                    })
                    ->action(function (array $data) {
                        $this->getOwnerRecord()
                            ->contacts()
                            ->attach([
                                $data['person_id'] => ['type' => $data['type']],
                            ]);
                    })
                    ->modalSubmitActionLabel('שייך')
                    ->outlined()
                    ->modalHeading('הוספת איש קשר'),
            ])
            ->recordActions([
                DetachAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
