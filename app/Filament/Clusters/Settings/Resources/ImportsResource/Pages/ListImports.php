<?php

namespace App\Filament\Clusters\Settings\Resources\ImportsResource\Pages;

use App\Filament\Clusters\Settings\Resources\ImportsResource;
use App\Services\Imports\Students\BatchStore;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListImports extends ListRecords
{
    protected static string $resource = ImportsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->action(function (array $data) {

                    $mapping = \Arr::except($data, ['type', 'file']);
                    
                    match ($data['type']) {
                        'students' => BatchStore::make($data['file'], $mapping)->handle(),
                        default => null,
                    };
                }),
        ];
    }
}
