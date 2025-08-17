<?php

namespace App\Filament\Resources\People\Pages;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Support\Enums\Width;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use App\Actions\RunPayments;
use App\Filament\Resources\People\PersonResource;
use App\Models\CreditCard;
use App\Models\Person;
use Filament\Forms;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class CreditCards extends ManageRelatedRecords
{
    protected static string $resource = PersonResource::class;

    protected static string $relationship = 'cards';

    protected static string | \BackedEnum | null $navigationIcon = 'iconsax-bul-card';

    protected static ?string $title = 'כרטיסי אשראי';

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('students_subscriptions');
    }
//    public static function getNavigationLabel(): string
//    {
//        return 'כרטיסי אשראי';
//    }

    public function form(Schema $schema): Schema
    {
        /*
        brand
        token
        last4
        is_active
        data
        */
        return $schema
            ->columns(1)
            ->components(static::formFields());
    }

    public static function formFields(): array
    {
        return [
            TextInput::make('card')
                ->label('כרטיס אשראי')
//                    ->rule('digits_between:7,16')
                ->mask('9999 9999 9999 9999')
                ->required(),

            TextInput::make('exp')
                ->label('תוקף')
                ->mask('99/99')
//                    ->rule('digits:4')
                ->required(),

            TextInput::make('cvv')
                ->label('CVV')
                ->rule('digits:3')
                ->required(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('כרטיסי אשראי')
            ->columns([
                ToggleColumn::make('is_active'),
                TextColumn::make('last4'),
                TextColumn::make('token'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalWidth(Width::Small)
                    ->label('כרטיס אשראי')
                    ->icon('heroicon-o-credit-card')
                    ->modalHeading('הוסף כרטיס אשראי')
                    ->action(function ($data, Schema $schema, HasTable $livewire, CreateAction $action) {
                        $record = $livewire->getRecord();
                        static::createNewCreditCard($record, $action, $data);
                    }),
//                Tables\Actions\AssociateAction::make(),
            ])
            ->recordActions([
                Action::make('transfer')
                    ->label('החלף בעלים')
                    ->icon('heroicon-o-arrows-right-left')
                    ->action(function ($action, $record, $data) {

                        Person::query()
                            ->where('billing_credit_card_id', $record->id)
                            ->update([
                                'billing_payer_id' => $data['person_id'],
                            ]);

                        $record->update([
                            'person_id' => $data['person_id'],
                        ]);

                        $action->success();
                    })
                    ->modalWidth(Width::Small)
                    ->schema([
                        Select::make('person_id')
                            ->label('בעלים חדש')
                            ->getOptionLabelUsing(fn($value) => Person::find($value)?->select_option_html)
                            ->searchable()
                            ->allowHtml()
                            ->required()
                            ->getSearchResultsUsing(fn($search) => Person::query()
                                ->when($search, fn($query, $search) => $query->searchName($search))
                                ->with('father', 'spouse')
                                ->limit(10)
                                ->get()
                                ->mapWithKeys(fn($person) => [$person->id => $person->select_option_html])
                                ->toArray()
                            )
                    ])
//                Tables\Actions\EditAction::make(),
//                Tables\Actions\DissociateAction::make(),
//                Tables\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
//                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DissociateBulkAction::make(),
//                    Tables\Actions\DeleteBulkAction::make(),
//                ]),
            ]);
    }

    static public function createNewCreditCard(Person $record, array $data, $action = null): ?CreditCard
    {
        $card = $record->createCreditCard($data);

        if ($card instanceof CreditCard) {
            if ($action) {
                $action->successNotificationTitle('הכרטיס נוסף בהצלחה');
                $action->success();
            }

            return $card;
        }

        if ($action) {
            $action->failureNotificationTitle($card['Message']);
            $action->failure();
            $action->halt();
        }

        return null;
    }
}
