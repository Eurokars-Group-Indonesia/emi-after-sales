<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UioSeeder::class,
            // Menu After Sales & Sales sekarang di tabel RBAC:
            //  - After Sales : AfselRbacSeeder (ms_afsel_atpm_* & ms_afsel_dealer_*)
            //  - Sales ATPM  : migration 2026_11_06_000006 (ms_sales_atpm_*)
            // Seeder menu lama (MenuAtpmAfterSalesSeeder, MenuDealerSeeder, SalesAtpmMasterMenuSeeder, MenuAtpmSalesSeeder) tidak dipakai lagi.
            AfselRbacSeeder::class,
            SalesAtpmRbacMenuSeeder::class,
            SalesAtpmMasterPermissionSeeder::class,
            TblSettingSeeder::class,
        ]);
    }
}
