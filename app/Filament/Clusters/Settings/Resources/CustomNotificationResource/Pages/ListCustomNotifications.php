<?php

namespace App\Filament\Clusters\Settings\Resources\CustomNotificationResource\Pages;

use App\Filament\Clusters\Settings\Resources\CustomNotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomNotifications extends ListRecords
{
    protected static string $resource = CustomNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('הוסף הודעה'),
        ];
    }
}
