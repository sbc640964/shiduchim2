<?php

namespace App\Filament\Clusters\Settings\Resources\Banners\Pages;

use App\Filament\Clusters\Settings\Resources\Banners\BannerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBanners extends ListRecords
{
    protected static string $resource = BannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->after(fn () => cache()->forget('banners'))
                ->mutateDataUsing(fn (array $data) => [
                    ...$data,
                    'published_at' => $data['published_at'] ?? now(),
                ])
                ->slideOver(),
        ];
    }
}
