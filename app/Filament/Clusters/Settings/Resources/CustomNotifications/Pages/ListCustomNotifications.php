<?php

namespace App\Filament\Clusters\Settings\Resources\CustomNotifications\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\Settings\Resources\CustomNotifications\CustomNotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomNotifications extends ListRecords
{
    protected static string $resource = CustomNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('הוסף הודעה'),
        ];
    }
}
