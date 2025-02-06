<?php

use App\Models\Discussion;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});


Broadcast::channel('chat.room.{discussion}', function (User $user, Discussion $discussion) {
    return $discussion->usersAssigned->contains($user);
});

Broadcast::channel('extension.{extension}', function (User $user, $extension) {
    return $user->ext === (int) $extension;
});
