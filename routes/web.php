<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthLdapController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\TrashController;

// LOGIN ROUTE
Route::get('/',  [AuthLdapController::class, 'showLogin'])->name('ldap.login');
Route::post('/ldap-login', [AuthLdapController::class, 'login'])->name('ldap.login.post')->middleware('throttle:ldap-login');;

Route::middleware('ldap.session')->group(function () {
    // DASHBOARD ROUTE
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    // MASTER DATA ROUTE
    Route::prefix('master-data')->name('master.')->group(function () {
        // FRONT END MASTER DATA
        Route::get('/master-sumber', [MasterDataController::class, 'master_sumber'])->name('sumber');
        Route::get('/master-transaction', [MasterDataController::class, 'master_transaction'])->name('transaction');
        Route::get('/master-asset-type', [MasterDataController::class, 'master_asset_type'])->name('asset_type');
        Route::get('/master-category', [MasterDataController::class, 'master_category'])->name('category');
        Route::get('/master-category-2', [MasterDataController::class, 'master_category_2'])->name('category_2');

        // DATATABLE MASTER DATA
        Route::get('/master-sumber/datatable', [MasterDataController::class, 'master_sumber_data'])->name('sumber.data');
        Route::get('/master-transaction/datatable',  [MasterDataController::class, 'master_transaction_data'])->name('transaction.data');
        Route::get('/master-asset-type/datatable',  [MasterDataController::class, 'master_asset_type_data'])->name('asset_type.data');
        Route::get('/master-category/datatable', [MasterDataController::class, 'master_category_data'])->name('category.data');
        Route::get('/master-category-2/datatable', [MasterDataController::class, 'master_category_2_data'])->name('category_2.data');

        // CRUD MASTER DATA
        // MASTER SUMBER
        Route::post('/master-sumber/save', [MasterDataController::class, 'master_sumber_save'])->name('sumber.save');
        Route::get('/master-sumber/{uuid}', [MasterDataController::class, 'master_sumber_show'])->name('sumber.show');
        Route::delete('/master-sumber/{uuid}', [MasterDataController::class, 'master_sumber_delete'])->name('sumber.delete');
        // MASTER TRANSACTION
        Route::post('/master-transaction/save', [MasterDataController::class, 'master_transaction_save'])->name('transaction.save');
        Route::get('/master-transaction/{uuid}', [MasterDataController::class, 'master_transaction_show'])->name('transaction.show');
        Route::delete('/master-transaction/{uuid}', [MasterDataController::class, 'master_transaction_delete'])->name('transaction.delete');
        // MASTER ASSET TYPE
        Route::post('/master-asset-type/save', [MasterDataController::class, 'master_asset_type_save'])->name('asset_type.save');
        Route::get('/master-asset-type/{uuid}', [MasterDataController::class, 'master_asset_type_show'])->name('asset_type.show');
        Route::delete('/master-asset-type/{uuid}', [MasterDataController::class, 'master_asset_type_delete'])->name('asset_type.delete');
        Route::get('/select-master-asset-type',   [MasterDataController::class, 'select_master_asset_type'])->name('asset_type.options');
        // MASTER CATEGORY
        Route::post('/master-category/save', [MasterDataController::class, 'master_category_save'])->name('category.save');
        Route::get('/master-category/{uuid}', [MasterDataController::class, 'master_category_show'])->name('category.show');
        Route::delete('/master-category/{uuid}', [MasterDataController::class, 'master_category_delete'])->name('category.delete');
        // MASTER CATEGORY 2
        Route::post('/master-category-2/save', [MasterDataController::class, 'master_category_2_save'])->name('category_2.save');
        Route::get('/master-category-2/{uuid}', [MasterDataController::class, 'master_category_2_show'])->name('category_2.show');
        Route::delete('/master-category-2/{uuid}', [MasterDataController::class, 'master_category_2_delete'])->name('category_2.delete');
    });

    // TRASH ROUTE
    Route::prefix('trash')->name('trash.')->group(function () {
        Route::get('/',              [TrashController::class, 'index'])->name('index');
        Route::get('/datatable',     [TrashController::class, 'data'])->name('data');
        Route::post('/{type}/{id}/restore', [TrashController::class, 'restore'])->name('restore');
        Route::delete('/{type}/{id}/force', [TrashController::class, 'force'])->name('force');
    });


    // LOGOUT ROUTE
    Route::post('/ldap-logout', [AuthLdapController::class, 'logout'])->name('ldap.logout');
});
require __DIR__ . '/auth.php';
