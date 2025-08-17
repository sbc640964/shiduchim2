<?php

namespace App\Filament\Clusters\Settings\Resources\Cities\Pages;

use App\Filament\Clusters\Settings\Resources\Cities\CityResource;
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
