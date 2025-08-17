<?php

namespace App\Filament\Clusters\Settings\Resources\TimeSheets\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\Settings\Resources\TimeSheets\TimeSheetsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTimeSheets extends EditRecord
{
    protected static string $resource = TimeSheetsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
