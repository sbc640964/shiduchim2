<?php

namespace App\Filament\Clusters\Settings\Resources\MatchmakerResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\Settings\Resources\MatchmakerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMatchmaker extends EditRecord
{
    protected static string $resource = MatchmakerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
