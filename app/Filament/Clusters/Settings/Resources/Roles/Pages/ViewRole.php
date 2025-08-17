<?php

namespace App\Filament\Clusters\Settings\Resources\Roles\Pages;

use Filament\Actions\EditAction;
use App\Filament\Clusters\Settings\Resources\Roles\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRole extends ViewRecord
{
    protected static string $resource = RoleResource::class;

    protected function getActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
