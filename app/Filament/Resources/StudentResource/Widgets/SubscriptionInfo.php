<?php

namespace App\Filament\Resources\StudentResource\Widgets;

use App\Filament\Resources\StudentResource;
use App\Filament\Resources\StudentResource\Pages\Subscription;
use App\Models\Person;
use App\Models\Subscriber;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets\Widget;

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

    public function toggleSubscription(): Action
    {
        $record = $this->getSubscription();

        return Action::make('toggleSubscription')
            ->label('')
            ->requiresConfirmation()
            ->visible($record->status !== 'completed' && ($record->end_date?->isFuture() ?? null))
            ->modalHeading('הפעל מנוי')
            ->modalDescription('האם אתה בטוח שברצונך להפעיל את המנוי?')
            ->extraAttributes([
                'class' => 'hidden-label-btn w-10 gap-0 items-center justify-center p-0',
            ])
            ->modalContent(str(str(
                !$record->next_payment_date || $record->next_payment_date->isPast()
                    ? 'המנוי יופעל **היום** והתשלום יתבצע מיד'
                    : 'המנוי יחוייב בתאריך ' . $record->next_payment_date->format('d/m/Y')
            )->markdown())->toHtmlString())

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
                    ->modalContent(null)
                    ->action(function  (self $livewire) use ($record) {
                        $record->status = 'hold';
                        $record->save();
                        $this->redirect(StudentResource::getUrl('subscription', [
                            'record' => $livewire->record->id,
                        ]), true);
                    });
            }, function (Action $component) use ($record) {
                $component
                    ->action(function (self $livewire, array $data) use ($record) {
                        $record->status = 'active';

//                        if (!$record->next_payment_date || $record->next_payment_date->isPast()) {
//                            $record->next_payment_date = now();
//                            $record->end_date = $record->next_payment_date->copy()->addMonths($record->payments);
//                        }

                        $record->next_payment_date = $data['next_payment_date'];

                        $record->save();

                        $this->redirect(StudentResource::getUrl('subscription', [
                            'record' => $livewire->record->id,
                        ]), true);
                    })
                    ->form([
                        DateTimePicker::make('next_payment_date')
                            ->label('תאריך התשלום הבא')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->helperText(fn ($state) => $state && Carbon::make($state)->isPast() ? 'שים לב!!! התאריך עבר!!!!!!!!!!!!' : '')
                            ->rule(fn () => function ($attribute, $value, $fail) use ($record) {
                                if ($value && Carbon::make($value)->isBefore(now()->startOfMonth())) {
                                    $fail('תאריך התשלום הבא חייב להיות מהחודש הנוכחי או אחרי');
                                }
                            })
                            ->default($record->next_payment_date ?? $record->start_date),
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

    public function editBilling(): Action
    {
        return Action::make('editBilling')
            ->label('ערוך פרטי חיוב')
            ->modalHeading('ערוך פרטי חיוב')
            ->slideOver()
            ->record($this->getSubscription())
            ->fillForm($this->getSubscription()->attributesToArray())
            ->modalSubmitActionLabel('עדכן')
            ->extraModalFooterActions([
                Action::make('deleteBilling')
                    ->label('בטל מנוי')
                    ->requiresConfirmation()
                    ->icon('heroicon-o-x-mark')
                    ->action(function (self $livewire) {
                        $livewire->getSubscription()->update([
                            'status' => 'canceled',
                        ]);

                        $this->redirect(StudentResource::getUrl('subscription', [
                            'record' => $livewire->record->id,
                        ]), true);
                    })
                    ->color('danger'),
            ])
            ->modalWidth(MaxWidth::Small)
            ->action(function ($data, Action $action){

                if(($data['user_id'] ?? 0) > 0 && data_get($data, 'start_date')) {
                    $data['end_date'] = Carbon::make($data['start_date'])->addMonths($data['payments'] - 1);
                }

                if($data['payments'] ?? null) {
                    $okTransactions = $this->record->lastSubscription
                        ->transactions()->whereStatus('OK')->count();

                    $balance = $data['payments'] - $okTransactions;

                    $data['balance_payments'] = $balance;
                }

                $this->record
                    ->lastSubscription
                    ->update($data);

                $action->successNotificationTitle('הפרטים נשמרו בהצלחה');

                $action->success();

                $this->getSubscription()->refresh();
            })
            ->form(function (Form $form) {
                return $form->schema(fn (Subscriber $record) => [
                    ...Subscription::formFields($record),
                ]);
            });
    }
}
