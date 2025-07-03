<?php

namespace App\Filament\Clusters\Settings\Resources\ActivityResource\Pages;

use App\Filament\Clusters\Settings\Resources\ActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivity extends EditRecord
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
