<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MenuAtpmAfterSales;
use Illuminate\Support\Facades\DB;

class MenuAtpmAfterSalesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        MenuAtpmAfterSales::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $home = MenuAtpmAfterSales::create([
            'title' => 'Home',
            'route' => 'aftersales.atpm.home',
            'icon'  => 'bi bi-house-fill',
            'order' => 1,
            'created_by' => '1',
            'date_created' => now()
        ]);

            $admin = MenuAtpmAfterSales::create([
                'title' => 'Administration',
                'icon'  => 'bi bi-building',
                'order' => 2,
                'created_by' => '1',
                'date_created' => now()
            ]);

            $atpm = MenuAtpmAfterSales::create([
                'title' => 'ATPM',
                'icon'  => 'bi bi-people',
                'parent_id' => $admin->id,
                'order' => 1,
                'created_by' => '1',
                'date_created' => now()
            ]);

                MenuAtpmAfterSales::create([
                    'title' => 'User',
                    'route' => 'aftersales.atpm.atpm_user',
                    'parent_id' => $atpm->id,
                    'order' => 1,
                    'created_by' => '1',
                    'date_created' => now()
                ]);

            MenuAtpmAfterSales::create([
                'title' => 'Model Other',
                'route' => 'aftersales.atpm.model_other',
                'parent_id' => $admin->id,
                'order' => 2,
                'created_by' => '1',
                'date_created' => now()
            ]);

            MenuAtpmAfterSales::create([
                'title' => 'Sync Monitoring',
                'route' => 'aftersales.atpm.utility.sync_index',
                'parent_id' => $admin->id,
                'order' => 3,
                    'created_by' => '1',
                    'date_created' => now()
            ]);


        $dealer = MenuAtpmAfterSales::create([
            'title' => 'Vehicle',
            'icon'  => 'bi-car-front',
            'order' => 2,
                    'created_by' => '1',
                    'date_created' => now()
        ]);

            MenuAtpmAfterSales::create([
                'title' => 'Service History',
                'route' => 'aftersales.atpm.vehicle_service_history',
                'parent_id' => $dealer->id,
                'order' => 1,
                    'created_by' => '1',
                    'date_created' => now()
            ]);



        $report = MenuAtpmAfterSales::create([
            'title' => 'Reports',
            'icon'  => 'bi bi-bar-chart-fill',
            'order' => 3,
                    'created_by' => '1',
                    'date_created' => now()
        ]);

            MenuAtpmAfterSales::create([
                'title' => 'Retention Report',
                'route' => 'aftersales.atpm.report.service-retention',
                'parent_id' => $report->id,
                'order' => 1,
                    'created_by' => '1',
                    'date_created' => now()
            ]);
    }
}
