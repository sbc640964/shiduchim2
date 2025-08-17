<?php

namespace App\Filament\Clusters\Settings\Resources\CallsDiariesResource\Pages;

use Filament\Actions\Action;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Clusters\Settings\Resources\CallsDiariesResource;
use App\Models\Call;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCallsDiaries extends ListRecords
{
    protected static string $resource = CallsDiariesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('update_models_phones')
                ->successNotificationTitle('טלפונים עודכנו')
                ->action(function (Action $action) {
                    Call::updateModelPhones();
                    $action->success();
                })
                ->label('עדכן טלפונים'),
        ];
    }

    public function getTabs(): array
    {
        return [
            Tab::make('הכל'),
            Tab::make('לא נענו')
                ->icon('iconsax-bul-call-remove')
                ->modifyQueryUsing(function ($query) {
                    $query->whereNull('started_at');
                }),
            Tab::make('נענו')
                ->icon('iconsax-bul-call-received')
                ->modifyQueryUsing(function ($query) {
                    $query->whereNotNull('started_at');
                }),
            Tab::make('שיחות יוצאות')
                ->icon('iconsax-bul-call-outgoing')
                ->modifyQueryUsing(function ($query) {
                    $query
                        ->whereDirection('outgoing');
                }),
            Tab::make('שיחות נכנסות')
                ->icon('iconsax-bul-call-incoming')
                ->modifyQueryUsing(function ($query) {
                    $query
                        ->whereDirection('incoming');
                }),
        ];
    }
}
