<?php

namespace App\Filament\Clusters\Settings\Resources\MatchmakerResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\Settings\Resources\MatchmakerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMatchmakers extends ListRecords
{
    protected static string $resource = MatchmakerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth('sm'),
        ];
    }
}
