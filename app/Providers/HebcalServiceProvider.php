<?php

namespace App\Providers;

use App\Helpers\Hebcal;
use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class HebcalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('hebcal', function () {
            return new Hebcal(
                new \Carbon\Carbon()
            );
        });
    }

    public function boot(): void
    {
        Carbon::macro('hebcal', function (): Hebcal {
            return new Hebcal($this);
        });
    }
}
