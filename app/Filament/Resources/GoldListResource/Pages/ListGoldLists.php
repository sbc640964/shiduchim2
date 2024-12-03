<?php

namespace App\Filament\Resources\GoldListResource\Pages;

use App\Filament\Resources\GoldListResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGoldLists extends ListRecords
{
    protected static string $resource = GoldListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
