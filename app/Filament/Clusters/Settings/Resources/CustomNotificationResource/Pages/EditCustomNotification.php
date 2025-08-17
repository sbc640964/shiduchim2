<?php

namespace App\Filament\Clusters\Settings\Resources\CustomNotificationResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\Settings\Resources\CustomNotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomNotification extends EditRecord
{
    protected static string $resource = CustomNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
