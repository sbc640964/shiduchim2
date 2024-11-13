<?php

namespace App\Filament\Clusters\Settings\Resources\CallsDiariesResource\Pages;

use App\Filament\Clusters\Settings\Resources\CallsDiariesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCallsDiary extends EditRecord
{
    protected static string $resource = CallsDiariesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
