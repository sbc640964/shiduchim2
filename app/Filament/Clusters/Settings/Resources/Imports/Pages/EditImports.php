<?php

namespace App\Filament\Clusters\Settings\Resources\Imports\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\Settings\Resources\Imports\ImportsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditImports extends EditRecord
{
    protected static string $resource = ImportsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
