<?php

namespace App\Filament\Resources\GoldListResource\Pages;

use App\Filament\Resources\GoldListResource;
use App\Models\Subscriber;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListGoldLists extends ListRecords
{
    protected static string $resource = GoldListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
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
