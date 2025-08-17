<?php

namespace App\Filament\Clusters\Settings\Resources\Matchmakers\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\Settings\Resources\Matchmakers\MatchmakerResource;
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
