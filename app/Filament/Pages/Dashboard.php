<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    public function getTitle(): string|Htmlable
    {
        return 'לוח בקרה';
    }

    /**
     * Get the subheading for the dashboard
     * Greet the user based on the time of day
     *
     * @return string|Htmlable|null
     */
    public function getSubheading(): string|Htmlable|null
    {
        $hour = date('H');

        if ($hour < 11) {
            $greeting = 'בוקר טוב';
        } elseif ($hour < 16) {
            $greeting = 'צהריים טובים';
        } elseif ($hour < 17) {
            $greeting = 'אחר הצהריים טובים';
        } else {
            $greeting = 'ערב טוב';
        }

        return $greeting.', '.auth()->user()->name;
    }
}
