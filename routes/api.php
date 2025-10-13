<?php

use App\Http\Controllers\Api\AssetsApi;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MasterDataApi;
use App\Http\Controllers\Api\TransferApi;

Route::prefix('v1')->group(function () {
    // PUBLIC
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:api');

    // PROTECTED
    Route::middleware('auth:sanctum')->group(function () {
        // PROFILE LOGIN (SESSION)
        Route::get('me', [AuthController::class, 'me']);
        // MASTER DATA API
        Route::get('master-data/master-user-code', [MasterDataApi::class, 'master_user_code']);
        Route::get('master-data/master-location', [MasterDataApi::class, 'master_location']);
        Route::get('master-data/master-status', [MasterDataApi::class, 'master_status']);
        // ASSET
        Route::get('/assets', [AssetsApi::class, 'index']);
        
        Route::get('/transfers', [TransferApi::class, 'index']);
        // LOGOUT
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
    });
});
