<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ContactSubmissionController;

Route::post('/external/applications', [ApplicationController::class, 'externalStore'])
    ->middleware('throttle:60,1')
    ->name('api.external.applications');

Route::post('/external/contact-submissions', [ContactSubmissionController::class, 'externalStore'])
    ->middleware('throttle:60,1')
    ->name('api.external.contact-submissions');

Route::get('/api/server-time', function () {
    return response()->json(['datetime' => now('Africa/Nairobi')->toIso8601String()]);
})->name('api.server-time');

