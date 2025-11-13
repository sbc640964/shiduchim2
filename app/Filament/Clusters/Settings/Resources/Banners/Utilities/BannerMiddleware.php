<?php

namespace App\Filament\Clusters\Settings\Resources\Banners\Utilities;

use Closure;
use Filament\Support\Facades\FilamentView;
use Illuminate\Http\Request;

class BannerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $banners = BannerStorage::getActiveBanners();

        foreach ($banners as $banner) {
            foreach ($banner->locations as $location) {
                FilamentView::registerRenderHook(
                    $location,
                    function (array $data, array $scopes) use ($location, $banner) {
                        return view('banner', [
                            'banner' => $banner,
                            'location' => $location,
                        ]);
                    },
                    (collect($banner->locations_data ?? [])->firstWhere('location', $location) ?? [])['scope'] ?? null
                );
            }
        }

        return $next($request);
    }
}
