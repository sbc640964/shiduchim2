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
use Filament\Actions\EditAction;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets\Widget;
use Filament\Forms\Components\Actions\Action as FormAction;

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
            ->visible($record->status !== 'completed')
            ->modalHeading('הפעל מנוי')
            ->modalDescription('האם אתה בטוח שברצונך להפעיל את המנוי?')
            ->modalContent(str(str(
                $record->next_payment_date->isPast()
                    ? 'המנוי יופעל **היום** והתשלום יתבצע מיד'
                    : 'המנוי יחוייב בתאריך ' . $record->next_payment_date->format('d/m/Y')
            )->markdown())->toHtmlString())
            ->action(function (self $livewire) use ($record) {
                $record->status = 'active';

                if ($record->next_payment_date->isPast()) {
                    $record->next_payment_date = now();
                    $record->end_date = $record->next_payment_date->copy()->addMonths($record->payments);
                }

                $record->save();

                $this->redirect(StudentResource::getUrl('subscription', [
                    'record' => $livewire->record->id,
                ]), true);
            })
            ->tooltip('הפעל מנוי')
            ->color('success')
            ->icon('heroicon-s-play')
            ->when($record->status === 'active', function ($component) use ($record) {
                $component
                    ->icon('heroicon-s-pause')
                    ->color('danger')
                    ->tooltip('השהה מנוי')
                    ->action(function  (self $livewire) use ($record) {
                        $record->status = 'hold';
                        $this->redirect(StudentResource::getUrl('subscription', [
                            'record' => $livewire->record->id,
                        ]), true);
                    });
            })
            ->size('lg');
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

                        redirect(StudentResource::getUrl('subscription', [
                            'record' => $livewire->record->id,
                        ]));
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
            })
            ->form(function (Form $form) {
                return $form->schema(fn (Subscriber $record) => [
                    ...Subscription::formFields($record),
                ]);
            });
    }
}
