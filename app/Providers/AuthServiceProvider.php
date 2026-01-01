<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Note;
use App\Models\NoteComment;
use App\Models\User;
use App\Policies\ExportPolicy;
use App\Policies\NoteCommentPolicy;
use App\Policies\NotePolicy;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Export::class => ExportPolicy::class,
        Note::class => NotePolicy::class,
        NoteComment::class => NoteCommentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {

        Gate::before(function (User $user) {
            return $user->hasRole('super_admin') ? true : null;
        });

        Gate::define('viewPulse', function (User $user) {
            return $user->hasRole('super_admin');
        });
    }
}
