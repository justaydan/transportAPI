<?php

use App\Http\Controllers\TransportController;
use App\Http\Middleware\AuthenticateWithApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateWithApiKey::class)->group(function () {
    Route::post('/calculate-transport', [TransportController::class, 'calculate']);
});
