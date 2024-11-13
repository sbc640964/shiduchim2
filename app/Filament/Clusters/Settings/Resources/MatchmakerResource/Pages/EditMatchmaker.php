<?php

namespace App\Filament\Clusters\Settings\Resources\MatchmakerResource\Pages;

use App\Filament\Clusters\Settings\Resources\MatchmakerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMatchmaker extends EditRecord
{
    protected static string $resource = MatchmakerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
