<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\PersonResource\Pages\CreditCards;
use App\Filament\Resources\StudentResource;
use App\Filament\Resources\StudentResource\Widgets\SubscriptionInfo;
use App\Models\CreditCard;
use App\Models\Person;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\AccountWidget;
use Illuminate\Database\Eloquent\Model;

class Subscription extends ManageRelatedRecords
{
    protected static string $resource = StudentResource::class;

    protected $listeners = [
        'refreshPage' => '$refresh',
    ];

    protected static string $relationship = 'subscriptions';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $title = 'מנוי';
    public static function getNavigationLabel(): string
    {
        return 'מנוי';
    }

    public function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema(static::formFields());
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('students_subscriptions');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            KeyValueEntry::make('data')
                ->keyLabel('סוג ערך')
                ->valueLabel('ערך')
                ->columnSpanFull()
                ->label('תגובת החיוב בנדרים פלוס')
        ]);
    }

    static function formFields(): array
    {
        return [
            Forms\Components\DatePicker::make('billing_start_date')
                ->label('תאריך פניה ראשונית')
                ->native(false)
                ->default(now())
                ->required(),

            Forms\Components\Select::make('referer')
                ->label('מפנה')
                ->getOptionLabelUsing(fn($value) => Person::find($value)?->select_option_html)
                ->searchable()
                ->allowHtml()
                ->getSearchResultsUsing(fn($search) => Person::query()
                    ->when($search, fn($query, $search) => $query->searchName($search))
                    ->with('father', 'spouse')
                    ->limit(10)
                    ->get()
                    ->mapWithKeys(fn($person) => [$person->id => $person->select_option_html])
                    ->toArray()
                ),

            Forms\Components\Select::make('person_id')
                ->label('משלם')
                ->searchable()
                ->allowHtml()
                ->getOptionLabelUsing(fn($value) => Person::find($value)?->select_option_html)
                ->getSearchResultsUsing(fn($search) => Person::query()
                    ->when($search, fn($query, $search) => $query->searchName($search))
                    ->with('father', 'spouse')
                    ->limit(10)
                    ->get()
                    ->mapWithKeys(fn($person) => [$person->id => $person->select_option_html])
                    ->toArray()
                )
                ->live()
                ->required(),

            Forms\Components\Select::make('method')
                ->label('אמצעי תשלום')
                ->options([
                    'credit_card' => 'כרטיס אשראי',
                    'cash' => 'מזומן',
                ])
                ->live()
                ->required(),

            Forms\Components\Select::make('credit_card_id')
                ->label('כרטיס אשראי')
                ->hidden(fn(Forms\Get $get) => $get('method') !== 'credit_card')
                ->disabled(fn(Forms\Get $get) => !$get('person_id'))
                ->placeholder('השאר ריק למזומן')
                ->selectablePlaceholder('השאר ריק למזומן')
                ->native(false)
                ->options(fn (Forms\Get $get) => CreditCard::where('person_id', $get('person_id'))->get()->pluck('last4', 'id')->toArray())
                ->createOptionForm(function (Forms\Get $get,  Form $form) {
                    return $form
                        ->schema([
                            Forms\Components\Hidden::make('person_id')
                                ->default($get('person_id'))
                                ->required(),
                            ...CreditCards::formFields(),
                        ]);
                })
                ->createOptionAction(fn ($action) => $action->modalWidth(MaxWidth::Small))
                ->createOptionUsing(function ($data) {
                    $record = Person::findOrFail($data['person_id']);
                    $card = CreditCards::createNewCreditCard($record, $data);
                    return $card?->getKey();
                })
                ->required(),

            Forms\Components\TextInput::make('amount')
                ->numeric()
                ->label('סכום')
                ->type('number')
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(',')
                ->required(),

            Forms\Components\Select::make('matchmaker')
                ->label('שדכן')
                ->placeholder('בחר שדכן')
                ->searchable()
                ->live()
                ->options(User::pluck('name', 'id')->toArray())
                ->nullable(),

            Forms\Components\Select::make('day')
                ->visible(fn(Forms\Get $get) => $get('matchmaker'))
                ->label('יום פעילות לשדכן')
                ->options(function (Forms\Get $get) {
//                    if(!$get('person_id')) {
//                        return [];
//                    }

//                    $hasDays = Person::whereId($get('person_id'))
//                        ->where('billing_status', 'active')
//                        ->whereNotNull('billing_matchmaker_day')
//                        ->pluck('billing_matchmaker_day');

                    return [
                        '1' => 'ראשון',
                        '2' => 'שני',
                        '3' => 'שלישי',
                        '4' => 'רביעי',
                        '5' => 'חמישי',
                        '6' => 'שישי',
                        '7' => 'מוצ"ש',
                    ];
                })
                ->nullable(),

            Forms\Components\TextInput::make('times')
                ->label("מס תשלומים")
                ->numeric()
                ->live()
                ->placeholder('אל תתחיל לגבות')
                ->nullable(),

            Forms\Components\DatePicker::make('next_date')
                ->label('תאריך תשלום ראשון')
                ->native(false)
                ->hidden(fn(Forms\Get $get) => !$get('times'))
                ->placeholder('הגבייה לא תתחיל עד שתבחר תאריך')
                ->nullable(),
            Forms\Components\Toggle::make('billing_published')
                ->label('פרסם לכל השדכנים')
                ->visible(fn(Forms\Get $get) => !$get('matchmaker'))
                ->default(true)
                ->nullable(),
            Forms\Components\Textarea::make('billing_notes')
                ->label('הערות')
                ->rule('max:255')
                ->rows(3)
                ->nullable(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        if (! $this->getRecord()->billing_status) {
            return [];
        }

        return [
            SubscriptionInfo::make([
                'record' => $this->getRecord()
            ]),
            StudentResource\Widgets\SubscriptionTasks::make([
                'record' => $this->getRecord()
            ])
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading($this->getRecord()->billing_status ? 'עדיין אין פעולות חיוב' :'לא מוגדר מנוי פעיל')
            ->emptyStateDescription($this->getRecord()->billing_status ? 'בעת פעולת חיוב במנוי תוכל לראות אותה כאן ' : 'הוסף מנוי חדש על ידי לחיצה על הכפתור למעלה')
            ->emptyStateIcon('heroicon-o-credit-card')
            ->recordTitleAttribute('last4')
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->width(100)
                    ->tooltip(fn ($record) => $record->status_message)
                    ->color(fn($state) => $state === 'OK' ? 'success' : 'danger')
                    ->formatStateUsing(fn($state) => $state === 'OK' ? 'הצליח' : 'נכשל')
                    ->label('סטטוס'),
                Tables\Columns\TextColumn::make('creditCard.person.full_name')
                    ->label('משלם'),
                Tables\Columns\TextColumn::make('last4')
                    ->formatStateUsing(fn($state) => "$state")
                    ->label('כרטיס אשראי'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('סכום')
                    ->money('ILS'),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('מספר עסקה'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('הגדר מנוי')
                    ->createAnother(false)
                    ->slideOver()
                    ->modalWidth(MaxWidth::Small)
                    ->modalHeading('הגדר מנוי')
                    ->hidden(!! $this->getRecord()->billing_status)
                    ->using(function ($data, $action, self $livewire) {//

                        /** @var Person $record */
                        $record = $livewire->getRecord();
                        $record->update([
                            'billing_payer_id' => $data['person_id'],
                            'billing_status' => 'pending',
                            'billing_amount' => $data['amount'],
                            'billing_balance_times' => $data['times'],
                            'billing_matchmaker' => $data['matchmaker'],
                            'billing_method' => $data['method'] ?? null,
                            'billing_next_date' => $data['next_date'] ?? null,
                            'billing_credit_card_id' => $data['credit_card_id'] ?? null,
                            'billing_matchmaker_day' => $data['day'] ?? null,
                            'billing_published' => $data['billing_published'] ?? false,
                            'billing_notes' => $data['billing_notes'] ?? null,
                            'billing_start_date' => $data['billing_start_date'],
                            'billing_referrer_id' => $data['referer'] ?? null,
                        ]);

                        return $record;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('פרטי חיוב')
                    ->iconButton(),

            ])
            ->bulkActions([
//                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
//                ]),
            ]);
    }
}
