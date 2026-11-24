<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\PentahoController;
use App\Http\Controllers\AuthController;

# AFTERSALES - ATPM
use App\Http\Controllers\AfterSalesAtpmHomeController;
use App\Http\Controllers\AfterSalesAtpmUserController;
use App\Http\Controllers\AfterSalesAtpmReportRetentionController;
use App\Http\Controllers\AfterSalesAtpmModelOtherController;
use App\Http\Controllers\AfterSalesAtpmServiceHistoryController;

# AFTERSALES - ACCESS CONTROL (RBAC)
use App\Http\Controllers\Afsel\AfselRoleController;
use App\Http\Controllers\Afsel\AfselPermissionController;
use App\Http\Controllers\Afsel\AfselMenuController;
use App\Http\Controllers\Afsel\AfselUserAccessController;
use App\Http\Controllers\Afsel\AfselLevelPersonelController;
use App\Http\Controllers\Afsel\AfselDealerPersonelController;
use App\Http\Controllers\Afsel\AfselDocumentNumberController;
use App\Http\Controllers\Afsel\AfselSyncWrsController;
use App\Http\Controllers\Afsel\AfselSyncMsiController;
use App\Http\Controllers\Afsel\AfselSyncConnectionController;
use App\Http\Controllers\Afsel\AfselCategoryVehicleController;
use App\Http\Controllers\Afsel\AfselCategoryVehicle2Controller;
use App\Http\Controllers\Afsel\AtpmProfileController;

# AFTERSALES - DEALER
use App\Http\Controllers\DealerAfterSalesHomeController;
use App\Http\Controllers\DealerReportRetentionController;
use App\Http\Controllers\DealerCustomerDataController;
use App\Http\Controllers\DealerServiceDataController;
use App\Http\Controllers\DealerServiceUploadController;
use App\Http\Controllers\DealerProfileController;
use App\Http\Controllers\DealerPersonelController;

# SALES - ATPM
use App\Http\Controllers\SalesAtpmHomeController;
use App\Http\Controllers\SalesAtpmConfigController;
use App\Http\Controllers\SalesAtpmSystemSetupMasterMenuController;
use App\Http\Controllers\SalesAtpmMasterPermissionController;
use App\Http\Controllers\SalesAtpmSystemSetupController;
use App\Http\Controllers\SalesAtpmAccessRepository;
use App\Http\Controllers\SalesAtpmEmailSchedulerController;





use App\Http\Controllers\SalesAtpmReportController;

# SALES - ACCESS CONTROL (RBAC Sales, terpisah dari RBAC After Sales)
use App\Http\Controllers\Sales\Access\SalesRoleController;
use App\Http\Controllers\Sales\Access\SalesPermissionController;
use App\Http\Controllers\Sales\Access\SalesMenuController;
use App\Http\Controllers\Sales\Access\SalesUserAccessController;
use App\Http\Controllers\Sales\Access\SalesMenuAccessController;

# SALES - DEALER
use App\Http\Controllers\SalesDealerHomeController;


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

// Pilih / ganti role aktif (RBAC After Sales)
Route::middleware('check.session')->group(function () {
    Route::get('/aftersales/select-role', [AuthController::class, 'showSelectRoleAfterSales'])->name('aftersales.role.select');
    Route::post('/aftersales/select-role', [AuthController::class, 'selectRoleAfterSales'])->name('aftersales.role.select.store');
});
Route::post('/sales/logout', [AuthController::class, 'salesLogout'])->name('sales.logout')->middleware('check.session');

// Pilih / ganti role aktif (RBAC Sales)
Route::middleware('check.session')->group(function () {
    Route::get('/sales/select-role', [AuthController::class, 'showSelectRoleSales'])->name('sales.role.select');
    Route::post('/sales/select-role', [AuthController::class, 'selectRoleSales'])->name('sales.role.select.store');
});


