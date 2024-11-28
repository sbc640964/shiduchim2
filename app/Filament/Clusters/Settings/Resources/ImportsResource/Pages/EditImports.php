<?php

namespace App\Filament\Clusters\Settings\Resources\ImportsResource\Pages;

use App\Filament\Clusters\Settings\Resources\ImportsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditImports extends EditRecord
{
    protected static string $resource = ImportsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
