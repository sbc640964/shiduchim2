<?php

namespace App\Filament\Clusters\Settings\Resources\Banners\Pages;

use App\Filament\Clusters\Settings\Resources\Banners\BannerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBanner extends EditRecord
{
    protected static string $resource = BannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
