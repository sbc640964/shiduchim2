<?php

namespace app\Filament\Clusters\Settings\Resources\RoleResource\Pages;

use Filament\Actions\CreateAction;
use app\Filament\Clusters\Settings\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
