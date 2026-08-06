<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\PentahoController;
use App\Http\Controllers\AuthController;

# AFTER SALES - ATPM
use App\Http\Controllers\AfterSalesAtpmHomeController;
use App\Http\Controllers\AfterSalesAtpmUserController;
use App\Http\Controllers\AfterSalesAfterSalesAtpmReportRetentionController;
use App\Http\Controllers\AfterSalesAtpmModelOtherController;
use App\Http\Controllers\AfterSalesAtpmServiceHistoryController;

# AFTER SALES - DEALER
use App\Http\Controllers\DealerAfterSalesHomeController;
use App\Http\Controllers\DealerReportRetentionController;

# SALES - ATPM
use App\Http\Controllers\SalesAtpmHomeController;
use App\Http\Controllers\SalesAtpmConfigController;
use App\Http\Controllers\SalesAtpmSystemSetupMasterMenuController;
use App\Http\Controllers\SalesAtpmMasterPermissionController;
use App\Http\Controllers\SalesAtpmSystemSetupController;
use App\Http\Controllers\SalesAtpmUserController;


use App\Http\Controllers\SalesAtpmReportController;

# SALES - DEALER



# === Guest Routes =============================================================
Route::middleware('guest')->group(function () {

    Route::get('/', [AuthController::class, 'wrsOption'])->name('wrs.connect');

    // LOGIN AFTER SALES
    Route::get('/aftersales', [AuthController::class, 'showLoginAfterSales'])->name('loginAfterSales');
    Route::post('/aftersales/login/auth', [AuthController::class, 'loginWrsAfterSales'])->name('aftersales.login.auth');

    // LOGIN SALES
    Route::get('/sales', [AuthController::class, 'showLoginSales'])->name('loginSales');
    Route::post('/sales/login/auth', [AuthController::class, 'loginWrsSales'])->name('sales.login.auth');

});

Route::post('/aftersales/logout', [AuthController::class, 'aftersalesLogout'])->name('aftersales.logout')->middleware('check.session');
Route::post('/sales/logout', [AuthController::class, 'salesLogout'])->name('sales.logout')->middleware('check.session');


