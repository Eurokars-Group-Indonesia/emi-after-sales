<?php

namespace Database\Seeders;

use App\Services\Afsel\AfselRbacService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Akses modul Dealer Personel After Sales. Idempotent.
 *
 *   php artisan db:seed --class=AfselDealerPersonelSeeder
 *
 * - Permission dealer-personel.view, .sync, .edit → role admin (super admin sudah bypass).
 * - Menu dealer-personel-aftersales (dibuat dari halaman Access Control) dihubungkan ke route,
 *   hanya jika route / permission menu masih kosong, lalu diberikan ke role admin.
 * Data personel TIDAK di-seed: diambil lewat tombol "Sync dari WRS" di halaman Dealer Personel.
 * Sengaja tidak memanggil AfselRbacSeeder agar menu yang sudah dihapus admin tidak dibuat ulang.
 */
class AfselDealerPersonelSeeder extends Seeder
{
    private const BY = 'SYSTEM-SEEDER';

    private const PERMISSIONS = [
        ['dealer-personel.view', 'Lihat Dealer Personel After Sales'],
        ['dealer-personel.sync', 'Sync Dealer Personel dari WRS'],
        ['dealer-personel.edit', 'Ubah Active & Black List EMI Dealer Personel'],
    ];

    public function run(): void
    {
        $now = now();
        $adminRoleId = DB::table('ms_afsel_atpm_roles')->where('slug', 'admin')->value('id');

        foreach (self::PERMISSIONS as [$slug, $name]) {
            if (!DB::table('ms_afsel_atpm_permissions')->where('slug', $slug)->exists()) {
                DB::table('ms_afsel_atpm_permissions')->insert([
                    'slug' => $slug, 'name' => $name, 'group_name' => 'Personel After Sales',
                    'created_by' => self::BY, 'date_created' => $now,
                ]);
            }

            if ($adminRoleId) {
                DB::table('ms_afsel_atpm_role_permissions')->insertOrIgnore([
                    'role_id'       => $adminRoleId,
                    'permission_id' => DB::table('ms_afsel_atpm_permissions')->where('slug', $slug)->value('id'),
                    'created_by'    => self::BY,
                    'date_created'  => $now,
                ]);
            }
        }

        $menu = DB::table('ms_afsel_atpm_menus')->where('name', 'dealer-personel-aftersales');

        (clone $menu)->whereNull('route_name')->update([
            'route_name' => 'aftersales.atpm.dealer_personel', 'updated_by' => self::BY, 'date_updated' => $now,
        ]);
        (clone $menu)->whereNull('permission_slug')->update(['permission_slug' => 'dealer-personel.view']);

        if ($adminRoleId) {
            foreach (DB::table('ms_afsel_atpm_menus')->whereIn('name', ['personel-after-sales', 'dealer-personel-aftersales'])->pluck('id') as $menuId) {
                DB::table('ms_afsel_atpm_menu_roles')->insertOrIgnore([
                    'menu_id' => $menuId, 'role_id' => $adminRoleId, 'created_by' => self::BY, 'date_created' => $now,
                ]);
            }
        }

        app(AfselRbacService::class)->flushCache('atpm');
    }
}
