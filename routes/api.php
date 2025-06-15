<?php

use App\Http\Controllers\EtaSpotController;
use App\Http\Controllers\RideSystemsController;
use App\Http\Controllers\SportsController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'sports',
], function () {
    Route::group([
        'prefix' => 'schedule'
    ], function() {
        Route::get('roster/{campus}', [SportsController::class, "getRoster"]);
        Route::get('news/{campus}', [SportsController::class, "getNews"]);
        Route::get('assets/{campus}', [SportsController::class, "getAssets"]);
        Route::get('score/{campus}/{game_id}', [SportsController::class, "getScore"]);
        Route::get('{campus}', [SportsController::class, "getSchedule"]);
    });
    Route::get('roster/{campus}', [SportsController::class, "getRoster"]);
    Route::get('news/{campus}', [SportsController::class, "getNews"]);
    Route::get('assets/{campus}', [SportsController::class, "getAssets"]);
    Route::get('score/{campus}/{game_id}', [SportsController::class, "getScore"]);
});

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
