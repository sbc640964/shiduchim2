<?php

namespace App\Policies;

use App\Models\NoteComment;
use App\Models\User;

class NoteCommentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, NoteComment $noteComment): bool
    {
        return $noteComment->note->isVisibleTo($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, NoteComment $noteComment): bool
    {
        return $noteComment->author_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, NoteComment $noteComment): bool
    {
        return $noteComment->author_id === $user->id
            || $noteComment->note->isOwnedBy($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, NoteComment $noteComment): bool
    {
        return $this->delete($user, $noteComment);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, NoteComment $noteComment): bool
    {
        return $this->delete($user, $noteComment);
    }
}
