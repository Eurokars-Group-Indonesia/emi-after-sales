<?php

use App\Services\Afsel\AfselRbacService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Hak akses menu Dealer Personel After Sales (login Dealer):
 *  - permission dealer personel.view / create / edit / status
 *  - menu `afsel.dealer.personel-after-sales` (parent) & `afsel.dealer.dealer-personel-after-sales`
 *    → route aftersales.dealer.personel.dealer-personel + permission_slug personel.view
 *  - permission diberikan ke role Dealer Admin & role yang sudah punya akses menu tsb,
 *    agar user yang sekarang bisa membuka menu tidak tiba-tiba kena 403.
 */
return new class extends Migration
{
    private const BY     = 'SYSTEM-MIGRATION';
    private const ROUTE  = 'aftersales.dealer.personel.dealer-personel';
    private const PARENT = 'afsel.dealer.personel-after-sales';
    private const MENU   = 'afsel.dealer.dealer-personel-after-sales';

    private const PERMISSIONS = [
        'personel.view'   => ['Lihat Dealer Personel', 'Melihat data personel after sales dealer sendiri.'],
        'personel.create' => ['Tambah Dealer Personel', 'Menambah personel after sales baru (status awal nonaktif).'],
        'personel.edit'   => ['Ubah Dealer Personel', 'Mengubah identitas, level, tanggal masuk & alamat personel.'],
        'personel.status' => ['Ubah Status & Data Keluar Personel', 'Menonaktifkan personel, tanggal & alasan keluar, blacklist dealer (aktivasi hanya oleh ATPM).'],
    ];

    public function up(): void
    {
        $now = now();

        // ---------- permission ----------
        $permIds = [];
        foreach (self::PERMISSIONS as $slug => [$name, $desc]) {
            $permIds[] = DB::table('ms_afsel_dealer_permissions')->where('slug', $slug)->value('id')
                ?: DB::table('ms_afsel_dealer_permissions')->insertGetId([
                    'slug' => $slug, 'name' => $name, 'group_name' => 'Personel', 'description' => $desc,
                    'created_by' => self::BY, 'date_created' => $now,
                ]);
        }

        // ---------- menu ----------
        $menus = DB::table('ms_afsel_dealer_menus');

        $parentId = (clone $menus)->where('name', self::PARENT)->value('id')
            ?: (clone $menus)->insertGetId([
                'name' => self::PARENT, 'label' => 'Personel After Sales', 'icon' => 'bi bi-person-badge',
                'sort_order' => 4, 'is_active' => true, 'created_by' => self::BY, 'date_created' => $now,
            ]);

        $menuId = (clone $menus)->where('name', self::MENU)->value('id');
        if ($menuId) {
            (clone $menus)->where('id', $menuId)->update([
                'route_name' => self::ROUTE, 'url' => null, 'permission_slug' => 'personel.view',
                'updated_by' => self::BY, 'date_updated' => $now,
            ]);
        } else {
            $menuId = (clone $menus)->insertGetId([
                'parent_id' => $parentId, 'name' => self::MENU, 'label' => 'Dealer Personel After Sales',
                'route_name' => self::ROUTE, 'sort_order' => 1, 'permission_slug' => 'personel.view',
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

        DB::table('ms_afsel_dealer_menus')->where('name', self::MENU)->update([
            'route_name' => null, 'permission_slug' => null, 'updated_by' => self::BY, 'date_updated' => now(),
        ]);

        app(AfselRbacService::class)->flushCache('dealer');
    }
};
