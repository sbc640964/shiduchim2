<?php

namespace App\Filament\Clusters\Settings\Resources\Schools\Pages;

use App\Filament\Clusters\Settings\Resources\Schools\SchoolResource;
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