# AFTER SALES ATPM =============================================================
# RBAC: afsel.auth:atpm (user aktif + role aktif) + afsel.permission:{slug} per fitur
Route::middleware(['check.session', 'role.atpm', 'afsel.auth:atpm'])->group(function () {

    # WRS AFTER SALES #######################################################

    ## Home (semua role)
    Route::get('aftersales/atpm/home', [AfterSalesAtpmHomeController::class, 'index'])->name('aftersales.atpm.home');

    ## Profile (semua role): lihat data user & role, ganti email & password (wajib password saat ini)
    Route::get('aftersales/atpm/profile', [AtpmProfileController::class, 'index'])->name('aftersales.atpm.profile');
    Route::middleware('throttle:5,1')->group(function () {
        Route::put('aftersales/atpm/profile/email', [AtpmProfileController::class, 'updateEmail'])->name('aftersales.atpm.profile.email');
        Route::put('aftersales/atpm/profile/password', [AtpmProfileController::class, 'updatePassword'])->name('aftersales.atpm.profile.password');
    });

    ## ATPM User
    Route::middleware('afsel.permission:atpm-user.view')->group(function () {
        Route::get('aftersales/atpm/atpm-user', [AfterSalesAtpmUserController::class, 'index'])->name('aftersales.atpm.atpm_user');
        Route::get('aftersales/atpm/atpm-user/datatable', [AfterSalesAtpmUserController::class, 'atpm_user_datatable'])->name('aftersales.atpm.atpm_user_datatable');
    });
    Route::get('aftersales/atpm/atpm-user/edit-menu-permission', [AfterSalesAtpmUserController::class, 'atpm_user_edit_menu_permission'])->name('aftersales.atpm.atpm_user_menu_permission')->middleware('afsel.permission:atpm-user.manage-access');
    Route::get('aftersales/atpm/atpm-user/sync', [AfterSalesAtpmUserController::class, 'atpm_user_sync'])->name('aftersales.atpm.atpm_user_sync')->middleware('afsel.permission:atpm-user.sync');

    // Model Other
    Route::middleware('afsel.permission:model-other.view')->group(function () {
        Route::get('aftersales/atpm/model-other', [AfterSalesAtpmModelOtherController::class, 'index'])->name('aftersales.atpm.model_other');
        Route::get('aftersales/atpm/model-other/datatable', [AfterSalesAtpmModelOtherController::class, 'atpm_model_other_datatable'])->name('aftersales.atpm.model_other_datatable');
    });
    Route::middleware('afsel.permission:model-other.create')->group(function () {
        Route::get('aftersales/atpm/model-other/create', [AfterSalesAtpmModelOtherController::class, 'atpm_model_other_create'])->name('aftersales.atpm.model_other_create');
        Route::post('aftersales/atpm/model-other/store', [AfterSalesAtpmModelOtherController::class, 'atpm_model_other_store'])->name('aftersales.atpm.model_other_store');
    });
    Route::get('aftersales/atpm/model-other/edit', [AfterSalesAtpmModelOtherController::class, 'atpm_model_other_edit'])->name('aftersales.atpm.model_other_edit')->middleware('afsel.permission:model-other.edit');
    Route::put('aftersales/atpm/model-other/{id}', [AfterSalesAtpmModelOtherController::class, 'atpm_model_other_update'])->where('id', '[0-9]+')->name('aftersales.atpm.model_other_update')->middleware('afsel.permission:model-other.edit');

    // Personel After Sales - Level Personel
    Route::get('aftersales/atpm/personel/level-personel', [AfselLevelPersonelController::class, 'index'])->name('aftersales.atpm.level_personel')->middleware('afsel.permission:level-personel.view');
    Route::post('aftersales/atpm/personel/level-personel', [AfselLevelPersonelController::class, 'store'])->name('aftersales.atpm.level_personel.store')->middleware('afsel.permission:level-personel.create');
    Route::put('aftersales/atpm/personel/level-personel/{id}', [AfselLevelPersonelController::class, 'update'])->where('id', '[0-9]+')->name('aftersales.atpm.level_personel.update')->middleware('afsel.permission:level-personel.edit');

    // Personel After Sales - Dealer Personel
    Route::middleware('afsel.permission:dealer-personel.view')->group(function () {
        Route::get('aftersales/atpm/personel/dealer-personel', [AfselDealerPersonelController::class, 'index'])->name('aftersales.atpm.dealer_personel');
        Route::get('aftersales/atpm/personel/dealer-personel/datatable', [AfselDealerPersonelController::class, 'datatable'])->name('aftersales.atpm.dealer_personel.datatable');
        Route::get('aftersales/atpm/personel/dealer-personel/{id}', [AfselDealerPersonelController::class, 'show'])->where('id', '[0-9]+')->name('aftersales.atpm.dealer_personel.show');
        Route::get('aftersales/atpm/personel/dealer-personel/{id}/photo', [AfselDealerPersonelController::class, 'photo'])->where('id', '[0-9]+')->name('aftersales.atpm.dealer_personel.photo');
    });
    Route::put('aftersales/atpm/personel/dealer-personel/{id}', [AfselDealerPersonelController::class, 'update'])->where('id', '[0-9]+')->name('aftersales.atpm.dealer_personel.update')->middleware(['afsel.permission:dealer-personel.view', 'afsel.permission:dealer-personel.edit']);
    Route::post('aftersales/atpm/personel/dealer-personel/sync',[AfselDealerPersonelController::class, 'sync'])->name('aftersales.atpm.dealer_personel.sync')->middleware('afsel.permission:dealer-personel.sync');

    // Administration - Document Numbering (nomor urut yyyymmdd + 20 digit per table_name)
    Route::get('aftersales/atpm/administration/document-number', [AfselDocumentNumberController::class, 'index'])->name('aftersales.atpm.document_number')->middleware('afsel.permission:document-number.view');
    Route::middleware(['afsel.permission:document-number.view', 'afsel.permission:document-number.manage'])->group(function () {
        Route::post('aftersales/atpm/administration/document-number', [AfselDocumentNumberController::class, 'store'])->name('aftersales.atpm.document_number.store');
        Route::put('aftersales/atpm/administration/document-number/{id}', [AfselDocumentNumberController::class, 'update'])->whereNumber('id')->name('aftersales.atpm.document_number.update');
    });

    // General Setting - Category Service: Cat Service Vehicle 1 (tblcategory_vehicles)
    Route::get('aftersales/atpm/general-setting/category-service-vehicle-1', [AfselCategoryVehicleController::class, 'index'])->name('aftersales.atpm.category_vehicle_1')->middleware('afsel.permission:category-vehicle-1.view');
    Route::post('aftersales/atpm/general-setting/category-service-vehicle-1', [AfselCategoryVehicleController::class, 'store'])->name('aftersales.atpm.category_vehicle_1.store')->middleware(['afsel.permission:category-vehicle-1.view', 'afsel.permission:category-vehicle-1.create']);
    Route::put('aftersales/atpm/general-setting/category-service-vehicle-1/{id}', [AfselCategoryVehicleController::class, 'update'])->whereNumber('id')->name('aftersales.atpm.category_vehicle_1.update')->middleware(['afsel.permission:category-vehicle-1.view', 'afsel.permission:category-vehicle-1.edit']);
    Route::delete('aftersales/atpm/general-setting/category-service-vehicle-1/{id}', [AfselCategoryVehicleController::class, 'destroy'])->whereNumber('id')->name('aftersales.atpm.category_vehicle_1.destroy')->middleware(['afsel.permission:category-vehicle-1.view', 'afsel.permission:category-vehicle-1.delete']);

    // General Setting - Category Service: Cat Service Vehicle 2 (tblcategory_2_vehicles)
    Route::get('aftersales/atpm/general-setting/category-service-vehicle-2', [AfselCategoryVehicle2Controller::class, 'index'])->name('aftersales.atpm.category_vehicle_2')->middleware('afsel.permission:category-vehicle-2.view');
    Route::post('aftersales/atpm/general-setting/category-service-vehicle-2', [AfselCategoryVehicle2Controller::class, 'store'])->name('aftersales.atpm.category_vehicle_2.store')->middleware(['afsel.permission:category-vehicle-2.view', 'afsel.permission:category-vehicle-2.create']);
    Route::put('aftersales/atpm/general-setting/category-service-vehicle-2/{id}', [AfselCategoryVehicle2Controller::class, 'update'])->whereNumber('id')->name('aftersales.atpm.category_vehicle_2.update')->middleware(['afsel.permission:category-vehicle-2.view', 'afsel.permission:category-vehicle-2.edit']);
    Route::delete('aftersales/atpm/general-setting/category-service-vehicle-2/{id}', [AfselCategoryVehicle2Controller::class, 'destroy'])->whereNumber('id')->name('aftersales.atpm.category_vehicle_2.destroy')->middleware(['afsel.permission:category-vehicle-2.view', 'afsel.permission:category-vehicle-2.delete']);

    // Vehicle History
    Route::middleware('afsel.permission:service-history.view')->group(function () {
        Route::get('aftersales/atpm/vehicle/service-history', [AfterSalesAtpmServiceHistoryController::class, 'index'])->name('aftersales.atpm.vehicle_service_history');
        Route::get('aftersales/atpm/vehicle/service-history-datatable', [AfterSalesAtpmServiceHistoryController::class, 'service_history_datatable'])->name('aftersales.atpm.vehicle_service_history_datatable');
    });

    // Sync Monitoring
    Route::middleware('afsel.permission:sync-monitoring.view')->group(function () {
        Route::get('aftersales/atpm/sync', [UtilityController::class, 'sync_index'])->name('aftersales.atpm.utility.sync_index');
        Route::get('aftersales/atpm/sync_logs_datatable', [UtilityController::class, 'sync_logs_datatable'])->name('aftersales.atpm.utility.sync_logs_datatable');
        Route::get('aftersales/atpm/sync/information', [UtilityController::class, 'sync_information'])->name('aftersales.atpm.utility.sync_information');
    });

    // Utility - Monitoring Sync WRS Sales (inventory / faktur)
    Route::middleware('afsel.permission:sync-wrs.view')->group(function () {
        Route::get('aftersales/atpm/utility/sync-wrs/inventory', [AfselSyncWrsController::class, 'index'])->defaults('job', 'inventory')->name('aftersales.atpm.sync_wrs.inventory');
        Route::get('aftersales/atpm/utility/sync-wrs/faktur', [AfselSyncWrsController::class, 'index'])->defaults('job', 'faktur')->name('aftersales.atpm.sync_wrs.faktur');
        Route::get('aftersales/atpm/utility/sync-wrs/log/{id}', [AfselSyncWrsController::class, 'show'])->whereNumber('id')->name('aftersales.atpm.sync_wrs.show');
        Route::get('aftersales/atpm/utility/sync-wrs/{job}/datatable', [AfselSyncWrsController::class, 'datatable'])->whereIn('job', ['inventory', 'faktur'])->name('aftersales.atpm.sync_wrs.datatable');
        Route::get('aftersales/atpm/utility/sync-wrs/{job}/status', [AfselSyncWrsController::class, 'status'])->whereIn('job', ['inventory', 'faktur'])->name('aftersales.atpm.sync_wrs.status');
    });
    Route::post('aftersales/atpm/utility/sync-wrs/{job}/run', [AfselSyncWrsController::class, 'run'])->whereIn('job', ['inventory', 'faktur'])->name('aftersales.atpm.sync_wrs.run')->middleware(['afsel.permission:sync-wrs.view', 'afsel.permission:sync-wrs.run']);

    // Utility - Monitoring Sync MSI (Customer CSI)
    Route::middleware('afsel.permission:sync-msi.view')->group(function () {
        Route::get('aftersales/atpm/utility/sync-msi', [AfselSyncMsiController::class, 'index'])->name('aftersales.atpm.sync_msi');
        Route::get('aftersales/atpm/utility/sync-msi/datatable', [AfselSyncMsiController::class, 'datatable'])->name('aftersales.atpm.sync_msi.datatable');
        Route::get('aftersales/atpm/utility/sync-msi/status', [AfselSyncMsiController::class, 'status'])->name('aftersales.atpm.sync_msi.status');
        Route::get('aftersales/atpm/utility/sync-msi/log/{id}', [AfselSyncMsiController::class, 'show'])->whereNumber('id')->name('aftersales.atpm.sync_msi.show');
    });
    Route::post('aftersales/atpm/utility/sync-msi/run', [AfselSyncMsiController::class, 'run'])->name('aftersales.atpm.sync_msi.run')->middleware(['afsel.permission:sync-msi.view', 'afsel.permission:sync-msi.run']);

    // Utility - Koneksi Database Sync (tanpa hapus)
    Route::get('aftersales/atpm/utility/sync-connection', [AfselSyncConnectionController::class, 'index'])->name('aftersales.atpm.sync_connection')->middleware('afsel.permission:sync-connection.view');
    Route::middleware(['afsel.permission:sync-connection.view', 'afsel.permission:sync-connection.manage'])->group(function () {
        Route::post('aftersales/atpm/utility/sync-connection', [AfselSyncConnectionController::class, 'store'])->name('aftersales.atpm.sync_connection.store');
        Route::put('aftersales/atpm/utility/sync-connection/{id}', [AfselSyncConnectionController::class, 'update'])->whereNumber('id')->name('aftersales.atpm.sync_connection.update');
        Route::post('aftersales/atpm/utility/sync-connection/test', [AfselSyncConnectionController::class, 'test'])->name('aftersales.atpm.sync_connection.test');
    });

    // Report
    // Route::middleware('check.sync')->group(function () {
    Route::middleware('afsel.permission:report-retention.view')->group(function () {
        Route::get('atpm/report/report-retention', [AfterSalesAtpmReportRetentionController::class, 'index'])->name('aftersales.atpm.report.service-retention');
        Route::post('atpm/report/report-retention-retrieve', [AfterSalesAtpmReportRetentionController::class, 'retrieve'])->name('aftersales.atpm.report.report-retention-retrieve');
    });

    Route::middleware('afsel.permission:report-retention-old.view')->group(function () {
        Route::get('atpm/report/report-retention-old', [AfterSalesAtpmReportRetentionController::class, 'index_old'])->name('aftersales.atpm.report.service-retention-old');
        Route::post('atpm/report/report-retention-old-retrieve', [AfterSalesAtpmReportRetentionController::class, 'retrieve_old'])->name('aftersales.atpm.report.report-retention-retrieve-old');
    });

        // Route::get('aftersales/atpm/sp-test', [TestController::class, 'sp_test'])->name('aftersales.atpmsp_test');
    // });

    // Access Control (RBAC) — mengelola akses ATPM & Dealer (?type=atpm|dealer)
    Route::prefix('aftersales/atpm/access')->name('aftersales.atpm.access.')->group(function () {

        Route::middleware('afsel.permission:rbac.role.manage')->group(function () {
            Route::get('roles', [AfselRoleController::class, 'index'])->name('roles');
            Route::post('roles', [AfselRoleController::class, 'store'])->name('roles.store');
            Route::put('roles/{id}', [AfselRoleController::class, 'update'])->whereNumber('id')->name('roles.update');
            Route::delete('roles/{id}', [AfselRoleController::class, 'destroy'])->whereNumber('id')->name('roles.destroy');
            Route::get('roles/{id}/access', [AfselRoleController::class, 'access'])->whereNumber('id')->name('roles.access');
            Route::post('roles/{id}/access', [AfselRoleController::class, 'saveAccess'])->whereNumber('id')->name('roles.access.save');
        });

        Route::middleware('afsel.permission:rbac.permission.manage')->group(function () {
            Route::get('permissions', [AfselPermissionController::class, 'index'])->name('permissions');
            Route::post('permissions', [AfselPermissionController::class, 'store'])->name('permissions.store');
            Route::put('permissions/{id}', [AfselPermissionController::class, 'update'])->whereNumber('id')->name('permissions.update');
            Route::delete('permissions/{id}', [AfselPermissionController::class, 'destroy'])->whereNumber('id')->name('permissions.destroy');
        });

        Route::middleware('afsel.permission:rbac.menu.manage')->group(function () {
            Route::get('menus', [AfselMenuController::class, 'index'])->name('menus');
            Route::post('menus', [AfselMenuController::class, 'store'])->name('menus.store');
            Route::put('menus/{id}', [AfselMenuController::class, 'update'])->whereNumber('id')->name('menus.update');
            Route::delete('menus/{id}', [AfselMenuController::class, 'destroy'])->whereNumber('id')->name('menus.destroy');
        });

        Route::middleware('afsel.permission:rbac.user-role.manage')->group(function () {
            Route::get('users', [AfselUserAccessController::class, 'index'])->name('users');
            Route::get('users/datatable', [AfselUserAccessController::class, 'datatable'])->name('users.datatable');
            // Tambah / ubah data user di luar sync WRS
            Route::middleware('afsel.permission:rbac.user.manage')->group(function () {
                Route::post('users', [AfselUserAccessController::class, 'store'])->name('users.store');
                Route::put('users/{kdUser}/profile', [AfselUserAccessController::class, 'updateProfile'])->where('kdUser', '[A-Za-z0-9._\-]+')->name('users.profile.update');
            });
            Route::get('users/{kdUser}', [AfselUserAccessController::class, 'edit'])->where('kdUser', '[A-Za-z0-9._\-]+')->name('users.edit');
            Route::post('users/{kdUser}', [AfselUserAccessController::class, 'update'])->where('kdUser', '[A-Za-z0-9._\-]+')->name('users.update');
        });
    });

});


