<?php

namespace App\Filament\Clusters\Settings\Resources\Banners\Utilities;

use App\Models\Banner;

class BannerStorage
{
    public static function getActiveBanners()
    {
        return cache()->remember('banners', now()->endOfDay(), function () {
            return Banner::query()
                ->whereIsActive(true)
                ->where('published_at', '<=', now())
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->get();
        });
    }
}
