<?php

namespace App\Filament\Clusters\Settings\Resources\CustomNotifications\Pages;

use App\Filament\Clusters\Settings\Resources\CustomNotifications\CustomNotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomNotification extends CreateRecord
{
    protected static string $resource = CustomNotificationResource::class;
}
