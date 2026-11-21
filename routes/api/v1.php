<?php

use App\Http\Controllers\API\V1\ObservationController;
use App\Http\Controllers\API\V1\SendController;
use App\Http\Controllers\API\V1\SiteController;
use Illuminate\Support\Facades\Route;

// Route::addRoute(['GET', 'POST'], '/send', SendController::class);

Route::prefix('observation')
    ->controller(ObservationController::class)
    ->group(function () {
        Route::get('', 'index');
    });

Route::prefix('site')
    ->controller(SiteController::class)
    ->group(function () {
        Route::get('', 'index');
        Route::prefix('{site}')
            ->group(function () {
                Route::get('', 'show');
                Route::get('latest', 'latest');
                Route::get('daily', 'daily');
                Route::get('daily-local', 'dailyLocal');
                Route::get('graph', 'graph');
            });
    });
