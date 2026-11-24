<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data awal tblsetting, disalin dari public.tblsetting di WRS After Sales lama.
 * Di sana ada 16 baris yang isinya identik; di sini cukup 1 baris
 * (script sync lama hanya membaca baris pertama dan meng-update semua baris sekaligus).
 *
 * Hanya mengisi jika tabel masih kosong — aman dijalankan berulang dan tidak menimpa setting yang sudah diubah.
 *   php artisan db:seed --class=TblSettingSeeder
 */
class TblSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('tblsetting')->exists()) {
            $this->command?->info('tblsetting sudah berisi data, seeder dilewati.');

            return;
        }

        DB::table('tblsetting')->insert([
            'current_month'          => 11,
            'current_year'           => 2012,
            'postgre_url'            => 'C:\\Progra~1\\PostgreSQL\\8.1\\bin\\',
            'weeks'                  => 2,
            'target_booking'         => '66',
            'target_mandatory_parts' => '77',
            'target_reminder'        => '66',
            'target_retention'       => '76',
            'passing_point'          => 90,
            'filter_csi'             => "type_customer in ('OWNER','') and service_type in ('FS2','MAINTENANCE') and fk_model in "
                . "('101','102','300','301','302','303','306','308','394','396','397','399','400','401','403','404','405','406',"
                . "'407','408','409','410','411','412','413','414','416','415') "
                . "and (tanggal_handover >='2018-01-01 00:00:00' and tanggal_handover<='2025-12-31 23:59:59')",
            'last_update_csi'        => '2026-07-21 00:00:00',
        ]);
    }
}
