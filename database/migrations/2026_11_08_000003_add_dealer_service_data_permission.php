<?php

use App\Services\Afsel\AfselRbacService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Hak akses menu Data Service (Dealer):
 *  - permission dealer `service-data.view` & `service-data.export`
 *  - menu `service` (parent) & `service.index` → route dealer.service.service-data + permission_slug
 *  - permission diberikan ke Dealer Admin & role yang sudah punya akses menu Data Service,
 *    agar user yang sekarang bisa membuka menu tidak tiba-tiba kena 403.
 */
return new class extends Migration
{
    private const BY    = 'SYSTEM-MIGRATION';
    private const ROUTE = 'dealer.service.service-data';

    private const PERMISSIONS = [
        'service-data.view'   => ['Lihat Data Service', 'Melihat data service di dealer sendiri.'],
        'service-data.export' => ['Download Excel Data Service', 'Download data service dealer sendiri ke Excel (.xlsx).'],
    ];

    public function up(): void
    {
        $now = now();

        // ---------- permission ----------
        $permIds = [];
        foreach (self::PERMISSIONS as $slug => [$name, $description]) {
            $id = DB::table('ms_afsel_dealer_permissions')->where('slug', $slug)->value('id');
            if (!$id) {
                $id = DB::table('ms_afsel_dealer_permissions')->insertGetId([
                    'slug' => $slug, 'name' => $name, 'group_name' => 'Service', 'description' => $description,
                    'created_by' => self::BY, 'date_created' => $now,
                ]);
            }
            $permIds[] = $id;
        }

        // ---------- menu ----------
        $menus = DB::table('ms_afsel_dealer_menus');

        $parentId = (clone $menus)->where('name', 'service')->value('id');
        if (!$parentId) {
            $parentId = (clone $menus)->insertGetId([
                'name' => 'service', 'label' => 'Service', 'icon' => 'bi bi-tools',
                'sort_order' => 2, 'is_active' => true, 'created_by' => self::BY, 'date_created' => $now,
            ]);
        }

        $menuId = (clone $menus)->where('name', 'service.index')->value('id');
        if ($menuId) {
            (clone $menus)->where('id', $menuId)->update([
                'route_name'      => self::ROUTE,
                'permission_slug' => 'service-data.view',
                'updated_by'      => self::BY,
                'date_updated'    => $now,
            ]);
        } else {
            $menuId = (clone $menus)->insertGetId([
                'parent_id' => $parentId, 'name' => 'service.index', 'label' => 'Data Service',
                'route_name' => self::ROUTE, 'sort_order' => 2, 'permission_slug' => 'service-data.view',
                'is_active' => true, 'created_by' => self::BY, 'date_created' => $now,
            ]);
        }

        // ---------- role ----------
        $adminRoleIds = DB::table('ms_afsel_dealer_roles')->where('slug', 'admin')->pluck('id');
        $roleIds = DB::table('ms_afsel_dealer_menu_roles')->where('menu_id', $menuId)->pluck('role_id')
            ->merge($adminRoleIds)
            ->unique();

        foreach ($roleIds as $roleId) {
            foreach ($permIds as $permId) {
                if (!DB::table('ms_afsel_dealer_role_permissions')->where(['role_id' => $roleId, 'permission_id' => $permId])->exists()) {
                    DB::table('ms_afsel_dealer_role_permissions')->insert([
                        'role_id' => $roleId, 'permission_id' => $permId, 'created_by' => self::BY, 'date_created' => $now,
                    ]);
                }
            }
        }

        // Dealer Admin (menu '*') juga diberi akses menu Data Service
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
        $permIds = DB::table('ms_afsel_dealer_permissions')->whereIn('slug', array_keys(self::PERMISSIONS))->pluck('id');

        DB::table('ms_afsel_dealer_role_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('ms_afsel_dealer_permissions')->whereIn('id', $permIds)->delete();

        DB::table('ms_afsel_dealer_menus')->where('name', 'service.index')->update([
            'permission_slug' => null,
            'updated_by'      => self::BY,
            'date_updated'    => now(),
        ]);

        app(AfselRbacService::class)->flushCache('dealer');
    }
};