# SALES ATPM ===================================================================
# RBAC Sales (terpisah dari After Sales): sales.auth:atpm (user aktif + role aktif) + sales.permission:{slug} per fitur
Route::middleware(['check.session', 'role.atpm', 'sales.auth:atpm'])->group(function () {

    # WRS SALES ##############################################################

    ## Home (semua role)
    Route::get('sales/atpm/home', [SalesAtpmHomeController::class, 'index'])->name('sales.atpm.home');

    # SYSTEM SETUP
    Route::get('sales/atpm/system-setup/config/', [SalesAtpmConfigController::class, 'index'])->name('sales.atpm.system_setup.config')->middleware('sales.permission:config.view');

    ## Halaman lama Master Menu / Master Permission (digantikan Administration > Access Control)
    Route::middleware('sales.permission:rbac.menu.manage')->group(function () {
        Route::get('sales/atpm/system-setup/master-menu-atpm/', [SalesAtpmSystemSetupMasterMenuController::class, 'sales_atpm_master_menu'])->name('sales.atpm.system_setup.sales_atpm_master_menu');
        Route::get('sales/atpm/system-setup/master-menu-atpm/datatable', [SalesAtpmSystemSetupMasterMenuController::class, 'sales_atpm_master_menu_datatable'])->name('sales.atpm.system_setup.sales_atpm_master_menu_datatable');
        Route::get('sales/atpm/system-setup/master-menu-dealer/', [SalesAtpmSystemSetupMasterMenuController::class, 'index'])->name('sales.atpm.system_setup.master_menu_dealer');
    });
    Route::middleware('sales.permission:rbac.permission.manage')->group(function () {
        Route::get('sales/atpm/system-setup/master-permission/', [SalesAtpmMasterPermissionController::class, 'index'])->name('sales.atpm.system_setup.master_permission');
        Route::get('sales/atpm/system-setup/master-permission/datatable/', [SalesAtpmMasterPermissionController::class, 'sales_atpm_master_permission_datatable'])->name('sales.atpm.system_setup.sales_atpm_master_permission_datatable');
    });

    # ADMINISTRATION - ATPM - USER: dihapus (tidak dipakai lagi). Akses user diatur di Access Control > User Access.

    # EMAIL SCHEDULER
    ## EMAIL SCHEDULER CKD
    Route::middleware('sales.permission:email-scheduler-ckd.view')->group(function () {
        Route::get('sales/atpm/email_scheduler/delivery_ckd/', [SalesAtpmEmailSchedulerController::class, 'index'])->name('sales.atpm.email_scheduler.delivery_ckd');
        Route::get('sales/atpm/email_scheduler/delivery_ckd/datatable', [SalesAtpmEmailSchedulerController::class, 'datatable'])->name('sales.atpm.email_scheduler.delivery_ckd_datatable');
        Route::get('sales/atpm/email_scheduler/delivery_ckd/retry_log_datatable', [SalesAtpmEmailSchedulerController::class, 'retryLogDatatable'])->name('sales.atpm.email_scheduler.delivery_ckd_retry_log_datatable');
    });
    Route::middleware(['sales.permission:email-scheduler-ckd.view', 'sales.permission:email-scheduler-ckd.manage'])->group(function () {
        Route::post('sales/atpm/email_scheduler/delivery_ckd/update_email_to', [SalesAtpmEmailSchedulerController::class, 'updateEmailTo'])->name('sales.atpm.email_scheduler.delivery_ckd_update_email_to');
        Route::post('sales/atpm/email_scheduler/delivery_ckd/retry', [SalesAtpmEmailSchedulerController::class, 'retry'])->name('sales.atpm.email_scheduler.delivery_ckd_retry');
    });

    # REPORT
    Route::get('sales/atpm/report/sales_person_history', [SalesAtpmReportController::class, 'salesPersonHistoryReport'])->name('sales.atpm.report.sales_person_history')->middleware('sales.permission:report.sales-person-history.view');
    Route::get('sales/atpm/report/sales_person_productivity', [SalesAtpmReportController::class, 'salesPersonProductivityReport'])->name('sales.atpm.report.sales_person_productivity')->middleware('sales.permission:report.sales-person-productivity.view');
    Route::get('sales/atpm/report/sales_person_national_productivity', [SalesAtpmReportController::class, 'salesPersonNationalProductivityReport'])->name('sales.atpm.report.sales_person_national_productivity')->middleware('sales.permission:report.sales-person-national-productivity.view');
    Route::get('sales/atpm/report/sales_person_list', [SalesAtpmReportController::class, 'salesPersonListReport'])->name('sales.atpm.report.sales_person_list')->middleware('sales.permission:report.sales-person-list.view');

    // Administration
    // Nonaktif: SalesAtpmAdministrationController belum ada (route ini membuat `php artisan route:list` error).
    // Route::get('sales/atpm/administration/atpm-user/', [SalesAtpmAdministrationController::class, 'index'])->name('sales.atpm.administration.atpm_user');
    // Route::get('sales/atpm/administration/dealer-user/', [SalesAtpmAdministrationController::class, 'index'])->name('sales.atpm.administration.dealer_user');

    # ACCESS CONTROL (RBAC Sales) — mengelola akses Sales ATPM & Sales Dealer (?type=atpm|dealer)
    Route::prefix('sales/atpm/access')->name('sales.atpm.access.')->group(function () {

        Route::middleware('sales.permission:rbac.role.manage')->group(function () {
            Route::get('roles', [SalesRoleController::class, 'index'])->name('roles');
            Route::post('roles', [SalesRoleController::class, 'store'])->name('roles.store');
            Route::put('roles/{id}', [SalesRoleController::class, 'update'])->whereNumber('id')->name('roles.update');
            Route::delete('roles/{id}', [SalesRoleController::class, 'destroy'])->whereNumber('id')->name('roles.destroy');
            Route::get('roles/{id}/access', [SalesRoleController::class, 'access'])->whereNumber('id')->name('roles.access');
            Route::post('roles/{id}/access', [SalesRoleController::class, 'saveAccess'])->whereNumber('id')->name('roles.access.save');
        });

        Route::middleware('sales.permission:rbac.permission.manage')->group(function () {
            Route::get('permissions', [SalesPermissionController::class, 'index'])->name('permissions');
            Route::post('permissions', [SalesPermissionController::class, 'store'])->name('permissions.store');
            Route::put('permissions/{id}', [SalesPermissionController::class, 'update'])->whereNumber('id')->name('permissions.update');
            Route::delete('permissions/{id}', [SalesPermissionController::class, 'destroy'])->whereNumber('id')->name('permissions.destroy');
        });

        Route::middleware('sales.permission:rbac.menu.manage')->group(function () {
            Route::get('menus', [SalesMenuController::class, 'index'])->name('menus');
            Route::post('menus', [SalesMenuController::class, 'store'])->name('menus.store');
            Route::put('menus/{id}', [SalesMenuController::class, 'update'])->whereNumber('id')->name('menus.update');
            Route::delete('menus/{id}', [SalesMenuController::class, 'destroy'])->whereNumber('id')->name('menus.destroy');
        });

        // Access Configuration (Per Role / Per User / Preview) — seperti eurokars-exam
        Route::middleware('sales.permission:rbac.menu-access.manage')->group(function () {
            Route::get('menu-access', [SalesMenuAccessController::class, 'index'])->name('menu-access');
            Route::post('menu-access/role', [SalesMenuAccessController::class, 'syncRole'])->name('menu-access.role.sync');
            Route::get('menu-access/users', [SalesMenuAccessController::class, 'users'])->name('menu-access.users');
            Route::get('menu-access/user/{kdUser}', [SalesMenuAccessController::class, 'userAccess'])->where('kdUser', '[A-Za-z0-9._\-]+')->name('menu-access.user');
            Route::post('menu-access/user/{kdUser}', [SalesMenuAccessController::class, 'syncUser'])->where('kdUser', '[A-Za-z0-9._\-]+')->name('menu-access.user.sync');
        });

        // Akun user dikelola di WRS Sales; di sini hanya role & override menu per user
        Route::middleware('sales.permission:rbac.user-role.manage')->group(function () {
            Route::get('users', [SalesUserAccessController::class, 'index'])->name('users');
            Route::get('users/datatable', [SalesUserAccessController::class, 'datatable'])->name('users.datatable');
            Route::get('users/{kdUser}', [SalesUserAccessController::class, 'edit'])->where('kdUser', '[A-Za-z0-9._\-]+')->name('users.edit');
            Route::post('users/{kdUser}', [SalesUserAccessController::class, 'update'])->where('kdUser', '[A-Za-z0-9._\-]+')->name('users.update');
        });
    });

});


