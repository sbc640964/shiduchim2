<?php

namespace app\Filament\Clusters\Settings\Resources\CityResource\Pages;

use app\Filament\Clusters\Settings\Resources\CityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCity extends EditRecord
{
    protected static string $resource = CityResource::class;

    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
