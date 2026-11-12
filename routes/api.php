<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\ReservationController;

/*
|--------------------------------------------------------------------------
| Add these routes to your project's routes/api.php
| NOTE: the static routes (calendar, track, check-conflict) must stay ABOVE
| /reservations/{reservation}, otherwise "calendar" is read as an id.
|--------------------------------------------------------------------------
*/

Route::get('/divisions', [DivisionController::class, 'index']);

Route::get('/reservations', [ReservationController::class, 'index']);
Route::get('/reservations/calendar', [ReservationController::class, 'calendar']);
Route::get('/reservations/track/{reference}', [ReservationController::class, 'track']);
Route::post('/reservations/check-conflict', [ReservationController::class, 'checkConflict']);
Route::post('/reservations', [ReservationController::class, 'store']);
Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
Route::get('/reservations/{reservation}/pdf', [ReservationController::class, 'pdf']);
Route::patch('/reservations/{reservation}/status', [ReservationController::class, 'updateStatus']);
