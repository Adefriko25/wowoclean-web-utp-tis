<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContainerController;
use App\Http\Controllers\AuthController;

Route::prefix('v1')->group(function () {

    // Auth (public)
    Route::post('/login', [AuthController::class, 'login']);

    // Auth (protected)
    Route::middleware('auth:api')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // Gateway routes (protected + role check untuk write)
    Route::prefix('gateway')->middleware('auth:api')->group(function () {

        // Semua user bisa akses
        Route::get('/containers/search', [ContainerController::class, 'search']);
        Route::get('/containers', [ContainerController::class, 'index']);
        Route::get('/containers/{id}/logs', [ContainerController::class, 'logs']);

        // Admin only
        Route::middleware('role:admin')->group(function () {
            Route::post('/containers', [ContainerController::class, 'store']);
            Route::patch('/containers/{id}/archive', [ContainerController::class, 'archive']);
            Route::delete('/containers/{id}', [ContainerController::class, 'destroy']);
        });
    });
});