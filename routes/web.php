
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\ReservationController;

// Welcome page
Route::get('/', function () {
    return view('welcome');
});

// Divisions
Route::get('/api/divisions', [DivisionController::class, 'index']);

// Reservations
Route::get('/api/reservations', [ReservationController::class, 'index']);
Route::get('/api/reservations/calendar', [ReservationController::class, 'calendar']);
Route::post('/api/reservations/check-conflict', [ReservationController::class, 'checkConflict']);
Route::post('/api/reservations', [ReservationController::class, 'store']);
Route::get('/api/reservations/{reservation}/pdf', [ReservationController::class, 'pdf']);
Route::get('/api/reservations/{reservation}', [ReservationController::class, 'show']);
Route::patch('/api/reservations/{reservation}/status', [ReservationController::class, 'updateStatus']);
Route::get('/api/reservations/track/{reference}', [ReservationController::class, 'track']);
