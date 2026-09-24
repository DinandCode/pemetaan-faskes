<?php

use App\Http\Controllers\AnalisisEventController;
use App\Http\Controllers\FaskesController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeocodeController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/



Route::get('/geocode/search', [GeocodeController::class, 'search'])
    ->middleware('throttle:20,1'); // maks 20 request/menit per IP, cegah spam ke Nominatim
    
// Endpoint Analisis Event Spasial & Rute OSRM
Route::match(['get', 'post'], '/analisis-event', [AnalisisEventController::class, 'analisis']);

// Endpoint Faskes
Route::get('/faskes/terdekat', [FaskesController::class, 'terdekat']);
Route::match(['get', 'post'], '/faskes/filter', [FaskesController::class, 'filter']);
Route::get('/faskes', [FaskesController::class, 'index']);
Route::get('/faskes/{id}', [FaskesController::class, 'show']);
Route::post('/faskes', [FaskesController::class, 'store']);
Route::delete('/faskes/{id}', [FaskesController::class, 'destroy']);
