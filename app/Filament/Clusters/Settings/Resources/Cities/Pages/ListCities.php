<?php

namespace App\Filament\Clusters\Settings\Resources\Cities\Pages;

use App\Filament\Clusters\Settings\Resources\Cities\CityResource;
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
