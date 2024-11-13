<?php

namespace App\Filament\Resources\StudentResource\Widgets;

use App\Filament\Resources\StudentResource;
use App\Filament\Resources\StudentResource\Pages\Subscription;
use App\Models\Person;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
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

    public function editBilling(): Action
    {
        return Action::make('editBilling')
            ->label('ערוך פרטי חיוב')
            ->modalHeading('ערוך פרטי חיוב')
            ->slideOver()
            ->fillForm(function () {
                return [
                    'person_id' => $this->record->billingCard?->person_id ?? null,
                    'method' => $this->record->billing_method,
                    'credit_card_id' => $this->record->billing_credit_card_id,
                    'matchmaker' => $this->record->billing_matchmaker,
                    'times' => $this->record->billing_balance_times,
                    'next_date' => $this->record->billing_next_date,
                    'amount' => $this->record->billing_amount,
                ];
            })
            ->modalSubmitActionLabel('עדכן')
            ->extraModalFooterActions([
                Action::make('deleteBilling')
                    ->label('מחק פרטי חיוב')
                    ->requiresConfirmation()
                    ->icon('heroicon-o-trash')
                    ->action(function (self $livewire) {
                        $livewire->record->update([
                            'billing_status' => null,
                            'billing_amount' => null,
                            'billing_balance_times' => null,
                            'billing_matchmaker' => null,
                            'billing_method' => null,
                            'billing_next_date' => null,
                            'billing_credit_card_id' => null,
                        ]);

                        redirect(StudentResource::getUrl('subscription', [
                            'record' => $livewire->record->id,
                        ]));
                    })
                    ->color('danger'),
            ])
            ->modalWidth(MaxWidth::Small)
            ->action(function ($data, Action $action){

                $this->record->update([
                    'billing_amount' => $data['amount'],
                    'billing_balance_times' => $data['times'],
                    'billing_matchmaker' => $data['matchmaker'],
                    'billing_method' => $data['method'] ?? null,
                    'billing_next_date' => $data['next_date'] ?? null,
                    'billing_credit_card_id' => $data['credit_card_id'] ?? null,
                ]);

                $action->successNotificationTitle('הפרטים נשמרו בהצלחה');

                $action->success();
            })
            ->form(function ($form) {
                return $form->schema([
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
                    ...Subscription::formFields(),
                ]);
            });
    }
}
