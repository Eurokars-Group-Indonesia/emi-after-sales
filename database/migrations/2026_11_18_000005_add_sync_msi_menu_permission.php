<?php

use App\Services\Afsel\AfselRbacService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Sync MSI (Customer CSI):
 *  - Menu Administration > Utility > Monitoring Sync MSI (CSI)   (aftersales.atpm.sync_msi)
 *  - Permission sync-msi.view & sync-msi.run → role ATPM Admin (Super Admin otomatis bypass)
 *  - Document Numbering: tblcustomer_log, tblwork_planning, tblwork_planning_pk_id
 *  - Baris koneksi MSI di ms_sync_db_connection, diisi dari .env DB_MSI_* (hanya jika DB_MSI_HOST diisi & baris belum ada).
 *    Jika .env kosong, tambahkan lewat menu Utility > Koneksi Database Sync (nama MSI, driver SQL Server).
 */
return new class extends Migration
{
    private const BY = 'SYSTEM-MIGRATION';

    private const DOC_TABLES = ['tblcustomer_log', 'tblwork_planning', 'tblwork_planning_pk_id'];

    private const PERMISSIONS = [
        'sync-msi.view' => ['Lihat Monitoring Sync MSI', 'Melihat status & riwayat Sync MSI (Customer CSI).'],
        'sync-msi.run'  => ['Jalankan Sync MSI', 'Menjalankan Sync MSI (Customer CSI) secara manual, termasuk memilih rentang tanggal.'],
    ];

    /** [name, label, parent, route, icon, sort_order, permission_slug] — parent dulu. */
    private const MENUS = [
        ['administration',      'Administration',            null,             null,                       'bi bi-building', 2, null],
        ['utility',             'Utility',                   'administration', null,                       'bi bi-boxes',    1, null],
        ['monitoring.sync.msi', 'Monitoring Sync MSI (CSI)', 'utility',        'aftersales.atpm.sync_msi', 'bi bi-send',     3, 'sync-msi.view'],
    ];

    public function up(): void
    {
        $now = now();

        // ---------- Document Numbering ----------
        foreach (self::DOC_TABLES as $table) {
            if (!DB::table('ms_afsel_atpm_document_number')->where('table_name', $table)->exists()) {
                DB::table('ms_afsel_atpm_document_number')->insert([
                    'table_name' => $table, 'last_number' => 0, 'last_date' => null,
                    'created_by' => self::BY, 'date_created' => $now,
                ]);
            }
        }

        // ---------- Koneksi MSI (SQL Server) ----------
        $host = env('DB_MSI_HOST');
        if (!empty($host) && !DB::table('ms_sync_db_connection')->where('name', 'MSI')->exists()) {
            $password = (string) env('DB_MSI_PASSWORD', '');
            DB::table('ms_sync_db_connection')->insert([
                'name'         => 'MSI',
                'driver'       => 'sqlsrv',
                'hostname'     => $host,
                'username'     => (string) env('DB_MSI_USERNAME', ''),
                'password'     => $password !== '' ? Crypt::encryptString($password) : null,
                'database'     => (string) env('DB_MSI_DATABASE', ''),
                'port'         => (string) env('DB_MSI_PORT', '1433'),
                'remarks'      => 'Database MSI / Customer CSI (SQL Server), tujuan Sync MSI. Diisi otomatis dari .env DB_MSI_*.',
                'is_active'    => true,
                'created_by'   => self::BY,
                'date_created' => $now,
            ]);
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
