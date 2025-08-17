<?php

namespace app\Filament\Clusters\Settings\Resources\FormResource\Pages;

use Filament\Actions\CreateAction;
use app\Filament\Clusters\Settings\Resources\FormResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListForms extends ListRecords
{
    protected static string $resource = FormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
