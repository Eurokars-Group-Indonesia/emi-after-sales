<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Halaman Access Configuration Sales (Per Role / Per User / Preview), seperti eurokars-exam.
 * Tanpa tabel baru — hanya 2 baris data:
 *   ms_sales_atpm_permissions : rbac.menu-access.manage
 *   ms_sales_atpm_menus       : access-control.menu-access (Administration > Access Control > Access Configuration)
 * Data akses tetap disimpan di ms_sales_{atpm|dealer}_menu_roles & ms_sales_{atpm|dealer}_menu_users.
 */
return new class extends Migration
{
    private const BY = 'SYSTEM-MIGRATION';
    private const SLUG = 'rbac.menu-access.manage';
    private const MENU = 'access-control.menu-access';

    public function up(): void
    {
        $now = now();

        if (!DB::table('ms_sales_atpm_permissions')->where('slug', self::SLUG)->exists()) {
            DB::table('ms_sales_atpm_permissions')->insert([
                'slug'         => self::SLUG,
                'name'         => 'Kelola Access Configuration (menu per role & per user)',
                'group_name'   => 'Access Control',
                'created_by'   => self::BY,
                'date_created' => $now,
            ]);
        }

        $parentId = DB::table('ms_sales_atpm_menus')->where('name', 'access-control')->value('id');

        if ($parentId && !DB::table('ms_sales_atpm_menus')->where('name', self::MENU)->exists()) {
            DB::table('ms_sales_atpm_menus')->insert([
                'parent_id'       => $parentId,
                'name'            => self::MENU,
                'label'           => 'Access Configuration',
                'route_name'      => 'sales.atpm.access.menu-access',
                'icon'            => null,
                'permission_slug' => self::SLUG,
                'sort_order'      => 2,
                'is_active'       => true,
                'created_by'      => self::BY,
                'date_created'    => $now,
            ]);
        }

        $this->flushSalesRbacCache();
    }

    public function down(): void
    {
        $menuId = DB::table('ms_sales_atpm_menus')->where('name', self::MENU)->value('id');
        if ($menuId) {
            DB::table('ms_sales_atpm_menu_roles')->where('menu_id', $menuId)->delete();
            DB::table('ms_sales_atpm_menu_users')->where('menu_id', $menuId)->delete();
            DB::table('ms_sales_atpm_menus')->where('id', $menuId)->delete();
        }

        $permId = DB::table('ms_sales_atpm_permissions')->where('slug', self::SLUG)->value('id');
        if ($permId) {
            DB::table('ms_sales_atpm_role_permissions')->where('permission_id', $permId)->delete();
            DB::table('ms_sales_atpm_permissions')->where('id', $permId)->delete();
        }

        $this->flushSalesRbacCache();
    }

    private function flushSalesRbacCache(): void
    {
        foreach (['atpm', 'dealer'] as $type) {
            $key = "sales_rbac_version_{$type}";
            Cache::forever($key, (int) Cache::get($key, 0) + 1);
        }
    }
};
