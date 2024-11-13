<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Imports\StudentImporter;
use App\Filament\Resources\StudentResource;
use App\Jobs\ImportCsv;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
            ActionGroup::make([
                ImportAction::make('import-students')
                    ->importer(StudentImporter::class)
                    ->job(ImportCsv::class)
                    ->label('ייבוא תלמידים'),
            ]),
        ];
    }

    //    protected function paginateTableQuery(Builder $query): Paginator|CursorPaginator
    //    {
    //        return $query->fastPaginate($this->getTableRecordsPerPage());
    //    }
}
