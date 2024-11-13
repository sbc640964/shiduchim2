<?php

namespace App\Policies;

use App\Models\User;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExportPolicy
{
    use HandlesAuthorization;


    public function view(User $user, Export $export): bool
    {
        return $user->hasRole('super_admin');
    }
}
