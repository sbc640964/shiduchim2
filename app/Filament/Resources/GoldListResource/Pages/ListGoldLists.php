<?php

namespace App\Filament\Resources\GoldListResource\Pages;

use App\Exports\ProposalsExport;
use App\Exports\SubscriptionsExport;
use App\Filament\Resources\GoldListResource;
use App\Jobs\SendNotificationsAfterExportDataJob;
use App\Models\Subscriber;
use App\Notifications\NotifyUserOfCompletedExportNotification;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ListGoldLists extends ListRecords
{
    protected static string $resource = GoldListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ActionGroup::make([
                Action::make('export')
                    ->hidden(!auth()->user()->can('students_subscriptions'))
                    ->label('יצוא')
                    ->successNotification(Notification::make()
                        ->title('הקובץ בבישול...')
                        ->body('אנחנו נעדכן אותך ברגע שהוא יהיה מוכן')
                        ->success()
                    )->action(function (Action $action) {

//                        return Excel::download(new SubscriptionsExport(auth()->user()), 'subscriptions.xlsx');
                        $fileName = 'subscriptions-'.now()->format('Y-m-d-His-').auth()->user()->id.'.xlsx';

                        Excel::queue(
                            new SubscriptionsExport(auth()->user()),
                            "exports/$fileName",
                            's3'
                        )->chain([
                            new SendNotificationsAfterExportDataJob(auth()->user(), $fileName)
                        ]);

                        $action->success();
                    })
                    ->icon('iconsax-bul-document-download')
            ])
        ];
    }

    public function getTabs(): array
    {
        $isManager = self::$resource::isManager();

        if(!$isManager) {
            return [];
        }
        return [
            Tab::make('הכל'),
            Tab::make('שגיאת תשלום')
                ->modifyQueryUsing(function (Builder  $query) {
                    $query->where('status', 'active')
                        ->whereRelation('lastTransaction', 'status', 'Error');
                })
                ->badge(
                    Subscriber::whereRelation('lastTransaction', 'status', 'Error')
                        ->where('status', 'active')
                        ->count()
                )->badgeColor('danger')
        ];
    }
}
