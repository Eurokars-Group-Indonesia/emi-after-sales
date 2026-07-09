<?php


use Illuminate\Support\Facades\Route;
use App\Http\Middleware\VerifyCsrfToken;

use App\Http\Controllers\UtilityController;
use App\Http\Controllers\PentahoController;
use App\Http\Controllers\AuthController;

# ATPM
use App\Http\Controllers\AtpmAfterSalesHomeController;
use App\Http\Controllers\AtpmAfterSalesUserController;
use App\Http\Controllers\AtpmReportRetentionController;
use App\Http\Controllers\AtpmAfterSalesModelOtherController;
use App\Http\Controllers\AtpmAfterSalesServiceHistoryController;

# Dealer
use App\Http\Controllers\DealerAfterSalesHomeController;
use App\Http\Controllers\DealerReportRetentionController;


// ================================ Guest Routes ================================
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login/auth', [AuthController::class, 'loginWrsAfterSales'])->name('login.auth');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('check.session');

// ================================ ATPM Routes ================================
Route::middleware(['check.session', 'role.atpm'])->group(function () {

    Route::get('atpm/after-sales/home', [AtpmAfterSalesHomeController::class, 'index'])->name('atpm.aftersales.home');

    // ATPM User
    Route::get('atpm/after-sales/atpm-user', [AtpmAfterSalesUserController::class, 'index'])->name('atpm.aftersales.atpm_user');
    Route::get('atpm/after-sales/atpm-user/datatable', [AtpmAfterSalesUserController::class, 'atpm_user_datatable'])->name('atpm.aftersales.atpm_user_datatable');
    Route::get('atpm/after-sales/atpm-user/edit-menu-permission', [AtpmAfterSalesUserController::class, 'atpm_user_edit_menu_permission'])->name('atpm.aftersales.atpm_user_menu_permission');
    Route::get('atpm/after-sales/atpm-user/sync', [AtpmAfterSalesUserController::class, 'atpm_user_sync'])->name('atpm.aftersales.atpm_user_sync');

    // Model Other
    Route::get('atpm/after-sales/model-other', [AtpmAfterSalesModelOtherController::class, 'index'])->name('atpm.aftersales.model_other');
    Route::get('atpm/after-sales/model-other/datatable', [AtpmAfterSalesModelOtherController::class, 'atpm_model_other_datatable'])->name('atpm.aftersales.model_other_datatable');
    Route::get('atpm/after-sales/model-other/create', [AtpmAfterSalesModelOtherController::class, 'atpm_model_other_create'])->name('atpm.aftersales.model_other_create');
    Route::post('atpm/after-sales/model-other/store', [AtpmAfterSalesModelOtherController::class, 'atpm_model_other_store'])->name('atpm.aftersales.model_other_store');
    Route::get('atpm/after-sales/model-other/edit', [AtpmAfterSalesModelOtherController::class, 'atpm_model_other_edit'])->name('atpm.aftersales.model_other_edit');
    
    // Vehicle History
    Route::get('atpm/after-sales/vehicle/service-history', [AtpmAfterSalesServiceHistoryController::class, 'index'])->name('atpm.aftersales.vehicle_service_history');
    Route::get('atpm/after-sales/vehicle/service-history-datatable', [AtpmAfterSalesServiceHistoryController::class, 'service_history_datatable'])->name('atpm.aftersales.vehicle_service_history_datatable');

    
    

    // Report
    // Route::middleware('check.sync')->group(function () {
        Route::get('atpm/report/report-retention', [AtpmReportRetentionController::class, 'index'])->name('atpm.report.service-retention');
        Route::post('atpm/report/report-retention-retrieve', [AtpmReportRetentionController::class, 'retrieve'])->name('atpm.report.report-retention-retrieve');
        // Route::get('atpm/after-sales/sp-test', [TestController::class, 'sp_test'])->name('atpm.aftersales.sp_test');
    // });







    
    

    // Sync Monitoring 
    Route::get('atpm/after-sales/sync', [UtilityController::class, 'sync_index'])->name('atpm.utility.sync_index');
    Route::get('atpm/after-sales/sync_logs_datatable', [UtilityController::class, 'sync_logs_datatable'])->name('atpm.utility.sync_logs_datatable');
    Route::get('atpm/after-sales/sync/information', [UtilityController::class, 'sync_information'])->name('atpm.utility.sync_information');
    

});

// ================================ Dealer Routes ================================
Route::middleware(['check.session', 'role.dealer'])->group(function () {

    Route::get('dealer/after-sales/home', [DealerAfterSalesHomeController::class, 'index'])->name('dealer.aftersales.home');

    # Retention Report
    Route::get('dealer/report/report-retention', [DealerReportRetentionController::class, 'index'])->name('dealer.report.service-retention');
    Route::post('dealer/report/report-retention-retrieve', [DealerReportRetentionController::class, 'retrieve'])->name('dealer.report.report-retention-retrieve');
    


    
});

// ================================ Utilities ================================
Route::prefix('utility')->group(function () {
   
});

Route::get('/run-job', [PentahoController::class, 'runJob']);
