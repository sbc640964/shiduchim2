<?php

namespace App\Filament\Clusters\Settings\Resources\Activities\Pages;

use App\Filament\Clusters\Settings\Resources\Activities\ActivityResource;
use Fibtegis\FilamentInfiniteScroll\Concerns\InteractsWithInfiniteScroll;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
