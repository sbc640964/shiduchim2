<?php

namespace App\Filament\Resources\GoldLists\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\GoldLists\GoldListResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGoldList extends EditRecord
{
    protected static string $resource = GoldListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
