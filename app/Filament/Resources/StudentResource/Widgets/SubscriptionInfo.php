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

    public function enableSubscription(): Action
    {
        return Action::make('enableSubscription')
            ->label('')
            ->requiresConfirmation()
            ->modalHeading('הפעל מנוי')
            ->modalDescription('האם אתה בטוח שברצונך להפעיל את המנוי?')
            ->modalContent(str(str($this->getSubscription()->next_payment_date->isToday()
                ? 'המנוי יופעל **היום** והתשלום יתבצע מיד'
                : 'המנוי יופעל בתאריך ' . $this->getSubscription()->next_payment_date->format('d/m/Y') . ' והתשלום הראשון יתבצע באותו יום'
            )->markdown())->toHtmlString())
            ->color('success')
            ->size('lg')
            ->tooltip('הפעל מנוי')
            ->icon('heroicon-s-play')
            ->action(function (self $livewire) {
                $livewire->record->lastSubscription->update(['status' => 'active']);
            });
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

                $this->record
                    ->lastSubscription
                    ->update($data);

                $action->successNotificationTitle('הפרטים נשמרו בהצלחה');

                $action->success();
            })
            ->form(function (Form $form) {
                return $form->schema(fn (Subscriber $record) => [
                    Actions::make([
                        FormAction::make('run')
                            ->color('success')
                            ->label('הפעל')
                            ->visible(in_array($this->record->billing_status, ['pending', 'hold', 'inactive']))
                            ->icon('heroicon-o-play')
                            ->action(function (self $livewire) {
                                $livewire->record->update(['billing_status' => 'active']);
                                redirect(StudentResource::getUrl('subscription', [
                                    'record' => $livewire->record->id,
                                ]));
                            }),

                        FormAction::make('hold')
                            ->label('השהה')
                            ->icon('heroicon-o-pause')
                            ->visible($this->record->billing_status === 'active')
                            ->action(function  (self $livewire){
                                $this->record->update(['billing_status' => 'hold']);
                                redirect(StudentResource::getUrl('subscription', [
                                    'record' => $livewire->record->id,
                                ]));
                            }) ,
                    ]),
                    ...Subscription::formFields($record),
                ]);
            });
    }
}
