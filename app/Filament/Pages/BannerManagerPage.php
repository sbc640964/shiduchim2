<?php

namespace App\Filament\Pages;
use App\Filament\Clusters\Settings;
use Illuminate\Support\Facades\Gate;
//use Kenepa\Banner\BannerPlugin;
//use Kenepa\Banner\Livewire\BannerManagerPage as Page;

class BannerManagerPage //extends Page
{
    protected static ?string $cluster = Settings::class;

    public static function canAccess(): bool
    {
        $bannerManagerPermission = null; //BannerPlugin::get()->getBannerManagerAccessPermission();

        if ($bannerManagerPermission) {
            return Gate::allows($bannerManagerPermission);
        }

        return true;
    }
}
