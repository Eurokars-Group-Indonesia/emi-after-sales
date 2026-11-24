<?php

use App\Services\Afsel\AfselRbacService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Hak akses menu Customer Data (Dealer):
 *  - permission dealer `customer-data.view`
 *  - menu `customer` (parent) & `customer.index` → route dealer.customer.customer-data + permission_slug
 *  - permission diberikan ke role Dealer Admin & role yang sudah punya akses menu Customer,
 *    agar user yang sekarang bisa membuka menu tidak tiba-tiba kena 403.
 */
return new class extends Migration
{
    private const BY    = 'SYSTEM-MIGRATION';
    private const SLUG  = 'customer-data.view';
    private const ROUTE = 'dealer.customer.customer-data';

    public function up(): void
    {
        $now = now();

        // ---------- permission ----------
        $permId = DB::table('ms_afsel_dealer_permissions')->where('slug', self::SLUG)->value('id');
        if (!$permId) {
            $permId = DB::table('ms_afsel_dealer_permissions')->insertGetId([
                'slug'         => self::SLUG,
                'name'         => 'Lihat Customer Data',
                'group_name'   => 'Customer',
                'description'  => 'Melihat data customer milik dealer sendiri.',
                'created_by'   => self::BY,
                'date_created' => $now,
            ]);
        }

        // ---------- menu ----------
        $menus = DB::table('ms_afsel_dealer_menus');

        $parentId = (clone $menus)->where('name', 'customer')->value('id');
        if (!$parentId) {
            $parentId = (clone $menus)->insertGetId([
                'name' => 'customer', 'label' => 'Customer Data', 'icon' => 'bi bi-people',
                'sort_order' => 2, 'is_active' => true, 'created_by' => self::BY, 'date_created' => $now,
            ]);
        }

        $menuId = (clone $menus)->where('name', 'customer.index')->value('id');
        if ($menuId) {
            (clone $menus)->where('id', $menuId)->update([
                'route_name'      => self::ROUTE,
                'permission_slug' => self::SLUG,
                'updated_by'      => self::BY,
                'date_updated'    => $now,
            ]);
        } else {
            $menuId = (clone $menus)->insertGetId([
                'parent_id' => $parentId, 'name' => 'customer.index', 'label' => 'Customer',
                'route_name' => self::ROUTE, 'sort_order' => 1, 'permission_slug' => self::SLUG,
                'is_active' => true, 'created_by' => self::BY, 'date_created' => $now,
            ]);
        }

        // ---------- role ----------
        $adminRoleIds = DB::table('ms_afsel_dealer_roles')->where('slug', 'admin')->pluck('id');
        $roleIds = DB::table('ms_afsel_dealer_menu_roles')->where('menu_id', $menuId)->pluck('role_id')
            ->merge($adminRoleIds)
            ->unique();

        foreach ($roleIds as $roleId) {
            if (!DB::table('ms_afsel_dealer_role_permissions')->where(['role_id' => $roleId, 'permission_id' => $permId])->exists()) {
                DB::table('ms_afsel_dealer_role_permissions')->insert([
                    'role_id' => $roleId, 'permission_id' => $permId, 'created_by' => self::BY, 'date_created' => $now,
                ]);
            }
        }

        // Dealer Admin (menu '*') juga diberi akses menu Customer Data
        foreach ($adminRoleIds as $roleId) {
            foreach ([$parentId, $menuId] as $id) {
                if (!DB::table('ms_afsel_dealer_menu_roles')->where(['role_id' => $roleId, 'menu_id' => $id])->exists()) {
                    DB::table('ms_afsel_dealer_menu_roles')->insert([
                        'role_id' => $roleId, 'menu_id' => $id, 'created_by' => self::BY, 'date_created' => $now,
                    ]);
                }
            }
        }

        app(AfselRbacService::class)->flushCache('dealer');
    }

    public function down(): void
    {
        $permId = DB::table('ms_afsel_dealer_permissions')->where('slug', self::SLUG)->value('id');

        if ($permId) {
            DB::table('ms_afsel_dealer_role_permissions')->where('permission_id', $permId)->delete();
            DB::table('ms_afsel_dealer_permissions')->where('id', $permId)->delete();
        }

        DB::table('ms_afsel_dealer_menus')->where('name', 'customer.index')->update([
            'permission_slug' => null,
            'updated_by'      => self::BY,
            'date_updated'    => now(),
        ]);

        app(AfselRbacService::class)->flushCache('dealer');
    }
};
