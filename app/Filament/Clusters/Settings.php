<?php

namespace App\Filament\Clusters;

use App\Filament\Clusters\Settings\Resources\CallsDiariesResource;
use App\Filament\Clusters\Settings\Resources\TimeSheetsResource;
use Filament\Clusters\Cluster;

class Settings extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'iconsax-bul-setting-2';

    protected static ?string $title = 'הגדרות';

    public static function canAccessClusteredComponents(): bool
    {
        foreach (static::getClusteredComponents() as $component) {

            if(! auth()->user()->canAccessAllTimeSheets() && $component === TimeSheetsResource::class) {
                continue;
            }

            if(! auth()->user()->canAccessAllCalls() && $component === CallsDiariesResource::class) {
                continue;
            }

            if ($component::canAccess()) {
                return true;
            }
        }

        return false;
    }
}
