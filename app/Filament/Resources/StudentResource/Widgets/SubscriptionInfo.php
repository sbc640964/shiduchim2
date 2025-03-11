<?php

namespace App\Filament\Resources\StudentResource\Widgets;

use App\Filament\Resources\PersonResource\Pages\CreditCards;
use App\Filament\Resources\StudentResource;
use App\Models\CreditCard;
use App\Models\Person;
use App\Models\Subscriber;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\RawJs;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionInfo extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public Person $record;

    protected static string $view = 'filament.resources.student-resource.widgets.subscription-info';

    public function getSubscription(): Subscriber
    {
        return $this->record->lastSubscription;
    }

    public function getRecord(): Person
    {
        return $this->record;
    }

    public function activities(): Action
    {
        return Action::make('activities')
            ->label('היסטוריית מנוי')
            ->icon('heroicon-o-clock')
            ->slideOver()
            ->modalHeading('היסטוריית מנוי')
            ->modalContent(function () {
                $activities = $this->getSubscription()->activities()->with('user')->get();
                $users = User::findMany([
                    ...$activities->pluck('data.old'),
                    ...$activities->pluck('data.new'),
                ]);
                return view('filament.resources.student-resource.widgets.subscription-activities', [
                    'activities' => $activities,
                    'users' => $users,
                ]);
            });
    }

    public function toggleSubscription(): Action
    {
        $record = $this->getSubscription();

        $holdActivityAt = $record->activities()->where('type', 'hold')->latest()->first()?->created_at ?? null;


        return Action::make('toggleSubscription')
            ->label('')
            ->requiresConfirmation()
            ->visible($record->status !== 'completed' && (($record->end_date?->isFuture() ?? true) || $record->status === 'pending'))
            ->modalHeading('הפעל מנוי')
            ->modalDescription('האם אתה בטוח שברצונך להפעיל את המנוי?')
            ->extraAttributes([
                'class' => 'hidden-label-btn w-10 gap-0 items-center justify-center p-0',
            ])
            ->tooltip('הפעל מנוי')
            ->color('success')
            ->icon('heroicon-s-play')
            ->when($record->status === 'active', function (Action $component) use ($record) {
                $component
                    ->icon('heroicon-s-pause')
                    ->color('danger')
                    ->tooltip('השהה מנוי')
                    ->modalHeading('השהה מנוי')
                    ->modalDescription('האם אתה בטוח שברצונך להשהות את המנוי?')
                    ->action(function  (self $livewire) use ($record) {
                        $record->status = 'hold';
                        $record->save();

                        $record->recordActivity('hold');

                        $this->redirect(StudentResource::getUrl('subscription', [
                            'record' => $livewire->record->id,
                        ]), true);
                    });
            }, function (Action $component) use ($record, $holdActivityAt) {
                $component
                    ->action(function (self $livewire, array $data) use ($record, $holdActivityAt) {
                        $record->status = 'active';
                        $record->next_payment_date = $data['next_payment_date'];
                        $record->user_id = $data['user_id'];
                        $record->work_day = $data['work_day'];

                        if(!$holdActivityAt) {
                            $record->start_date = $data['start_date'];
                            $record->end_date = Carbon::make($data['start_date'])->addMonths($record->balance_payments);
                        } else {
                            $startDate = Carbon::make($data['start_date']);
                            $balance = $record->start_date->diffInDays($record->end_date) - $record->start_date->diffInDays($holdActivityAt);
                            $record->end_date = $startDate->addDays($balance);
                        }

                        $record->save();

                        if($record->isDirty('user_id')){
                            $record->recordActivity($record->getOriginal('user_id') ? 'replace_matchmaker' : 'set_matchmaker', collect([
                                'old' => $record->getOriginal('user_id'),
                                'new' => $data['user_id'],
                            ])->filter()->toArray());
                        }
                        $record->recordActivity('run', [
                            'start_date' => $data['start_date'],
                            'next_payment_date' => $record->next_payment_date,
                        ]);

                        $this->redirect(StudentResource::getUrl('subscription', [
                            'record' => $livewire->record->id,
                        ]), true);
                    })
                    ->form([
                        ...$this->setMatchmakerFormFields(),
                        DateTimePicker::make('start_date')
                            ->label('תאריך תחילת העבודה')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->rule('before:tomorrow')
                            ->validationMessages([
                                'before' => 'תאריך תחילת העבודה לא יכול להיות מאוחר מהיום',
                            ])
                            ->live()
                            ->helperText(function ($state) use($record, $holdActivityAt) {
                                if($state){
                                    $state = Carbon::make($state);
                                    $isPast = $state->isBefore(now()->startOfDay());
                                    $endDate = $record->status === 'hold'
                                        ? now()->addDays($record->start_date->diffInDays($record->end_date) - $record->start_date->diffInDays($holdActivityAt))
                                        : $state->copy()->addMonths($record->payments);
                                    return "תאריך הסיום: " . $endDate->format('d/m/Y') . " ($record->payments חודשים)" . ($isPast ? ' | התאריך מוקדם מהיום!' : '');
                                }
                                return null;
                            })
                            ->default(now()),
                        DateTimePicker::make('next_payment_date')
                            ->label('תאריך התשלום הבא')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->helperText(fn ($state) => $state && Carbon::make($state)->isBefore(now()->startOfDay()) ? 'שים לב!!! התאריך עבר!!!!!!!!!!!!' : '')
                            ->rule(fn () => function ($attribute, $value, $fail) use ($record) {
                                if ($value && Carbon::make($value)->isBefore(now()->startOfMonth())) {
                                    $fail('תאריך התשלום הבא חייב להיות מהחודש הנוכחי או אחרי');
                                }
                            })
                            ->default(function () use ($record) {
                                if($record->status === 'pending') {
                                    return now()->addDays(4)->startOfDay();
                                }

                                if($record->next_payment_date->isPast()) {
                                    return now();
                                }

                                return $record->next_payment_date;
                            }),
                    ]);
            })
            ->size('lg');
    }

    public function togglePublished(): Action
    {
        return Action::make('togglePublished')
            ->label('')
            ->icon(!$this->getSubscription()->is_published ? 'heroicon-s-eye' : 'heroicon-s-eye-slash')
            ->size('lg')
            ->outlined()
            ->color(!$this->getSubscription()->is_published ? 'success' : 'danger')
            ->extraAttributes([
                'class' => 'hidden-label-btn w-10 gap-0 items-center justify-center',
            ])
            ->tooltip(!$this->getSubscription()->is_published ? 'פרסם מנוי לשדכנים' : 'בטל פרסום מנוי לשדכנים')
            ->visible($this->getSubscription()->status === 'pending')
            ->action(fn (self $livewire) => $livewire->getSubscription()->update([
                'is_published' => ! $livewire->getSubscription()->is_published,
            ]));
    }

    public function setMatchmakerFormFields(): array
    {
        return [
            Forms\Components\Select::make('user_id')
                ->required()
                ->model(Subscriber::class)
                ->relationship('matchmaker', 'name')
                ->saveRelationshipsUsing(fn () => null)
                ->label('שדכן')
                ->placeholder('בחר שדכן')
                ->searchable()
                ->preload()
                ->default($this->getSubscription()->user_id)
                ->live(),

            Forms\Components\Select::make('work_day')
                ->required()
                ->label('יום פעילות לשדכן')
                ->default($this->getSubscription()->work_day)
                ->options(function (Forms\Get $get) {

                    $hasDays = collect();

                    if($user = $get('user_id') ? User::find($get('user_id')) : null) {
                        $hasDays = $user
                            ->subscribers()
                            ->isActive()
                            ->groupBy('work_day')
                            ->selectRaw('work_day, count(*) as count')
                            ->get()
                            ->pluck('count', 'work_day');
                    }

                    return collect(['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'מוצ"ש'])
                        ->mapWithKeys(function($day, $index) use($hasDays) {
                            $index = $index + 1;
                            return [$index => $day . ($hasDays->get($index) ? " ($hasDays[$index])" : '')];
                        });
                }),
        ];
    }

    public function setMatchmaker(): Action
    {
        return Action::make('setMatchmaker')
            ->label('הגדר שדכן')
            ->modalWidth(MaxWidth::Small)
            ->icon('heroicon-o-user')
            ->form([
                ...$this->setMatchmakerFormFields(),
            ])
            ->action(function (self $livewire, array $data){
                $record = $livewire->getSubscription();

                $record->fill([
                    'user_id' => $data['user_id'],
                    'work_day' => $data['work_day'],
                ]);

                $oldUser = $record->getOriginal('user_id');
                $userIdIsDirty = $record->isDirty('user_id');

                if($record->save()) {
                    if($userIdIsDirty) {
                        $record->recordActivity($oldUser ? 'replace_matchmaker' : 'set_matchmaker', collect([
                            'old' => $oldUser,
                            'new' => $data['user_id'],
                        ])->filter()->toArray());
                    }
                }
            });
    }

    public function cancelSubscription()
    {
        return Action::make('cancelSubscription')
            ->label('בטל מנוי')
            ->requiresConfirmation()
            ->form([
                Forms\Components\Textarea::make('reason')
                    ->label('סיבת ביטול')
                    ->required(),
            ])
            ->icon('heroicon-o-trash')
            ->action(function (self $livewire, array $data) {
                if($livewire->getSubscription()->update([
                    'status' => 'canceled',
                    'end_date' => now(),
                ])) {
                    $livewire->getSubscription()->recordActivity('cancel', [
                        'reason' => $data['reason'] ?? null,
                    ]);
                }

                $this->redirect(StudentResource::getUrl('subscription', [
                    'record' => $livewire->record->id,
                ]), true);
            })
            ->color('danger');
    }

    public function editBilling(): Action
    {
        return Action::make('editBilling')
            ->label('ערוך פרטי חיוב')
            ->icon('heroicon-o-pencil')
            ->modalHeading('ערוך פרטי חיוב')
            ->slideOver()
            ->record($this->getSubscription())
            ->fillForm($this->getSubscription()->attributesToArray())
            ->modalSubmitActionLabel('עדכן')
            ->modalWidth(MaxWidth::Small)
            ->action(function ($data, Action $action){

                if(($data['user_id'] ?? 0) > 0 && data_get($data, 'start_date')) {
                    $data['end_date'] = Carbon::make($data['start_date'])->addMonths($data['payments']);
                }

                $isNeedCharge = false;

                if(($data['payments'] ?? null)) {
                    $okTransactions = $this->record->lastSubscription
                        ->transactions()->whereStatus('OK')->count();

                    $balance = $data['payments'] - $okTransactions;

                    $data['balance_payments'] = $balance;

                    $oldPaymentsValue = $this->record->lastSubscription->payments;

                    if($oldPaymentsValue && $data['payments'] != $oldPaymentsValue && $this->record->lastSubscription->end_date) {
                        if($data['payments'] < $oldPaymentsValue) {
                            $data['end_date'] = $this->record->lastSubscription->end_date->copy()->subMonths($oldPaymentsValue - $data['payments']);
                        } else {
                            $data['end_date'] = $this->record->lastSubscription->end_date->copy()->addMonths($data['payments'] - $oldPaymentsValue);
                        }

                        if($this->record->lastSubscription->status === 'completed') {
                            $data['status'] = 'active';
                            if($this->record->lastSubscription->next_payment_date->isPast()) {
                                $isNeedCharge = true;
                            }
                        }
                    }
                }

                $this->record->lastSubscription->fill($data);

                $originalData = $this->record->lastSubscription->getOriginal();

                $updated = \DB::transaction(fn() => tap(
                    $this->record->lastSubscription->save(),
                    fn() => $this->record->lastSubscription->recordActivity('update', [
                        'old' => collect($originalData)->only(array_keys($this->record->lastSubscription->getChanges()))->toArray(),
                        'new' => collect($data)->only(array_keys($this->record->lastSubscription->getChanges()))->toArray(),
                    ])
                ));

                if($updated && $isNeedCharge) {
                    $this->record->lastSubscription->charge();
                    $action->successNotificationTitle('הפרטים נשמרו בהצלחה');
                    $action->success();
                    $this->getSubscription()->refresh();

                    return;
                }

                $action->failureNotificationTitle('הפרטים נשמרו בהצלחה');
                $action->failure();
            })
            ->form(function (Form $form) {
                return $form->schema(fn (Subscriber $record) => [
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
                ]);
            });
    }
}
