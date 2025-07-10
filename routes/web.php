<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});



Route::get('/test-pm', function () {
    sleep(10); // מדמה עבודה של FPM
    return 'ok';
});

Route::get('webhook/gis', \App\Http\Controllers\WebhookGisController::class);
