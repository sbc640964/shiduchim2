<?php

namespace app\Filament\Clusters\Settings\Resources\CityResource\Pages;

use app\Filament\Clusters\Settings\Resources\CityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCities extends ListRecords
{
    protected static string $resource = CityResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
