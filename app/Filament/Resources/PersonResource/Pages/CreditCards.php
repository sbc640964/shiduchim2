<?php

namespace App\Filament\Resources\PersonResource\Pages;

use App\Actions\RunPayments;
use App\Filament\Resources\PersonResource;
use App\Models\CreditCard;
use App\Models\Person;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class CreditCards extends ManageRelatedRecords
{
    protected static string $resource = PersonResource::class;

    protected static string $relationship = 'cards';

    protected static ?string $navigationIcon = 'iconsax-bul-card';

    protected static ?string $title = 'כרטיסי אשראי';

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('students_subscriptions');
    }
//    public static function getNavigationLabel(): string
//    {
//        return 'כרטיסי אשראי';
//    }

    public function form(Form $form): Form
    {
        /*
        brand
        token
        last4
        is_active
        data
        */
        return $form
            ->columns(1)
            ->schema(static::formFields());
    }

    public static function formFields(): array
    {
        return [
            Forms\Components\TextInput::make('card')
                ->label('כרטיס אשראי')
//                    ->rule('digits_between:7,16')
                ->mask('9999 9999 9999 9999')
                ->required(),

            Forms\Components\TextInput::make('exp')
                ->label('תוקף')
                ->mask('99/99')
//                    ->rule('digits:4')
                ->required(),

            Forms\Components\TextInput::make('cvv')
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
                Tables\Columns\ToggleColumn::make('is_active'),
                Tables\Columns\TextColumn::make('last4'),
                Tables\Columns\TextColumn::make('token'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->modalWidth(MaxWidth::Small)
                    ->label('כרטיס אשראי')
                    ->icon('heroicon-o-credit-card')
                    ->modalHeading('הוסף כרטיס אשראי')
                    ->action(function ($data, Form $form, HasTable $livewire, Tables\Actions\CreateAction $action) {
                        $record = $livewire->getRecord();
                        static::createNewCreditCard($record, $action, $data);
                    }),
//                Tables\Actions\AssociateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('transfer')
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
                    ->modalWidth(MaxWidth::Small)
                    ->form([
                        Forms\Components\Select::make('person_id')
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
            ->bulkActions([
//                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DissociateBulkAction::make(),
//                    Tables\Actions\DeleteBulkAction::make(),
//                ]),
            ]);
    }

    static public function createNewCreditCard($record, array $data, $action = null): ?CreditCard
    {
        $card = RunPayments::createDirectDebit($record, $data);

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
