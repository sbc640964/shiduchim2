<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Person;
use Illuminate\Auth\Access\HandlesAuthorization;

class PersonPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_person');
    }

    public function view(AuthUser $authUser, Person $person): bool
    {
        return $authUser->can('view_person');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_person');
    }

    public function update(AuthUser $authUser, Person $person): bool
    {
        return $authUser->can('update_person');
    }

    public function delete(AuthUser $authUser, Person $person): bool
    {
        return $authUser->can('delete_person');
    }

    public function restore(AuthUser $authUser, Person $person): bool
    {
        return $authUser->can('restore_person');
    }

    public function forceDelete(AuthUser $authUser, Person $person): bool
    {
        return $authUser->can('force_delete_person');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_person');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_person');
    }

    public function replicate(AuthUser $authUser, Person $person): bool
    {
        return $authUser->can('replicate_person');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_person');
    }

}