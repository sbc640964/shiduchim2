<?php

namespace app\Filament\Clusters\Settings\Resources\UserResource\Pages;

use Filament\Actions\DeleteAction;
use app\Filament\Clusters\Settings\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
