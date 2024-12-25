<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('webhook/gis', \App\Http\Controllers\WebhookGisController::class);

Route::get('report/{user}', function (User $user) {

    $list = $user->load([
        'subscribers.proposals.diaries.call',
        'subscribers.father.city',
        'subscribers.father.phones',
        'subscribers.mother.phones',
        'subscribers.proposals.people.parentsFamily.people',
        'subscribers.proposals.people.parentsFamily.city',
        'subscribers.proposals.people.parentsFamily.phones',
        'subscribers.proposals.people.contacts.phones',
        'subscribers.proposals.people.father.city',
        'subscribers.proposals.people.father.phones',
        'subscribers.proposals.people.mother.phones',
        'subscribers.parentsFamily.people',
        'subscribers.parentsFamily.city',
        'subscribers.parentsFamily.phones',
        'subscribers.contacts.phones',
        'subscribers.city',
    ]);

    return view('report', [
        'user' => $user,
    ]);
});
