<?php

namespace app\Filament\Clusters\Settings\Resources\SchoolResource\Pages;

use app\Filament\Clusters\Settings\Resources\SchoolResource;
use App\Models\Contact;
use App\Models\Person;
use App\Models\School;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class Contacts extends ManageRelatedRecords
{
    protected static string $resource = SchoolResource::class;

    protected static string $relationship = 'contacts';

    protected static ?string $navigationIcon = 'iconsax-bul-user-square';

    protected static ?string $title = 'אנשי קשר';

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
            ->pluralModelLabel('אנשי קשר')
            ->modelLabel('איש קשר')
            ->columns([
                Person::nameColumn(),
                Tables\Columns\TextInputColumn::make('pivot.type')
                    ->updateStateUsing(function ($state, Person $person) {
                        $person->pivot->update(['type' => $state]);
                    })
                    ->label('סוג קשר'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('create-new')
                    ->label('שייך חדש')
                    ->color('gray')
                    ->modalWidth(MaxWidth::Small)
                    ->form(function (Form $form) {
                        return $form->schema([
                            Forms\Components\Select::make('person_id')
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
                            Forms\Components\TextInput::make('type')
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
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
