<?php

namespace app\Filament\Clusters\Settings\Resources\SchoolResource\Pages;

use app\Filament\Clusters\Settings\Resources\SchoolResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSchool extends EditRecord
{
    protected static string $resource = SchoolResource::class;

    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
