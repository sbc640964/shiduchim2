<?php

namespace app\Filament\Clusters\Settings\Resources\FormResource\Pages;

use Filament\Actions\DeleteAction;
use app\Filament\Clusters\Settings\Resources\FormResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditForm extends EditRecord
{
    protected static string $resource = FormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
