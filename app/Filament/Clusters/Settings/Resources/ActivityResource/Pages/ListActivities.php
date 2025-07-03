<?php

namespace App\Filament\Clusters\Settings\Resources\ActivityResource\Pages;

use App\Filament\Clusters\Settings\Resources\ActivityResource;
use Fibtegis\FilamentInfiniteScroll\Concerns\InteractsWithInfiniteScroll;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