# SALES DEALER =================================================================
# RBAC Sales Dealer: sales.auth:dealer. Menu & permission diatur dari Sales ATPM > Access Control (tab Dealer).
Route::middleware(['check.session', 'role.dealer', 'sales.auth:dealer'])->prefix('sales/dealer')->name('sales.dealer.')->group(function () {

    Route::redirect('/', '/sales/dealer/home');
    Route::get('home', [SalesDealerHomeController::class, 'index'])->name('home');

});


// ================================ Dealer Routes ================================
Route::middleware(['check.session', 'role.dealer', 'afsel.auth:dealer'])->group(function () {

    # Retention Report
    Route::middleware('afsel.permission:report-retention.view')->group(function () {
        Route::get('dealer/report/report-retention', [DealerReportRetentionController::class, 'index'])->name('dealer.report.service-retention');
        Route::post('dealer/report/report-retention-retrieve', [DealerReportRetentionController::class, 'retrieve'])->name('dealer.report.report-retention-retrieve');
    });

    // URL lama home dealer (sebelum pindah ke /aftersales/dealer) → tetap diarahkan supaya bookmark user tidak 404
    Route::permanentRedirect('dealer/aftersales/home', '/aftersales/dealer/home');

    # After Sales Dealer: domain.com/aftersales/dealer/...
    Route::prefix('aftersales/dealer')->name('aftersales.dealer.')->group(function () {

        Route::redirect('/', '/aftersales/dealer/home');
        Route::get('home', [DealerAfterSalesHomeController::class, 'index'])->name('home');

        # Profile user dealer (semua role): lihat data dealer, ganti email & password (wajib password saat ini)
        Route::get('profile', [DealerProfileController::class, 'index'])->name('profile');
        Route::middleware('throttle:5,1')->group(function () {
            Route::put('profile/email', [DealerProfileController::class, 'updateEmail'])->name('profile.email');
            Route::put('profile/password', [DealerProfileController::class, 'updatePassword'])->name('profile.password');
        });

        # Customer Data
        Route::middleware('afsel.permission:customer-data.view')->group(function () {
            Route::get('customer/customer-data', [DealerCustomerDataController::class, 'index'])->name('customer.customer-data');
            Route::get('customer/customer-data-datatable', [DealerCustomerDataController::class, 'datatable'])->name('customer.customer-data-datatable');
            Route::get('customer/customer-data-export', [DealerCustomerDataController::class, 'export'])->name('customer.customer-data-export')->middleware('afsel.permission:customer-data.export');
        });

        # Upload Service (Excel → tblcustomer + tblkpi dealer sendiri)
        Route::middleware('afsel.permission:service-data.upload')->group(function () {
            Route::get('service/service-upload', [DealerServiceUploadController::class, 'index'])->name('service.service-upload');
            Route::get('service/service-upload-template', [DealerServiceUploadController::class, 'template'])->name('service.service-upload-template');
            Route::post('service/service-upload', [DealerServiceUploadController::class, 'upload'])->name('service.service-upload-store');
        });

        # Data Service
        Route::middleware('afsel.permission:service-data.view')->group(function () {
            Route::get('service/service-data', [DealerServiceDataController::class, 'index'])->name('service.service-data');
            Route::get('service/service-data-datatable', [DealerServiceDataController::class, 'datatable'])->name('service.service-data-datatable');
            Route::get('service/service-data-export', [DealerServiceDataController::class, 'export'])->name('service.service-data-export')->middleware('afsel.permission:service-data.export');
        });

        # Dealer Personel After Sales (tanpa hapus: personel cukup dinonaktifkan)
        Route::prefix('personel/dealer-personel')->name('personel.dealer-personel')->group(function () {
            Route::middleware('afsel.permission:personel.view')->group(function () {
                Route::get('/', [DealerPersonelController::class, 'index'])->name('');
                Route::get('datatable', [DealerPersonelController::class, 'datatable'])->name('.datatable');
                Route::get('export', [DealerPersonelController::class, 'export'])->name('.export')->middleware('afsel.permission:personel.export');
                Route::get('{id}', [DealerPersonelController::class, 'show'])->where('id', '[0-9]+')->name('.show');
                Route::get('{id}/photo', [DealerPersonelController::class, 'photo'])->where('id', '[0-9]+')->name('.photo');
            });
            Route::post('/', [DealerPersonelController::class, 'store'])->name('.store')
                ->middleware(['afsel.permission:personel.view', 'afsel.permission:personel.create']);
            Route::put('{id}', [DealerPersonelController::class, 'update'])->where('id', '[0-9]+')->name('.update')
                ->middleware(['afsel.permission:personel.view', 'afsel.permission:personel.edit,personel.status']);
        });
    });



    
});

// ================================ Utilities ================================
Route::prefix('utility')->group(function () {
   
});

Route::get('/run-job', [PentahoController::class, 'runJob']);
