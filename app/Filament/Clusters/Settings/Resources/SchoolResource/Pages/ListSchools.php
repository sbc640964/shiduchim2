<?php

namespace app\Filament\Clusters\Settings\Resources\SchoolResource\Pages;

use app\Filament\Clusters\Settings\Resources\SchoolResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSchools extends ListRecords
{
    protected static string $resource = SchoolResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth('sm'),
            //            ActionGroup::make([
            //                ImportAction::make()
            //                    ->importer(SchoolImporter::class)
            //                    ->chunkSize(250),
            //            ]),
        ];
    }
}
