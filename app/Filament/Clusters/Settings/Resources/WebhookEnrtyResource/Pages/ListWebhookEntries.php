<?php

namespace App\Filament\Clusters\Settings\Resources\WebhookEnrtyResource\Pages;

use App\Filament\Clusters\Settings\Resources\WebhookEntryResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListWebhookEntries extends ListRecords
{
    protected static string $resource = WebhookEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }


    public function getTabs(): array
    {
        return [
            'all' => Tab::make('הכל'),
            'completed' => Tab::make('הושלמו')
                ->query(fn (Builder $query) => $query->where('is_completed', true)),
            'not_completed' => Tab::make('לא הושלמו')
                ->query(fn (Builder $query) => $query->where('is_completed', false))
        ];
    }

}
