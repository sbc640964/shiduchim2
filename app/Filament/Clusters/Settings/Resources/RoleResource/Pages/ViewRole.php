<?php

namespace app\Filament\Clusters\Settings\Resources\RoleResource\Pages;

use app\Filament\Clusters\Settings\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRole extends ViewRecord
{
    protected static string $resource = RoleResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
