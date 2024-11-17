<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('webhook/gis', \App\Http\Controllers\WebhookGisController::class);
