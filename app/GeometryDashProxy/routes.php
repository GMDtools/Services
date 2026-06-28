<?php

use App\GeometryDashProxy\Controllers\GeometryDashAccountDataProxyController;
use App\GeometryDashProxy\Controllers\GeometryDashCustomContentProxyController;
use App\GeometryDashProxy\Controllers\GeometryDashLevelProxyController;
use App\GeometryDashProxy\Controllers\GeometryDashProxyController;
use App\GeometryDashProxy\Controllers\GeometryDashSongProxyController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'GeometryDashProxy',
    'as' => 'geometry_dash_proxy.',
], function () {
    Route::post('/getAccountURL.php', [GeometryDashAccountDataProxyController::class, 'handleGetURL']);

    Route::group([
        'prefix' => '_account',
        'as' => 'account.',
    ], function () {
        Route::group([
            'prefix' => '{accountID}:{type}',
            'where' => [
                'accountID' => '\\d+',
                'type' => '\\d+',
            ],
        ], function () {
            Route::post('/{endpoint}.php', [GeometryDashAccountDataProxyController::class, 'handle'])
                ->where('endpoint', '.*');
        });
    });

    Route::post('/getGJSongInfo.php', [GeometryDashSongProxyController::class, 'handleGameInfoEndpoint']);
    Route::post('/getGJLevels21.php', [GeometryDashLevelProxyController::class, 'handleGameListEndpoint']);
    Route::post('/downloadGJLevel22.php', [GeometryDashLevelProxyController::class, 'handleGameDownloadEndpoint']);

    Route::group([
        'prefix' => '_song',
        'as' => 'song.',
    ], function () {
        Route::group([
            'prefix' => '{id}',
            'where' => [
                'id' => '\\d+',
            ],
        ], function () {
            // Route::get('info', [GeometryDashSongProxyController::class, 'info']);
            // Route::get('object', [GeometryDashSongProxyController::class, 'object']);
            Route::get('download', [GeometryDashSongProxyController::class, 'download']);
        });
    });

    Route::post('/getCustomContentURL.php', [GeometryDashCustomContentProxyController::class, 'handleGetURL']);

    Route::group([
        'prefix' => '_custom-content',
        'as' => 'custom-content.',
    ], function () {
        Route::get('/{path}', [GeometryDashCustomContentProxyController::class, 'handle'])
            ->where('path', '.*');
    });

    Route::post('/{endpoint}.php', [GeometryDashProxyController::class, 'handleGameEndpoint'])
        ->where('endpoint', '.*');
});
