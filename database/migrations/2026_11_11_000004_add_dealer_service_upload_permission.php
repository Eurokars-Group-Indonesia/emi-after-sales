<?php

use App\Services\Afsel\AfselRbacService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Hak akses menu Upload Service (Dealer):
 *  - permission dealer `service-data.upload`
 *  - menu `service.upload` (sudah dibuat manual) → route aftersales.dealer.service.service-upload + permission_slug
 *  - permission diberikan ke Dealer Admin & role yang sudah punya akses menu Upload Service.
 */
return new class extends Migration
{
    private const BY     = 'SYSTEM-MIGRATION';
    private const ROUTE  = 'aftersales.dealer.service.service-upload';
    private const PARENT = 'service';
    private const MENU   = 'service.upload';
    private const SLUG   = 'service-data.upload';

    public function up(): void
    {
        $now = now();

        $permId = DB::table('ms_afsel_dealer_permissions')->where('slug', self::SLUG)->value('id')
            ?: DB::table('ms_afsel_dealer_permissions')->insertGetId([
                'slug' => self::SLUG, 'name' => 'Upload Service', 'group_name' => 'Service',
                'description' => 'Upload data service (Excel) ke tblcustomer & tblkpi dealer sendiri.',
                'created_by' => self::BY, 'date_created' => $now,
            ]);

        $menus = DB::table('ms_afsel_dealer_menus');

        $parentId = (clone $menus)->where('name', self::PARENT)->value('id')
            ?: (clone $menus)->insertGetId([
                'name' => self::PARENT, 'label' => 'Service', 'icon' => 'bi bi-tools',
                'sort_order' => 2, 'is_active' => true, 'created_by' => self::BY, 'date_created' => $now,
            ]);

        $menuId = (clone $menus)->where('name', self::MENU)->value('id');
        if ($menuId) {
            (clone $menus)->where('id', $menuId)->update([
                'route_name' => self::ROUTE, 'url' => null, 'permission_slug' => self::SLUG,
                'updated_by' => self::BY, 'date_updated' => $now,
            ]);
        } else {
            $menuId = (clone $menus)->insertGetId([
                'parent_id' => $parentId, 'name' => self::MENU, 'label' => 'Upload Service',
                'route_name' => self::ROUTE, 'sort_order' => 1, 'permission_slug' => self::SLUG,
                'is_active' => true, 'created_by' => self::BY, 'date_created' => $now,
            ]);
        }

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
        $permIds = DB::table('ms_afsel_dealer_permissions')->where('slug', self::SLUG)->pluck('id');

        DB::table('ms_afsel_dealer_role_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('ms_afsel_dealer_permissions')->whereIn('id', $permIds)->delete();

        DB::table('ms_afsel_dealer_menus')->where('name', self::MENU)->update([
            'route_name' => null, 'permission_slug' => null, 'updated_by' => self::BY, 'date_updated' => now(),
        ]);

        app(AfselRbacService::class)->flushCache('dealer');
    }
};
