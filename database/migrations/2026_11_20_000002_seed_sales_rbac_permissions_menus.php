<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Seed RBAC WRS Connect Sales (terpisah dari RBAC After Sales):
 *
 * Sales ATPM (ms_sales_atpm_*)
 *  - Permission per fitur (dipakai middleware sales.permission:{slug} di routes/web.php).
 *  - Menu Administration > Access Control (User Access, Role, Permission, Menu).
 *  - permission_slug di menu fitur yang sudah ada.
 *  - Role "user" mendapat semua permission fitur (non Access Control) agar akses user lama tidak berubah;
 *    menu tetap diatur per user (override) seperti sebelumnya.
 *  - Menu lama yang digantikan Access Control dinonaktifkan (TIDAK dihapus):
 *    Master Menu, Master Permission, Master Menu Sales Dealer, Administration > Dealer > User (route tidak ada).
 *
 * Sales Dealer (ms_sales_dealer_*)
 *  - Role super-admin & dealer-user (role default saat login pertama, config sales.default_role.dealer).
 *  - Menu Home.
 *
 * Super Admin Sales pertama dibuat manual: php artisan sales:assign-role atpm <username> super-admin
 * Idempotent: data yang sudah ada tidak ditimpa.
 */
return new class extends Migration
{
    private const BY = 'SYSTEM-MIGRATION';

    /** slug => [name, group] */
    private const ATPM_PERMISSIONS = [
        'rbac.user-role.manage'                        => ['Kelola Akses User (role & menu per user)', 'Access Control'],
        'rbac.role.manage'                             => ['Kelola Role', 'Access Control'],
        'rbac.permission.manage'                       => ['Kelola Permission', 'Access Control'],
        'rbac.menu.manage'                             => ['Kelola Menu', 'Access Control'],
        'config.view'                                  => ['Config - Lihat', 'System Setup'],
        // atpm-user.view & atpm-user.sync dihapus: menu Administration > ATPM > User tidak dipakai lagi (lihat 2026_11_24_000001)
        'email-scheduler-ckd.view'                     => ['Email Scheduler CKD - Lihat', 'Email Scheduler'],
        'email-scheduler-ckd.manage'                   => ['Email Scheduler CKD - Ubah email tujuan & kirim ulang', 'Email Scheduler'],
        'report.sales-person-history.view'             => ['Sales Person History Report', 'Report'],
        'report.sales-person-productivity.view'        => ['Sales Person Productivity Report', 'Report'],
        'report.sales-person-national-productivity.view' => ['National Sales Person Productivity Report', 'Report'],
        'report.sales-person-list.view'                => ['Sales Person List Report', 'Report'],
    ];

    /** route_name menu lama => permission_slug */
    private const ATPM_MENU_PERMISSION = [
        'sales.atpm.system_setup.config'                      => 'config.view',
        'sales.atpm.email_scheduler.delivery_ckd'             => 'email-scheduler-ckd.view',
        'sales.atpm.report.sales_person_history'              => 'report.sales-person-history.view',
        'sales.atpm.report.sales_person_productivity'         => 'report.sales-person-productivity.view',
        'sales.atpm.report.sales_person_national_productivity' => 'report.sales-person-national-productivity.view',
        'sales.atpm.report.sales_person_list'                 => 'report.sales-person-list.view',
        'sales.atpm.system_setup.sales_atpm_master_menu'      => 'rbac.menu.manage',
        'sales.atpm.system_setup.master_permission'           => 'rbac.permission.manage',
        'sales.atpm.system_setup.master_menu_dealer'          => 'rbac.menu.manage',
    ];

    /** Menu lama (kolom name) yang digantikan halaman Access Control → dinonaktifkan */
    private const ATPM_LEGACY_MENUS = [
        'sales.atpm.system_setup.sales_atpm_master_menu',
        'sales.atpm.system_setup.master_permission',
        'sales.atpm.system_setup.master_menu_dealer',
        'sales.atpm.administration.dealer_user',
        'dealer',
    ];

    private const ACCESS_MENUS = [
        ['access-control.users',       'User Access', 'sales.atpm.access.users',       'rbac.user-role.manage', 1],
        ['access-control.roles',       'Role',        'sales.atpm.access.roles',       'rbac.role.manage',      2],
        ['access-control.permissions', 'Permission',  'sales.atpm.access.permissions', 'rbac.permission.manage', 3],
        ['access-control.menus',       'Menu',        'sales.atpm.access.menus',       'rbac.menu.manage',      4],
    ];

    public function up(): void
    {
        $now = now();

        // ===================== SALES ATPM =====================
        $p = 'ms_sales_atpm_';

        foreach (self::ATPM_PERMISSIONS as $slug => [$name, $group]) {
            if (!DB::table($p . 'permissions')->where('slug', $slug)->exists()) {
                DB::table($p . 'permissions')->insert([
                    'slug' => $slug, 'name' => $name, 'group_name' => $group,
                    'created_by' => self::BY, 'date_created' => $now,
                ]);
            }
        }

        // Role default (sudah dibuat migration 2026_11_06_000006, dipastikan ada)
        $this->ensureRole($p, 'super-admin', 'Super Admin', 1, true, 'Akses penuh ke seluruh menu & fitur Sales ATPM (bypass RBAC).');
        $userRoleId = $this->ensureRole($p, 'user', 'User', 50, false, 'User Sales ATPM. Menu diatur per role / per user (Access Control).');

        // Role "user" → semua permission fitur (bukan Access Control) agar akses user lama tetap sama
        $featurePermIds = DB::table($p . 'permissions')->where('slug', 'not like', 'rbac.%')->pluck('id');
        foreach ($featurePermIds as $permId) {
            DB::table($p . 'role_permissions')->insertOrIgnore([
                'role_id' => $userRoleId, 'permission_id' => $permId, 'created_by' => self::BY, 'date_created' => $now,
            ]);
        }

        // permission_slug di menu fitur yang sudah ada (hanya jika masih kosong)
        foreach (self::ATPM_MENU_PERMISSION as $route => $slug) {
            DB::table($p . 'menus')->where('route_name', $route)->whereNull('permission_slug')
                ->update(['permission_slug' => $slug, 'updated_by' => self::BY, 'date_updated' => $now]);
        }

        // Menu Administration > Access Control
        $adminId = DB::table($p . 'menus')->where('name', 'administration')->value('id')
            ?? DB::table($p . 'menus')->insertGetId([
                'parent_id' => null, 'name' => 'administration', 'label' => 'Administration', 'icon' => 'bi bi-building',
                'sort_order' => 3, 'is_active' => true, 'created_by' => self::BY, 'date_created' => $now,
            ]);

        $accessId = DB::table($p . 'menus')->where('name', 'access-control')->value('id')
            ?? DB::table($p . 'menus')->insertGetId([
                'parent_id' => $adminId, 'name' => 'access-control', 'label' => 'Access Control', 'icon' => 'bi bi-shield-lock',
                'sort_order' => 1, 'is_active' => true, 'created_by' => self::BY, 'date_created' => $now,
            ]);

        foreach (self::ACCESS_MENUS as [$name, $label, $route, $slug, $order]) {
            if (!DB::table($p . 'menus')->where('name', $name)->exists()) {
                DB::table($p . 'menus')->insert([
                    'parent_id' => $accessId, 'name' => $name, 'label' => $label, 'route_name' => $route,
                    'permission_slug' => $slug, 'sort_order' => $order, 'is_active' => true,
                    'created_by' => self::BY, 'date_created' => $now,
                ]);
            }
        }

        // Nonaktifkan menu lama yang digantikan Access Control (data tidak dihapus)
        DB::table($p . 'menus')->whereIn('name', self::ATPM_LEGACY_MENUS)->where('is_active', true)
            ->update(['is_active' => false, 'updated_by' => self::BY, 'date_updated' => $now]);

        // ===================== SALES DEALER =====================
        $d = 'ms_sales_dealer_';

        $this->ensureRole($d, 'super-admin', 'Super Admin', 1, true, 'Akses penuh ke seluruh menu & fitur Sales Dealer (bypass RBAC).');
        $dealerRoleId = $this->ensureRole($d, 'dealer-user', 'Dealer User', 50, false, 'Role default user Dealer Sales (diberikan otomatis saat login pertama).');

        $homeId = DB::table($d . 'menus')->where('name', 'sales.dealer.home')->value('id')
            ?? DB::table($d . 'menus')->insertGetId([
                'parent_id' => null, 'name' => 'sales.dealer.home', 'label' => 'Home', 'route_name' => 'sales.dealer.home',
                'icon' => 'bi bi-house-fill', 'sort_order' => 1, 'is_active' => true, 'created_by' => self::BY, 'date_created' => $now,
            ]);

        DB::table($d . 'menu_roles')->insertOrIgnore([
            'menu_id' => $homeId, 'role_id' => $dealerRoleId, 'created_by' => self::BY, 'date_created' => $now,
        ]);

        $this->flushSalesRbacCache();
    }

    public function down(): void
    {
        $p = 'ms_sales_atpm_';

        DB::table($p . 'menus')->whereIn('name', self::ATPM_LEGACY_MENUS)->update(['is_active' => true]);

        $accessIds = DB::table($p . 'menus')->whereIn('name', array_merge(array_column(self::ACCESS_MENUS, 0), ['access-control']))->pluck('id');
        DB::table($p . 'menu_roles')->whereIn('menu_id', $accessIds)->delete();
        DB::table($p . 'menu_users')->whereIn('menu_id', $accessIds)->delete();
        DB::table($p . 'menus')->whereIn('id', $accessIds)->delete();

        DB::table($p . 'menus')->whereIn('route_name', array_keys(self::ATPM_MENU_PERMISSION))->update(['permission_slug' => null]);

        $permIds = DB::table($p . 'permissions')->whereIn('slug', array_keys(self::ATPM_PERMISSIONS))->pluck('id');
        DB::table($p . 'role_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table($p . 'permissions')->whereIn('id', $permIds)->delete();

        // Data RBAC Sales Dealer ikut hilang saat tabelnya di-drop oleh migration 2026_11_20_000001
        $this->flushSalesRbacCache();
    }

    private function ensureRole(string $prefix, string $slug, string $name, int $level, bool $isSystem, string $description): int
    {
        $id = DB::table($prefix . 'roles')->where('slug', $slug)->value('id');
        if ($id) {
            return (int) $id;
        }

        return (int) DB::table($prefix . 'roles')->insertGetId([
            'slug' => $slug, 'name' => $name, 'level' => $level, 'is_system' => $isSystem, 'description' => $description,
            'is_active' => true, 'created_by' => self::BY, 'date_created' => now(),
        ]);
    }

    /** Naikkan versi cache RBAC Sales (lihat RbacEngine::flushCache) */
    private function flushSalesRbacCache(): void
    {
        foreach (['atpm', 'dealer'] as $type) {
            $key = "sales_rbac_version_{$type}";
            Cache::forever($key, (int) Cache::get($key, 0) + 1);
        }
    }
};
