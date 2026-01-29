<?php
// routes/api.php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MasterDataApi;
use App\Http\Controllers\Api\AssetsApi;
use App\Http\Controllers\Api\TransferApi;
use App\Http\Controllers\Api\DisposalApi;
use App\Http\Controllers\Api\ReturnHistoryApi;
use App\Http\Controllers\Api\StockOpnameApi;
use App\Http\Controllers\Api\StorageApi;
use App\Http\Controllers\Api\RfidApiController;

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
        Route::get('master-data/master-sumber', [MasterDataApi::class, 'master_sumber']);
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
        Route::post('/transfers/approve', [TransferApi::class, 'approve'])->name('transfers.approve');
        Route::post('/transfers/reject', [TransferApi::class, 'reject'])->name('transfers.reject');

        // DISPOSAL
        Route::get('/disposals', [DisposalApi::class, 'index']);
        Route::post('/disposals', [DisposalApi::class, 'store']);
        Route::post('/disposals/approve', [DisposalApi::class, 'approve'])->name('disposals.approve');
        Route::post('/disposals/reject', [DisposalApi::class, 'reject'])->name('disposals.reject');
        Route::get('/disposals/form', [DisposalApi::class, 'downloadForm'])->name('disposals.form.download');
        Route::get('/disposals/ba', [DisposalApi::class, 'downloadBa'])->name('disposals.ba.download');

        // RETURN HISTORY
        Route::get('/returns', [ReturnHistoryApi::class, 'index']);
        Route::post('/returns', [ReturnHistoryApi::class, 'store']);
        Route::get('/returns/options', [ReturnHistoryApi::class, 'options']);

        // STOCK OPNAME
        Route::get('/stock-opname', [StockOpnameApi::class, 'index'])->name('stockopname.api.index');
        Route::post('/stock-opname/transfer', [StockOpnameApi::class, 'storeTransfer'])->name('stockopname.api.transfer.store');
        Route::post('/stock-opname/disposal', [StockOpnameApi::class, 'storeDisposal'])->name('stockopname.api.disposal.store');
        Route::patch('/stock-opname/projects/{projectUuid}/close',  [StockOpnameApi::class, 'projectClose']);
        Route::patch('/stock-opname/projects/{projectUuid}/reopen', [StockOpnameApi::class, 'projectReopen']);
        Route::patch('/stock-opname/projects/{projectUuid}/done', [StockOpnameApi::class, 'stockOpnameDone']);
        Route::get('/stock-opname/projects/{projectUuid}', [StockOpnameApi::class, 'projectShow']);
        Route::get('/stock-opname/projects', [StockOpnameApi::class, 'projects']);


        // PREVIEW (query param: asset_uuid=...)
        Route::get('stock-opname/preview/transfer-form', [StockOpnameApi::class, 'previewTransferForm'])
            ->name('api.stockopname.preview.transfer-form');
        Route::get('stock-opname/preview/disposal-form', [StockOpnameApi::class, 'previewDisposalForm'])
            ->name('api.stockopname.preview.disposal-form');
        Route::get('stock-opname/preview/disposal-ba', [StockOpnameApi::class, 'previewDisposalBa'])
            ->name('api.stockopname.preview.disposal-ba');


        // FILES/STORAGE
        Route::get('/files/{kind}/{uuid}/download', [StorageApi::class, 'download'])
            ->whereIn('kind', ['disposal', 'transfer'])
            ->name('files.download.kind');
        Route::get('/files/manifest', [StorageApi::class, 'manifest'])->name('files.manifest');
        Route::get('/files/{uuid}/download', [StorageApi::class, 'downloadLegacy'])->name('files.download');

        // RFID
        Route::post('/rfid/lookup', [RfidApiController::class, 'lookupByEpc']);

        // LOGOUT
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
    });
});
