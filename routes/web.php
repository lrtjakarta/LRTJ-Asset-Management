<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthLdapController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterDataController;

Route::get('/ldap-login',  [AuthLdapController::class, 'showLogin'])->name('ldap.login');
Route::post('/ldap-login', [AuthLdapController::class, 'login'])->name('ldap.login.post');

Route::middleware('ldap.session')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    // MASTER DATA ROUTE
    Route::get('/master-data/master-sumber', [MasterDataController::class, 'master_sumber'])->name('master.sumber');
    Route::post('/ldap-logout', [AuthLdapController::class, 'logout'])->name('ldap.logout');
});
require __DIR__.'/auth.php';