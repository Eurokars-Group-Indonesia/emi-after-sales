<?php

use App\Services\Afsel\AfselRbacService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menu & permission Sync WRS Sales (ATPM After Sales):
 *   Administration > Utility > Monitoring Sync WRS Sales > Sync Inventory   (aftersales.atpm.sync_wrs.inventory)
 *   Administration > Utility > Monitoring Sync WRS Sales > Sync Faktur      (aftersales.atpm.sync_wrs.faktur)
 *   Administration > Utility > Koneksi Database Sync                        (aftersales.atpm.sync_connection)
 * Menu "utility" & "monitoring.sync.wrs-sales" yang sudah dibuat lewat Access Control dipakai ulang (tidak dibuat dobel).
 * Permission & menu diberikan ke role ATPM Admin (Super Admin otomatis bypass).
 * Juga mendaftarkan Document Numbering untuk tblmodel_color, tbltype, tblspk_retail, tblinventory.
 */
return new class extends Migration
{
    private const BY = 'SYSTEM-MIGRATION';

    private const DOC_TABLES = ['tblmodel_color', 'tbltype', 'tblspk_retail', 'tblinventory'];

    private const PERMISSIONS = [
        'sync-wrs.view'          => ['Lihat Monitoring Sync WRS Sales', 'Melihat status & riwayat Sync Inventory / Sync Faktur dari WRS Sales.'],
        'sync-wrs.run'           => ['Jalankan Sync WRS Sales', 'Menjalankan Sync Inventory / Sync Faktur secara manual.'],
        'sync-connection.view'   => ['Lihat Koneksi Database Sync', 'Melihat setting koneksi database sumber sync.'],
        'sync-connection.manage' => ['Kelola Koneksi Database Sync', 'Menambah, mengubah & test koneksi database sumber sync.'],
    ];

    /** [name, label, parent, route, icon, sort_order, permission_slug] — parent dulu. */
    private const MENUS = [
        ['administration',                        'Administration',            null,                         null,                                  'bi bi-building',    2, null],
        ['utility',                               'Utility',                   'administration',             null,                                  'bi bi-boxes',       1, null],
        ['monitoring.sync.wrs-sales',             'Monitoring Sync WRS Sales', 'utility',                    null,                                  'bi bi-arrow-repeat', 1, null],
        ['monitoring.sync.wrs-sales.inventory',   'Sync Inventory',            'monitoring.sync.wrs-sales',  'aftersales.atpm.sync_wrs.inventory',  null,                1, 'sync-wrs.view'],
        ['monitoring.sync.wrs-sales.faktur',      'Sync Faktur',               'monitoring.sync.wrs-sales',  'aftersales.atpm.sync_wrs.faktur',     null,                2, 'sync-wrs.view'],
        ['utility.sync-connection',               'Koneksi Database Sync',     'utility',                    'aftersales.atpm.sync_connection',     null,                2, 'sync-connection.view'],
    ];

    public function up(): void
    {
        $now = now();

        // ---------- Document Numbering ----------
        foreach (self::DOC_TABLES as $table) {
            if (!DB::table('ms_afsel_atpm_document_number')->where('table_name', $table)->exists()) {
                DB::table('ms_afsel_atpm_document_number')->insert([
                    'table_name'   => $table,
                    'last_number'  => 0,
                    'last_date'    => null,
                    'created_by'   => self::BY,
                    'date_created' => $now,
                ]);
            }
        }

        // ---------- Permissions ----------
        $permIds = [];
        foreach (self::PERMISSIONS as $slug => [$name, $desc]) {
            $permIds[] = DB::table('ms_afsel_atpm_permissions')->where('slug', $slug)->value('id')
                ?: DB::table('ms_afsel_atpm_permissions')->insertGetId([
                    'slug' => $slug, 'name' => $name, 'group_name' => 'Utility', 'description' => $desc,
                    'created_by' => self::BY, 'date_created' => $now,
                ]);
        }

        // ---------- Menus ----------
        $menuIds = [];
        foreach (self::MENUS as [$name, $label, $parent, $route, $icon, $order, $perm]) {
            $parentId = $parent ? ($menuIds[$parent] ?? null) : null;
            $menuId   = DB::table('ms_afsel_atpm_menus')->where('name', $name)->value('id');

            if ($menuId) {
                if ($route) {
                    DB::table('ms_afsel_atpm_menus')->where('id', $menuId)->update([
                        'route_name' => $route, 'url' => null, 'permission_slug' => $perm,
                        'updated_by' => self::BY, 'date_updated' => $now,
                    ]);
                }
            } else {
                $menuId = DB::table('ms_afsel_atpm_menus')->insertGetId([
                    'parent_id' => $parentId, 'name' => $name, 'label' => $label, 'route_name' => $route,
                    'icon' => $icon, 'sort_order' => $order, 'permission_slug' => $perm, 'is_active' => true,
                    'created_by' => self::BY, 'date_created' => $now,
                ]);
            }

            $menuIds[$name] = (int) $menuId;
        }

        // ---------- Role Admin ----------
        foreach (DB::table('ms_afsel_atpm_roles')->where('slug', 'admin')->pluck('id') as $roleId) {
            foreach ($permIds as $permId) {
                if (!DB::table('ms_afsel_atpm_role_permissions')->where(['role_id' => $roleId, 'permission_id' => $permId])->exists()) {
                    DB::table('ms_afsel_atpm_role_permissions')->insert([
                        'role_id' => $roleId, 'permission_id' => $permId, 'created_by' => self::BY, 'date_created' => $now,
                    ]);
                }
            }
            foreach ($menuIds as $menuId) {
                if (!DB::table('ms_afsel_atpm_menu_roles')->where(['role_id' => $roleId, 'menu_id' => $menuId])->exists()) {
                    DB::table('ms_afsel_atpm_menu_roles')->insert([
                        'role_id' => $roleId, 'menu_id' => $menuId, 'created_by' => self::BY, 'date_created' => $now,
                    ]);
                }
            }
        }

        app(AfselRbacService::class)->flushCache('atpm');
    }

    /**
     * Sengaja kosong: project ini tidak boleh menghapus data.
     * Untuk menyembunyikan menu, non-aktifkan / cabut lewat halaman Access Control.
     */
    public function down(): void
    {
    }
};
