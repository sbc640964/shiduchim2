<?php

namespace App\Filament\Clusters\Settings\Resources\TimeSheetsResource\Pages;

use App\Filament\Clusters\Settings\Resources\TimeSheetsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTimeSheets extends EditRecord
{
    protected static string $resource = TimeSheetsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
