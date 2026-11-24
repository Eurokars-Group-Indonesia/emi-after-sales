<?php

namespace Database\Seeders;

use App\Services\Afsel\AfselRbacService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Permission tambah & ubah data user After Sales (halaman Access Control > User Access). Idempotent.
 *
 *   php artisan db:seed --class=AfselUserManageSeeder
 *
 * rbac.user.manage → role admin ATPM (super admin sudah bypass).
 * Sengaja tidak memanggil AfselRbacSeeder agar menu yang sudah dihapus admin tidak dibuat ulang.
 */
class AfselUserManageSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        if (!DB::table('ms_afsel_atpm_permissions')->where('slug', 'rbac.user.manage')->exists()) {
            DB::table('ms_afsel_atpm_permissions')->insert([
                'slug' => 'rbac.user.manage', 'name' => 'Tambah & Ubah Data User', 'group_name' => 'RBAC',
                'created_by' => 'SYSTEM-SEEDER', 'date_created' => $now,
            ]);
        }

        $adminRoleId = DB::table('ms_afsel_atpm_roles')->where('slug', 'admin')->value('id');
        if ($adminRoleId) {
            DB::table('ms_afsel_atpm_role_permissions')->insertOrIgnore([
                'role_id'       => $adminRoleId,
                'permission_id' => DB::table('ms_afsel_atpm_permissions')->where('slug', 'rbac.user.manage')->value('id'),
                'created_by'    => 'SYSTEM-SEEDER',
                'date_created'  => $now,
            ]);
        }

        app(AfselRbacService::class)->flushCache('atpm');
    }
}
