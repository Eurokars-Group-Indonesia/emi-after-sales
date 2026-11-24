<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Hapus menu Sales ATPM "Administration > ATPM > User" beserta permission-nya (sudah tidak dipakai).
 *
 *  - ms_sales_atpm_menus       : menu route sales.atpm.user_index + grup induknya (ATPM) jika jadi kosong
 *  - ms_sales_atpm_menu_roles  : relasi menu tsb ke role
 *  - ms_sales_atpm_menu_users  : override menu tsb per user
 *  - ms_sales_atpm_permissions : atpm-user.view, atpm-user.sync (+ ms_sales_atpm_role_permissions)
 *
 * Hanya RBAC Sales (ms_sales_atpm_*). Permission atpm-user.* milik After Sales (ms_afsel_atpm_*) TIDAK disentuh.
 * Idempotent: aman dijalankan di database yang datanya sudah tidak ada.
 */
return new class extends Migration
{
    private const P           = 'ms_sales_atpm_';
    private const ROUTE       = 'sales.atpm.user_index';
    private const PERMISSIONS = [
        'atpm-user.view' => ['ATPM User - Lihat', 'Administration'],
        'atpm-user.sync' => ['ATPM User - Sync dari WRS Sales', 'Administration'],
    ];

    public function up(): void
    {
        $p = self::P;

        DB::transaction(function () use ($p) {
            $menus     = DB::table($p . 'menus')->where('route_name', self::ROUTE)->get(['id', 'parent_id']);
            $menuIds   = $menus->pluck('id')->all();
            $parentIds = $menus->pluck('parent_id')->filter()->unique()->all();

            $this->deleteMenus($menuIds);

            // Grup induk (mis. ATPM) ikut dihapus jika sekarang tidak punya sub menu & bukan halaman
            $emptyParents = DB::table($p . 'menus')
                ->whereIn('id', $parentIds)
                ->whereNull('route_name')
                ->whereNull('url')
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from($p . 'menus as c')->whereColumn('c.parent_id', $p . 'menus.id'))
                ->pluck('id')
                ->all();
            $this->deleteMenus($emptyParents);

            $permIds = DB::table($p . 'permissions')->whereIn('slug', array_keys(self::PERMISSIONS))->pluck('id');
            DB::table($p . 'role_permissions')->whereIn('permission_id', $permIds)->delete();
            DB::table($p . 'permissions')->whereIn('id', $permIds)->delete();
        });

        $this->flushSalesRbacCache();
    }

    public function down(): void
    {
        $p   = self::P;
        $by  = 'SYSTEM-MIGRATION';
        $now = now();

        foreach (self::PERMISSIONS as $slug => [$name, $group]) {
            if (!DB::table($p . 'permissions')->where('slug', $slug)->exists()) {
                DB::table($p . 'permissions')->insert([
                    'slug' => $slug, 'name' => $name, 'group_name' => $group, 'created_by' => $by, 'date_created' => $now,
                ]);
            }
        }

        $adminId = DB::table($p . 'menus')->where('name', 'administration')->value('id');
        if ($adminId && !DB::table($p . 'menus')->where('route_name', self::ROUTE)->exists()) {
            $atpmId = DB::table($p . 'menus')->where('name', 'atpm')->value('id')
                ?? DB::table($p . 'menus')->insertGetId([
                    'parent_id' => $adminId, 'name' => 'atpm', 'label' => 'ATPM', 'icon' => 'bi bi-sliders',
                    'sort_order' => 2, 'is_active' => true, 'created_by' => $by, 'date_created' => $now,
                ]);

            DB::table($p . 'menus')->insert([
                'parent_id' => $atpmId, 'name' => self::ROUTE, 'label' => 'User', 'route_name' => self::ROUTE,
                'permission_slug' => 'atpm-user.view', 'sort_order' => 1, 'is_active' => true,
                'created_by' => $by, 'date_created' => $now,
            ]);
        }

        $this->flushSalesRbacCache();
    }

    private function deleteMenus(array $ids): void
    {
        if (!$ids) {
            return;
        }

        DB::table(self::P . 'menu_roles')->whereIn('menu_id', $ids)->delete();
        DB::table(self::P . 'menu_users')->whereIn('menu_id', $ids)->delete();
        DB::table(self::P . 'menus')->whereIn('id', $ids)->delete();
    }

    /** Naikkan versi cache RBAC Sales ATPM (lihat RbacEngine::flushCache) */
    private function flushSalesRbacCache(): void
    {
        $key = 'sales_rbac_version_atpm';
        Cache::forever($key, (int) Cache::get($key, 0) + 1);
    }
};
