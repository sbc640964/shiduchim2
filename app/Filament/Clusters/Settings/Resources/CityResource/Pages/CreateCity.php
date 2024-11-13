<?php

namespace app\Filament\Clusters\Settings\Resources\CityResource\Pages;

use app\Filament\Clusters\Settings\Resources\CityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCity extends CreateRecord
{
    protected static string $resource = CityResource::class;

    protected function getActions(): array
    {
        return [

        ];
    }
}
