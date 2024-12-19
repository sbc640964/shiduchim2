<?php

use App\Actions\RunPayments;
use App\Models\Call;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

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
        $schedule->command('telescope:prune --hours=72')->daily();
        $schedule->call(function () {
            Call::query()
                ->whereNull('finished_at')
                ->where('created_at', '<', now()->subMinutes(2))->update([
                    'finished_at' => now(),
                ]);
        })->everyTwoMinutes();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
