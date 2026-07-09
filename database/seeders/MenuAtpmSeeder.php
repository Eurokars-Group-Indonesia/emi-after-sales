<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MenuAtpm;
use Illuminate\Support\Facades\DB;

class MenuAtpmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        MenuAtpm::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $home = MenuAtpm::create([
            'title' => 'Home',
            'route' => 'atpm.aftersales.home',
            'icon'  => 'bi bi-house-fill',
            'order' => 1
        ]);

            $admin = MenuAtpm::create([
                'title' => 'Administration',
                'icon'  => 'bi bi-building',
                'order' => 2
            ]);

            $atpm = MenuAtpm::create([
                'title' => 'ATPM',
                'icon'  => 'bi bi-people',
                'parent_id' => $admin->id,
                'order' => 1
            ]);

            MenuAtpm::create([
                'title' => 'User',
                'route' => 'atpm.aftersales.atpm_user',
                'parent_id' => $atpm->id,
                'order' => 1
            ]);

            MenuAtpm::create([
                'title' => 'Model Other',
                'route' => 'atpm.aftersales.model_other',
                'parent_id' => $admin->id,
                'order' => 2
            ]);

            MenuAtpm::create([
                'title' => 'Sync Monitoring',
                'route' => 'atpm.utility.sync_index',
                'parent_id' => $admin->id,
                'order' => 3
            ]);


        $dealer = MenuAtpm::create([
            'title' => 'Vehicle',
            'icon'  => 'bi-car-front',
            'order' => 2
        ]);

            MenuAtpm::create([
                'title' => 'Service History',
                'route' => 'atpm.aftersales.vehicle_service_history',
                'parent_id' => $dealer->id,
                'order' => 1
            ]);



        $report = MenuAtpm::create([
            'title' => 'Reports',
            'icon'  => 'bi bi-bar-chart-fill',
            'order' => 3
        ]);

            MenuAtpm::create([
                'title' => 'Retention Report',
                'route' => 'atpm.report.service-retention',
                'parent_id' => $report->id,
                'order' => 1
            ]);
    }
}
