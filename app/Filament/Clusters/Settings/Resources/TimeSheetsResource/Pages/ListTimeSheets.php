<?php

namespace App\Filament\Clusters\Settings\Resources\TimeSheetsResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\Settings\Resources\TimeSheetsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTimeSheets extends ListRecords
{
    protected static string $resource = TimeSheetsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
