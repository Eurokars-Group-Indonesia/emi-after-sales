<?php

use App\Services\Afsel\AfselRbacService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menu General Setting > Category Service (ATPM):
 *  - Cat Service Vehicle 1 → tblcategory_vehicles   (route aftersales.atpm.category_vehicle_1)
 *  - Cat Service Vehicle 2 → tblcategory_2_vehicles (route aftersales.atpm.category_vehicle_2)
 *  - permission view/create/edit/delete untuk masing-masing menu
 *  - Document Numbering untuk kd_category_vehicles & kd_category_2_vehicles
 *  - permission & menu diberikan ke role ATPM Admin (Super Admin otomatis bypass).
 * Menu yang sudah dibuat lewat halaman Access Control (nama sama) hanya di-update route & permission-nya.
 */
return new class extends Migration
{
    private const BY = 'SYSTEM-MIGRATION';

    private const DOC_TABLES = ['tblcategory_vehicles', 'tblcategory_2_vehicles'];

    private const PERMISSIONS = [
        'category-vehicle-1.view'   => ['Lihat Cat Service Vehicle 1',  'Melihat master Category Service Vehicle 1 (tblcategory_vehicles).'],
        'category-vehicle-1.create' => ['Tambah Cat Service Vehicle 1', 'Menambah Category Service Vehicle 1.'],
        'category-vehicle-1.edit'   => ['Ubah Cat Service Vehicle 1',   'Mengubah Category Service Vehicle 1.'],
        'category-vehicle-1.delete' => ['Hapus Cat Service Vehicle 1',  'Menghapus Category Service Vehicle 1 yang belum dipakai.'],
        'category-vehicle-2.view'   => ['Lihat Cat Service Vehicle 2',  'Melihat master Category Service Vehicle 2 (tblcategory_2_vehicles).'],
        'category-vehicle-2.create' => ['Tambah Cat Service Vehicle 2', 'Menambah Category Service Vehicle 2.'],
        'category-vehicle-2.edit'   => ['Ubah Cat Service Vehicle 2',   'Mengubah Category Service Vehicle 2.'],
        'category-vehicle-2.delete' => ['Hapus Cat Service Vehicle 2',  'Menghapus Category Service Vehicle 2 yang belum dipakai.'],
    ];

    /** [name, label, parent, route, icon, sort_order, permission_slug] — parent dulu. */
    private const MENUS = [
        ['afsel.atpm.general-setting',                'General Setting',       null,                                  null,                                  'bi bi-gear',  3, null],
        ['afsel.atpm.category-service',               'Category Service',      'afsel.atpm.general-setting',          null,                                  'bi bi-tools', 1, null],
        ['afsel.atpm.category-service-vehicle-1',     'Cat Service Vehicle 1', 'afsel.atpm.category-service',         'aftersales.atpm.category_vehicle_1',  null,          1, 'category-vehicle-1.view'],
        ['afsel.atpm.category-service-vehicle-2',     'Cat Service Vehicle 2', 'afsel.atpm.category-service',         'aftersales.atpm.category_vehicle_2',  null,          2, 'category-vehicle-2.view'],
    ];

    public function up(): void
    {
        $now = now();

        // ---------- Document Numbering ----------
        foreach (self::DOC_TABLES as $table) {
            if (!DB::table('ms_afsel_atpm_document_number')->where('table_name', $table)->exists()) {
                DB::table('ms_afsel_atpm_document_number')->insert([
                    'table_name'   => $table,
                    'last_number'  => 0,      // nomor pertama yang dipakai = 1
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
                    'slug' => $slug, 'name' => $name, 'group_name' => 'General Setting', 'description' => $desc,
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
                DB::table('ms_afsel_atpm_role_permissions')->insertOrIgnore([
                    'role_id' => $roleId, 'permission_id' => $permId, 'created_by' => self::BY, 'date_created' => $now,
                ]);
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
     * Permission & route menu dilepas. Menu tidak dihapus (bisa jadi dibuat manual lewat Access Control),
     * counter Document Numbering juga dibiarkan agar nomor yang sudah terpakai tidak terulang.
     */
    public function down(): void
    {
        $permIds = DB::table('ms_afsel_atpm_permissions')->whereIn('slug', array_keys(self::PERMISSIONS))->pluck('id');

        DB::table('ms_afsel_atpm_role_permissions')->whereIn('permission_id', $permIds)->delete();
        if (DB::getSchemaBuilder()->hasTable('ms_afsel_atpm_user_permissions')) {
            DB::table('ms_afsel_atpm_user_permissions')->whereIn('permission_id', $permIds)->delete();
        }
        DB::table('ms_afsel_atpm_permissions')->whereIn('id', $permIds)->delete();

        DB::table('ms_afsel_atpm_menus')
            ->whereIn('name', array_column(self::MENUS, 0))
            ->whereNotNull('route_name')
            ->update(['route_name' => null, 'permission_slug' => null, 'updated_by' => self::BY, 'date_updated' => now()]);

        app(AfselRbacService::class)->flushCache('atpm');
    }
};
