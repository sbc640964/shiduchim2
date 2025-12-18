<?php

namespace App\Filament\Resources\People\Pages;

use App\Filament\Resources\People\PersonResource;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class Comments extends ViewRecord
{
    protected static string $resource = PersonResource::class;

    protected static ?string $title = 'תגובות';
    protected static ?string $navigationLabel = 'תגובות';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::ChatBubbleLeftRight;

    protected function getHeaderActions(): array
    {
        return [
//            EditAction::make(),
        ];
    }
}