# AFTER SALES ATPM =============================================================
Route::middleware(['check.session', 'role.atpm'])->group(function () {

    # WRS AFTER SALES #######################################################

    // Home
    Route::get('aftersales/atpm/home', [AfterSalesAtpmHomeController::class, 'index'])->name('aftersales.atpm.home');

    // ATPM User
    Route::get('aftersales/atpm/atpm-user', [AfterSalesAtpmUserController::class, 'index'])->name('aftersales.atpm.atpm_user');
    Route::get('aftersales/atpm/atpm-user/datatable', [AfterSalesAtpmUserController::class, 'atpm_user_datatable'])->name('aftersales.atpm.atpm_user_datatable');
    Route::get('aftersales/atpm/atpm-user/edit-menu-permission', [AfterSalesAtpmUserController::class, 'atpm_user_edit_menu_permission'])->name('aftersales.atpm.atpm_user_menu_permission');
    Route::get('aftersales/atpm/atpm-user/sync', [AfterSalesAtpmUserController::class, 'atpm_user_sync'])->name('aftersales.atpm.atpm_user_sync');

    // Model Other
    Route::get('aftersales/atpm/model-other', [AfterSalesAtpmModelOtherController::class, 'index'])->name('aftersales.atpm.model_other');
    Route::get('aftersales/atpm/model-other/datatable', [AfterSalesAtpmModelOtherController::class, 'atpm_model_other_datatable'])->name('aftersales.atpm.model_other_datatable');
    Route::get('aftersales/atpm/model-other/create', [AfterSalesAtpmModelOtherController::class, 'atpm_model_other_create'])->name('aftersales.atpm.model_other_create');
    Route::post('aftersales/atpm/model-other/store', [AfterSalesAtpmModelOtherController::class, 'atpm_model_other_store'])->name('aftersales.atpm.model_other_store');
    Route::get('aftersales/atpm/model-other/edit', [AfterSalesAtpmModelOtherController::class, 'atpm_model_other_edit'])->name('aftersales.atpm.model_other_edit');
    
    // Vehicle History
    Route::get('aftersales/atpm/vehicle/service-history', [AfterSalesAtpmServiceHistoryController::class, 'index'])->name('aftersales.atpm.vehicle_service_history');
    Route::get('aftersales/atpm/vehicle/service-history-datatable', [AfterSalesAtpmServiceHistoryController::class, 'service_history_datatable'])->name('aftersales.atpm.vehicle_service_history_datatable');

    
    

    // Report
    // Route::middleware('check.sync')->group(function () {
        Route::get('atpm/report/report-retention', [AfterSalesAtpmReportRetentionController::class, 'index'])->name('aftersales.atpm.report.service-retention');
        Route::post('atpm/report/report-retention-retrieve', [AfterSalesAtpmReportRetentionController::class, 'retrieve'])->name('aftersales.atpm.report.report-retention-retrieve');
        // Route::get('aftersales/atpm/sp-test', [TestController::class, 'sp_test'])->name('aftersales.atpmsp_test');
    // });




    # WRS SALES ##############################################################

        Route::get('sales/atpm/home', [SalesAtpmHomeController::class, 'index'])->name('sales.atpm.home');
        
        // System Setup
        Route::get('sales/atpm/system-setup/config/', [SalesAtpmSystemSetupController::class, 'index'])->name('sales.atpm.system_setup.config');
        Route::get('sales/atpm/system-setup/master-menu-atpm/', [SalesAtpmSystemSetupMasterMenuController::class, 'sales_atpm_master_menu'])->name('sales.atpm.system_setup.sales_atpm_master_menu');
        Route::get('sales/atpm/system-setup/master-menu-atpm/datatable', [SalesAtpmSystemSetupMasterMenuController::class, 'sales_atpm_master_menu_datatable'])->name('sales.atpm.system_setup.sales_atpm_master_menu_datatable');
        Route::get('sales/atpm/system-setup/master-permission/', [SalesAtpmMasterPermissionController::class, 'index'])->name('sales.atpm.system_setup.sales_atpm_master_permission');
        Route::get('sales/atpm/system-setup/master-permission/datatable/', [SalesAtpmMasterPermissionController::class, 'sales_atpm_master_permission_datatable'])->name('sales.atpm.system_setup.sales_atpm_master_permission_datatable');
        
        # ADMINISTRATION 
        Route::get('sales/atpm/user', [SalesAtpmUserController::class, 'index'])->name('sales.atpm.user_index');
        Route::get('sales/atpm/user/datatable', [SalesAtpmUserController::class, 'user_datatable'])->name('sales.atpm.user_datatable');
        Route::get('sales/atpm/user/sync', [SalesAtpmUserController::class, 'userSync'])->name('sales.atpm.user_sync');

        
        # ADMINISTRATION - ATPM - USER

        ## MENU
        Route::get('sales/atpm/user_menu', [SalesAtpmUserController::class, 'userMenu'])->name('sales.atpm.user_menu_index');
        Route::get('sales/atpm/edit_user_menu/{kd_atpm_user}', [SalesAtpmUserController::class, 'editUserMenu'])->name('sales.atpm.edit_user_menu');
        
        
        ## PERMISSION
        Route::get('sales/atpm/edit_user_permission/{kd_atpm_user}', [SalesAtpmUserController::class, 'userPermissionEdit'])->name('sales.atpm.edit_user_permission');

        



        Route::get('sales/atpm/user/permission/', [SalesAtpmUserPermissionController::class, 'index'])->name('sales.atpm.user_permission_index');
        

        // sales.atpm.user_datatable






        Route::get('sales/atpm/system-setup/master-menu-dealer/', [SalesAtpmSystemSetupMasterMenuController::class, 'index'])->name('sales.atpm.system_setup.master_menu_dealer');
        
        // Administration
        Route::get('sales/atpm/administration/atpm-user/', [SalesAtpmAdministrationController::class, 'index'])->name('sales.atpm.administration.atpm_user');
        Route::get('sales/atpm/administration/dealer-user/', [SalesAtpmAdministrationController::class, 'index'])->name('sales.atpm.administration.dealer_user');
        
    




    // Route::get('sales/atpm/config', [SalesAtpmConfigController::class, 'index'])->name('sales.atpm.config');
    








    // # 
    // Route::get('sales/atpm/administration/atpm-user', [SalesAtpmConfigController::class, 'index'])->name('sales.atpm.administration.atpm_user');
    // Route::get('sales/atpm/administration/dealer-user', [SalesAtpmConfigController::class, 'index'])->name('sales.atpm.administration.dealer_user');
    // 
    // Route::get('sales/atpm/administration/master-menu-dealer/', [SalesAtpmConfigController::class, 'index'])->name('sales.atpm.administration.master_menu_dealer');
    
    // Report
    Route::get('sales/atpm/report/sales_person_history', [SalesAtpmReportController::class, 'salesPersonHistoryReport'])->name('sales.atpm.report.sales_person_history');
    Route::get('sales/atpm/report/sales_person_productivity', [SalesAtpmReportController::class, 'salesPersonProductivityReport'])->name('sales.atpm.report.sales_person_productivity');
    Route::get('sales/atpm/report/sales_person_national_productivity', [SalesAtpmReportController::class, 'salesPersonNationalProductivityReport'])->name('sales.atpm.report.sales_person_national_productivity');


    
    

    // Sync Monitoring 
    Route::get('aftersales/atpm/sync', [UtilityController::class, 'sync_index'])->name('aftersales.atpm.utility.sync_index');
    Route::get('aftersales/atpm/sync_logs_datatable', [UtilityController::class, 'sync_logs_datatable'])->name('aftersales.atpm.utility.sync_logs_datatable');
    Route::get('aftersales/atpm/sync/information', [UtilityController::class, 'sync_information'])->name('aftersales.atpm.utility.sync_information');
    

});






// ================================ Dealer Routes ================================
Route::middleware(['check.session', 'role.dealer'])->group(function () {

    Route::get('dealer/aftersales/home', [DealerAfterSalesHomeController::class, 'index'])->name('dealer.aftersales.home');

    # Retention Report
    Route::get('dealer/report/report-retention', [DealerReportRetentionController::class, 'index'])->name('dealer.report.service-retention');
    Route::post('dealer/report/report-retention-retrieve', [DealerReportRetentionController::class, 'retrieve'])->name('dealer.report.report-retention-retrieve');
    


    
});

// ================================ Utilities ================================
Route::prefix('utility')->group(function () {
   
});

Route::get('/run-job', [PentahoController::class, 'runJob']);
