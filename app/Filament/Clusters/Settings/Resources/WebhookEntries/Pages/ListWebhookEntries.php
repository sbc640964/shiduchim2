<?php

namespace App\Filament\Clusters\Settings\Resources\WebhookEntries\Pages;

use App\Filament\Clusters\Settings\Resources\WebhookEntries\WebhookEntryResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
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
