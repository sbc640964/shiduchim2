<?php

namespace App\Providers;

use App\Models\User;
use Auth;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Sentry\State\Scope;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RequestException::dontTruncate();

        Gate::define('banner-manager', function (User $user) {
            return $user->can('banner_manager');
        });

        if (app()->bound('sentry')) {
            \Sentry\configureScope(function (Scope $scope): void {
                if (Auth::check()) {
                    $user = Auth::user();
                    $scope->setUser([
                        'id' => $user->id,
                        'email' => $user->email,
                        'username' => $user->name, // כאן תוודא שהשדה הוא 'name' או השם המתאים במודל
                    ]);
                }
            });
        }
    }
}
