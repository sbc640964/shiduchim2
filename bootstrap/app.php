<?php

use App\Actions\RunPayments;
use App\Models\Call;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Sentry\Laravel\Integration;
use Sentry\State\Scope;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->call(new RunPayments)->dailyAt('05:00')->name('run-payments');
        $schedule->command('telescope:prune --hours=48')->daily();
        $schedule->call(function () {
            Call::checkAndFinishOldCalls();
        })->everyTwoMinutes();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->reportable(function (Throwable $e) {
            \Sentry\configureScope(function (Scope $scope) use($e): void {
                if (method_exists($e, 'getModel')) {
                    $model = $e->getModel();
                    $scope->setContext('model', $model?->toArray() ?? null);
                }
            });
        });

        Integration::handles($exceptions);
    })->create();
