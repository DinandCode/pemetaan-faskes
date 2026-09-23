<?php

use App\Http\Controllers\AnalisisEventController;
use App\Http\Controllers\FaskesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Endpoint Analisis Event Spasial & Rute OSRM
Route::match(['get', 'post'], '/analisis-event', [AnalisisEventController::class, 'analisis']);

// Endpoint Faskes
Route::get('/faskes/terdekat', [FaskesController::class, 'terdekat']);
Route::match(['get', 'post'], '/faskes/filter', [FaskesController::class, 'filter']);
Route::get('/faskes', [FaskesController::class, 'index']);
Route::get('/faskes/{id}', [FaskesController::class, 'show']);
Route::post('/faskes', [FaskesController::class, 'store']);
Route::delete('/faskes/{id}', [FaskesController::class, 'destroy']);
