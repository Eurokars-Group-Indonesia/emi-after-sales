<?php

namespace Database\Seeders;

use App\Services\Afsel\AfselRbacService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder Level Personel After Sales + akses menunya.
 * Idempotent: aman dijalankan berulang.
 *
 *   php artisan db:seed --class=AfselDealerLevelPersonelSeeder
 *
 * 1. Data master afsel_dealer_level_personel (dari master_aftersales.tbllevel_personil_aftersales).
 * 2. Permission level-personel.* (diberikan ke role admin; super admin sudah bypass).
 *    Sengaja TIDAK memanggil AfselRbacSeeder agar menu yang sudah dihapus admin tidak dibuat ulang.
 * 3. Menu level-personel-aftersales yang dibuat dari halaman Access Control dihubungkan ke route
 *    (hanya jika route / permission menu masih kosong).
 */
class AfselDealerLevelPersonelSeeder extends Seeder
{
    private const DATA = [
        // [dealer_level_personel_id, dealer_level_personel, is_active, jenis]
        ['2012070600000000000000000006', 'Customer Relation Staff', true,  'Indirect'],
        ['2012070600000000000000000004', 'Foreman',                 true,  'Indirect'],
        ['2012070600000000000000000007', 'Parts Manager',           true,  'Indirect'],
        ['2012070600000000000000000008', 'Parts Staff',             true,  'Indirect'],
        ['2012070600000000000000000003', 'Service Advisor',         true,  'Indirect'],
        ['2012070600000000000000000001', 'Service Manager',         true,  'Indirect'],
        ['2012070600000000000000000005', 'Technician',              true,  'Direct'],
        ['2012070600000000000000000009', 'Washing Man',             true,  'Indirect'],
        ['2012070600000000000000000002', 'Workshop Manager',        true,  'Indirect'],
        ['2012081500000000000000000001', 'Office Boy',              false, 'Indirect'],
        ['2012111400000000000000000001', 'Kasir',                   true,  'Indirect'],
        ['2012111400000000000000000002', 'Helper',                  true,  'Indirect'],
        ['2013052900000000000000000001', 'Service Admin',           true,  'Indirect'],
        ['2013052900000000000000000002', 'General Manager',         true,  'Indirect'],
        ['2013052900000000000000000003', 'Part Supervisor',         true,  'Indirect'],
        ['2013052900000000000000000004', 'Part Counter',            true,  'Indirect'],
        ['2013052900000000000000000005', 'Part Sales',              true,  'Indirect'],
        ['2013052900000000000000000006', 'Part Keeper',             true,  'Indirect'],
    ];

    private const PERMISSIONS = [
        ['level-personel.view',   'Lihat Level Personel After Sales'],
        ['level-personel.create', 'Tambah Level Personel After Sales'],
        ['level-personel.edit',   'Ubah Level Personel After Sales'],
    ];

    public function run(): void
    {
        // ---------- data master (yang sudah ada tidak ditimpa) ----------
        foreach (self::DATA as [$id, $name, $active, $jenis]) {
            DB::table('afsel_dealer_level_personel')->insertOrIgnore([
                'dealer_level_personel_id' => $id,
                'dealer_level_personel'    => $name,
                'is_active'                => $active,
                'jenis'                    => $jenis,
            ]);
        }

        // ---------- permission ----------
        $now = now();
        $adminRoleId = DB::table('ms_afsel_atpm_roles')->where('slug', 'admin')->value('id');

        foreach (self::PERMISSIONS as [$slug, $name]) {
            if (!DB::table('ms_afsel_atpm_permissions')->where('slug', $slug)->exists()) {
                DB::table('ms_afsel_atpm_permissions')->insert([
                    'slug' => $slug, 'name' => $name, 'group_name' => 'Personel After Sales',
                    'created_by' => 'SYSTEM-SEEDER', 'date_created' => $now,
                ]);
            }

            if ($adminRoleId) {
                DB::table('ms_afsel_atpm_role_permissions')->insertOrIgnore([
                    'role_id' => $adminRoleId,
                    'permission_id' => DB::table('ms_afsel_atpm_permissions')->where('slug', $slug)->value('id'),
                    'created_by' => 'SYSTEM-SEEDER', 'date_created' => $now,
                ]);
            }
        }

        // ---------- menu: role admin dapat menu Personel After Sales & Level Personel ----------
        if ($adminRoleId) {
            foreach (DB::table('ms_afsel_atpm_menus')->whereIn('name', ['personel-after-sales', 'level-personel-aftersales'])->pluck('id') as $menuId) {
                DB::table('ms_afsel_atpm_menu_roles')->insertOrIgnore([
                    'menu_id' => $menuId, 'role_id' => $adminRoleId, 'created_by' => 'SYSTEM-SEEDER', 'date_created' => $now,
                ]);
            }
        }

        DB::table('ms_afsel_atpm_menus')
            ->where('name', 'level-personel-aftersales')
            ->whereNull('route_name')
            ->update([
                'route_name'      => 'aftersales.atpm.level_personel',
                'updated_by'      => 'SYSTEM-SEEDER',
                'date_updated'    => now(),
            ]);

        DB::table('ms_afsel_atpm_menus')
            ->where('name', 'level-personel-aftersales')
            ->whereNull('permission_slug')
            ->update(['permission_slug' => 'level-personel.view']);

        app(AfselRbacService::class)->flushCache('atpm');
    }
}
