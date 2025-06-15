<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'bus/{campus}',
], function () {
    Route::group([
        'prefix' => 'etaspot',
    ], function () {
        Route::get('announcements', [EtaSpotController::class, "getAnnouncements"]);
        Route::get('buses', [EtaSpotController::class, "getBuses"]);
        Route::get('routes', [EtaSpotController::class, "getRoutes"]);
        Route::get('stops', [EtaSpotController::class, "getStops"]);
        Route::get('eta', [EtaSpotController::class, "getEtas"]);

    });

    Route::group([
        'prefix' => 'ridesystems',
    ], function () {
        Route::get('buses', [RideSystemsController::class, "getBuses"]);
        Route::get('routes', [RideSystemsController::class, "getRoutes"]);
        Route::get('stops', [RideSystemsController::class, "getStops"]);
        Route::get('eta', [RideSystemsController::class, "getEtas"]);
    });
});
