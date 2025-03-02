<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\PersonResource\Pages\CreditCards;
use App\Filament\Resources\StudentResource;
use App\Filament\Resources\StudentResource\Widgets\SubscriptionInfo;
use App\Models\CreditCard;
use App\Models\Payment;
use App\Models\Person;
use App\Models\Subscriber;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
        return $infolist
            ->schema([
                KeyValueEntry::make('data')
                    ->keyLabel('סוג ערך')
                    ->valueLabel('ערך')
                    ->columnSpanFull()
                    ->label('תגובת החיוב בנדרים פלוס'),
            ]);
    }

    static function formFields(?Subscriber $formRecord = null): array
    {
        return [
            Forms\Components\Select::make('referrer_id')
                ->model(Subscriber::class)
                ->relationship('referrer', modifyQueryUsing: fn (Builder $query, ?string $search) =>
                    $query->limit(60)
                        ->orderBy('first_name')
                        ->orderBy('last_name')
                        ->searchName($search)
                        ->with(['father', 'spouse', 'mother', 'family.city', 'city'])
                )
                ->saveRelationshipsUsing(fn () => null)
                ->label('מפנה')
                ->optionsLimit(60)
                ->getOptionLabelFromRecordUsing(fn(Person $record) => $record->getSelectOptionHtmlAttribute(withAddress: true))
                ->getSearchResultsUsing(fn (string $search) => Person::searchName($search)->limit(60)
                    ->get()
                    ->mapWithKeys(fn(Person $person) => [$person->getKey() => $person->getSelectOptionHtmlAttribute(withAddress: true)])
                )
                ->searchable()
                ->allowHtml(),

            Forms\Components\Select::make('payer_id')
                ->model(Subscriber::class)
                ->relationship('payer', modifyQueryUsing: fn (Builder $query, ?string $search) =>
                    $query->limit(60)
                        ->orderBy('first_name')
                        ->orderBy('last_name')
                        ->searchName($search)
                        ->with(['father', 'spouse', 'mother', 'family.city', 'city'])
                )
                ->saveRelationshipsUsing(fn () => null)
                ->default(fn($livewire) => $livewire->getRecord()->father_id)
                ->helperText('ברירת מחדל יופיע האבא של הבחור/ה')
                ->label('משלם')
                ->searchable()
                ->allowHtml()
                ->getOptionLabelFromRecordUsing(fn(Person $record) => $record->getSelectOptionHtmlAttribute(withAddress: true))
                ->getSearchResultsUsing(fn (string $search) => Person::searchName($search)
                    ->limit(60)
                    ->with(['father', 'spouse', 'mother', 'family.city', 'city'])
                    ->orderBy('first_name')
                    ->orderBy('last_name')
                    ->get()
                    ->mapWithKeys(fn(Person $person) => [$person->getKey() => $person->getSelectOptionHtmlAttribute(withAddress: true)])
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
                ->preload()
                ->options(fn (Forms\Get $get) => $get('payer_id')
                    ? CreditCard::where('person_id', $get('payer_id'))->get()->mapWithKeys(fn(CreditCard $card) => [$card->getKey() => $card->last4])
                    : []
                )
                ->searchable()
                ->hidden(fn(Forms\Get $get) => $get('method') !== 'credit_card')
                ->disabled(fn(Forms\Get $get) => !$get('payer_id'))
                ->native(false)
                ->createOptionForm(function (Forms\Get $get,  Form $form) {
                    return $form
                        ->schema([
                            Forms\Components\Hidden::make('person_id')
                                ->default($get('payer_id'))
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

            Forms\Components\TextInput::make('payments')
                ->label("מס תשלומים/חודשי עבודה")
                ->numeric()
                ->live()
                ->required(),

            Forms\Components\Toggle::make('is_published')
                ->label('פרסם לכל השדכנים')
                ->visible(fn(Forms\Get $get) => !$get('user_id'))
                ->default(true)
                ->nullable(),

            Forms\Components\Textarea::make('notes')
                ->label('הערות')
                ->rule('max:255')
                ->rows(3)
                ->nullable(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        if (! $this->getRecord()->lastSubscription) {
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
            ->modifyQueryUsing(fn ($livewire) => $livewire->getRecord()->payments()->getQuery()->select('payments.*'))
            ->emptyStateHeading('עדיין אין פעולות חיוב')
            ->emptyStateDescription('בעת פעולת חיוב במנוי תוכל לראות אותה כאן ')
            ->emptyStateIcon('heroicon-o-credit-card')
            ->recordTitleAttribute('last4')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('תאריך')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->width(100)
                    ->tooltip(fn ($record) => $record->created_at->format('H:i')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->width(100)
                    ->tooltip(fn ($record) => $record->status_message)
                    ->color(fn($state) => match ($state) {
                        'OK' => 'success',
                        'Error' => 'danger',
                        'refunded' => 'warning',
                        'cancelled' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'OK' => 'הצליח',
                        'Error' => 'נכשל',
                        'refunded' => 'הוחזר',
                        'cancelled' => 'בוטל',
                        default => $state,
                    })
                    ->label('סטטוס'),
                Tables\Columns\TextColumn::make('description')
                    ->tooltip(fn (Payment $record) => str($record->description)->endsWith('*') ? 'חיוב ידני' : false)
                    ->label('תיאור'),
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
            ->heading('פעולות חיוב')
            ->hiddenFilterIndicators()
            ->filters([
                Tables\Filters\SelectFilter::make('subscriber_id')
                    ->default($this->getRecord()->lastSubscription?->id ?? null)
                    ->options($this->getRecord()->subscriptions
                        ->mapWithKeys(fn(Subscriber $subscriber) => [$subscriber->id => $subscriber->getToOptionsSelect()])
                    )
                    ->label('מנוי'),

                Tables\Filters\SelectFilter::make('subscribers.status')
                    ->options([
                        'OK' => 'הצליח',
                        'FAILED' => 'נכשל',
                    ])
                    ->label('סטטוס'),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->headerActions([
                Tables\Actions\Action::make('charge')
                ->label('חייב ח"פ')
                ->requiresConfirmation()
                ->form([

                    Forms\Components\TextInput::make('amount')
                        ->label('סכום')
                        ->numeric()
                        ->default($this->getRecord()->amount)
                        ->required(),

                    Forms\Components\Toggle::make('join')
                        ->visible(fn(Forms\Get $get) => $get('amount') === $this->getRecord()->amount)
                        ->label('צרף את התשלום כחלק מהו"ק')
                        ->helperText('אם התשלום יצורף המנוי תשלומי היתרה ותאריך הבא יעודכנו')
                        ->rule('max:255'),
                ])
                ->action(fn (array $data) => $this->getRecord()
                    ->lastSubscription
                    ->charge(true, $data['join'], $data['amount']))

                ,
                Tables\Actions\CreateAction::make()
                    ->label('הגדר מנוי')
                    ->createAnother(false)
                    ->slideOver()
                    ->modalWidth(MaxWidth::Small)
                    ->modalHeading('הגדר מנוי')
                    ->hidden(!! $this->getRecord()->lastSubscription?->isCurrent())
                    ->using(function ($data, $action, self $livewire) {//

                        /** @var Person $record */
                        $record = $livewire->getRecord();

                        $status = 'pending';

                        if(filled($data['matchmaker_id'] ?? null)
                            && filled($data['payments'])
                            && filled($data['start_date'])
                            && filled($data['amount'])
                            && filled($data['work_day'])
                        ) {
                            $status = 'active';
                        }

                        $record->subscriptions()->create(array_merge($data, [
                            'status' => $status,
                            'balance_payments' => $data['payments'],
                            'end_date' => $data['start_date'] ?? null
                                ? Carbon::make($data['start_date'])->addMonths((int) $data['payments'])
                                : null,
                        ]));

                        return $record;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('פרטי חיוב')
                    ->record($this->getRecord())
                    ->iconButton(),
                Tables\Actions\Action::make('cancel')
                    ->label('ביטול עסקה')
                    ->color('danger')
                    ->button()
                    ->size('xs')
                    ->visible(fn (Payment $record) => $record->status === 'OK'
                        && $record->created_at->isToday()
                        && auth()->user()->can('refund_payments')
                    )
                    ->action(function (Payment $record, array $data, Tables\Actions\Action $action) {
                        $result = $record->cancel($data['comments'] ?? '');

                        if($result['Result'] === 'OK') {
                            $action->successNotificationTitle('העסקה בוטלה בהצלחה');
                            $action->success();

                            return;
                        }

                        $action->failureNotificationTitle('הביטול נכשל (' . $result['Message'] . ')');
                        $action->failure();
                    })
                    ->form([
                        Forms\Components\Textarea::make('comments')
                            ->label('הערות')
                            ->rule('max:255')
                            ->rows(3),
                        Forms\Components\TextInput::make('confirm_password')
                            ->label('סיסמה')
                            ->helperText('הכנס את סיסמת המשתמש כדי לאשר את הפעולה')
                            ->password()
                            ->markAsRequired()
                            ->rule('required')
                            ->currentPassword(),
                    ])
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('refund')
                    ->label('החזר עסקה')
                    ->color('danger')
                    ->button()
                    ->size('xs')
                    ->visible(fn (Payment $record) => $record->status === 'OK' && auth()->user()->can('refund_payments'))
                    ->action(function (Payment $record, array $data, Tables\Actions\Action $action) {
                        $result = $record->refund($data['comments'] ?? '', $data['amount'] ?? null);

                        if($result['Result'] === 'OK') {
                            $action->successNotificationTitle('ההחזרה בוצעה בהצלחה');
                            $action->success();

                            return;
                        }

                        if($result['Message'] === "עסקה זו כבר בוטלה") {
                            if($record->is_join) {
                                $record->subscriber->subPayment();
                                $record->status = 'cancelled';
                                $record->save();
                            }
                        }

                        $action->failureNotificationTitle('ההחזרה נכשלה (' . $result['Message'] . ')');
                        $action->failure();
                    })
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('סכום')
                            ->numeric()
                            ->helperText('לא ניתן לזכות פעמיים את אותה עסקה')
                            ->default(fn ($record) => $record->amount)
                            ->required(),
                        Forms\Components\Textarea::make('comments')
                            ->label('הערות')
                            ->rule('max:255')
                            ->rows(3),
                        Forms\Components\TextInput::make('confirm_password')
                            ->label('סיסמה')
                            ->helperText('הכנס את סיסמת המשתמש כדי לאשר את הפעולה')
                            ->password()
                            ->markAsRequired()
                            ->rule('required')
                            ->currentPassword(),
                    ])
                    ->requiresConfirmation(),

            ])
            ->bulkActions([
//                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
//                ]),
            ]);
    }
}
