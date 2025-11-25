<?php

use App\Http\Controllers\Api\AssetsApi;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MasterDataApi;
use App\Http\Controllers\Api\TransferApi;
use App\Http\Controllers\Api\DisposalApi;
use App\Http\Controllers\Api\ReturnHistoryApi;
use App\Http\Controllers\Api\StorageApi;

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
        Route::get('master-data/master-transaction', [MasterDataApi::class, 'master_transaction']);
        Route::get('master-data/master-asset-class', [MasterDataApi::class, 'master_asset_class']);
        Route::get('master-data/users', [MasterDataApi::class, 'users']);
        
        // ASSET
        Route::get('/assets', [AssetsApi::class, 'index']);
        
        // TRANSFERS
        Route::get('/transfers', [TransferApi::class, 'index']);
        Route::post('/transfers', [TransferApi::class, 'store']);
        Route::get('/transfers/download-form', [TransferApi::class, 'downloadForms'])->name('transfers.downloadForms');
        
        // DISPOSAL
        Route::get('/disposals', [DisposalApi::class, 'index']);
        Route::post('/disposals', [DisposalApi::class, 'store']);
        Route::get('/disposals/{disposal}/ba', [DisposalApi::class, 'downloadBa'])
            ->name('disposals.ba.download');
        Route::get('/disposals/{disposal}/form', [DisposalApi::class, 'downloadForm'])
            ->name('disposals.form.download');

        // RETURN HISTORY
        Route::get('/returns', [ReturnHistoryApi::class, 'index']);
        Route::post('/returns', [ReturnHistoryApi::class, 'store']);
        Route::get('/returns/options', [ReturnHistoryApi::class, 'options']);
        
        // FILES/STORAGE
        Route::get('/files/{kind}/{uuid}/download', [StorageApi::class, 'download'])->whereIn('kind', ['disposal', 'transfer'])
            ->name('files.download.kind');
        Route::get('/files/manifest', [StorageApi::class, 'manifest'])->name('files.manifest');
        Route::get('/files/{uuid}/download', [StorageApi::class, 'downloadLegacy'])
            ->name('files.download');
        
            // LOGOUT
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
    });
});
