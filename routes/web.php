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
        Route::get('/master-sub-category', [MasterDataController::class, 'master_sub_category'])->name('sub_category');
        Route::get('/master-location', [MasterDataController::class, 'master_location'])->name('location');
        Route::get('/master-group_category', [MasterDataController::class, 'master_group_category'])->name('group_category');
        Route::get('/master-uom', [MasterDataController::class, 'master_uom'])->name('uom');
        Route::get('/master-status', [MasterDataController::class, 'master_status'])->name('status');
        Route::get('/master-asset-class', [MasterDataController::class, 'master_asset_class'])->name('asset_class');

        // DATATABLE MASTER DATA
        Route::get('/master-sumber/datatable', [MasterDataController::class, 'master_sumber_data'])->name('sumber.data');
        Route::get('/master-transaction/datatable',  [MasterDataController::class, 'master_transaction_data'])->name('transaction.data');
        Route::get('/master-asset-type/datatable',  [MasterDataController::class, 'master_asset_type_data'])->name('asset_type.data');
        Route::get('/master-category/datatable', [MasterDataController::class, 'master_category_data'])->name('category.data');
        Route::get('/master-category-2/datatable', [MasterDataController::class, 'master_category_2_data'])->name('category_2.data');
        Route::get('/master-sub-category/datatable', [MasterDataController::class, 'master_sub_category_data'])->name('sub_category.data');
        Route::get('/master-location/datatable', [MasterDataController::class, 'master_location_data'])->name('location.data');
        Route::get('/master-group-category/datatable', [MasterDataController::class, 'master_group_category_data'])->name('group_category.data');
        Route::get('/master-uom/datatable', [MasterDataController::class, 'master_uom_data'])->name('uom.data');
        Route::get('/master-status/datatable', [MasterDataController::class, 'master_status_data'])->name('status.data');
        Route::get('/master-asset-class/datatable', [MasterDataController::class, 'master_asset_class_data'])->name('asset_class.data');

        // SELECT MASTER DATA (AJAX)
        Route::get('/select-master-sumber',   [MasterDataController::class, 'select_master_sumber'])->name('sumber.options');
        Route::get('/select-master-transaction',   [MasterDataController::class, 'select_master_transaction'])->name('transaction.options');
        Route::get('/select-master-asset-type',   [MasterDataController::class, 'select_master_asset_type'])->name('asset_type.options');
        Route::get('/select-master-category',   [MasterDataController::class, 'select_master_category'])->name('category.options');
        Route::get('/select-master-category-2',   [MasterDataController::class, 'select_master_category_2'])->name('category_2.options');
        Route::get('/select-master-sub-category',   [MasterDataController::class, 'select_master_sub_category'])->name('sub_category.options');
        Route::get('/select-master-location',   [MasterDataController::class, 'select_master_location'])->name('location.options');
        Route::get('/select-master-group-category',   [MasterDataController::class, 'select_master_group_category'])->name('group_category.options');
        Route::get('/select-master-uom',   [MasterDataController::class, 'select_master_uom'])->name('uom.options');
        Route::get('/select-master-status',   [MasterDataController::class, 'select_master_status'])->name('status.options');
        Route::get('/select-master-asset-class',   [MasterDataController::class, 'select_master_asset_class'])->name('asset_class.options');

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
        // MASTER CATEGORY
        Route::post('/master-category/save', [MasterDataController::class, 'master_category_save'])->name('category.save');
        Route::get('/master-category/{uuid}', [MasterDataController::class, 'master_category_show'])->name('category.show');
        Route::delete('/master-category/{uuid}', [MasterDataController::class, 'master_category_delete'])->name('category.delete');
        // MASTER CATEGORY 2
        Route::post('/master-category-2/save', [MasterDataController::class, 'master_category_2_save'])->name('category_2.save');
        Route::get('/master-category-2/{uuid}', [MasterDataController::class, 'master_category_2_show'])->name('category_2.show');
        Route::delete('/master-category-2/{uuid}', [MasterDataController::class, 'master_category_2_delete'])->name('category_2.delete');
        //  SUB CATEGORY
        Route::post('/master-sub-category/save', [MasterDataController::class, 'master_sub_category_save'])->name('sub_category.save');
        Route::get('/master-sub-category/{uuid}', [MasterDataController::class, 'master_sub_category_show'])->name('sub_category.show');
        Route::delete('/master-sub-category/{uuid}', [MasterDataController::class, 'master_sub_category_delete'])->name('sub_category.delete');
        // MASTER LOCATION
        Route::post('/master-location/save', [MasterDataController::class, 'master_location_save'])->name('location.save');
        Route::get('/master-location/{uuid}', [MasterDataController::class, 'master_location_show'])->name('location.show');
        Route::delete('/master-location/{uuid}', [MasterDataController::class, 'master_location_delete'])->name('location.delete');
        // MASTER GROUP CATEGORY
        Route::post('/master-group-category/save', [MasterDataController::class, 'master_group_category_save'])->name('group_category.save');
        Route::get('/master-group-category/{uuid}', [MasterDataController::class, 'master_group_category_show'])->name('group_category.show');
        Route::delete('/master-group-category/{uuid}', [MasterDataController::class, 'master_group_category_delete'])->name('group_category.delete');
        // MASTER UOM
        Route::post('/master-uom/save', [MasterDataController::class, 'master_uom_save'])->name('uom.save');
        Route::get('/master-uom/{uuid}', [MasterDataController::class, 'master_uom_show'])->name('uom.show');
        Route::delete('/master-uom/{uuid}', [MasterDataController::class, 'master_uom_delete'])->name('uom.delete');
        // MASTER STATUS
        Route::post('/master-status/save', [MasterDataController::class, 'master_status_save'])->name('status.save');
        Route::get('/master-status/{uuid}', [MasterDataController::class, 'master_status_show'])->name('status.show');
        Route::delete('/master-status/{uuid}', [MasterDataController::class, 'master_status_delete'])->name('status.delete');
        // MASTER ASSET CLASS
        Route::post('/master-asset-class/save', [MasterDataController::class, 'master_asset_class_save'])->name('asset_class.save');
        Route::get('/master-asset-class/{uuid}', [MasterDataController::class, 'master_asset_class_show'])->name('asset_class.show');
        Route::delete('/master-asset-class/{uuid}', [MasterDataController::class, 'master_asset_class_delete'])->name('asset_class.delete');
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
