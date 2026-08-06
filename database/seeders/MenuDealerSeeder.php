<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\MenuDealer;

class MenuDealerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        

        $home = MenuDealer::create([
            'title' => 'Home',
            'route' => 'dealer.aftersales.home',
            'icon'  => 'bi bi-house-fill',
            'order' => 1,
            'created_by' => 1,
            'date_created' => now()
        ]);

        $report = MenuDealer::create([
            'title' => 'Reports',
            'icon'  => 'bi bi-bar-chart-fill',
            'order' => 3,
            'created_by' => 1,
            'date_created' => now()
        ]);

        MenuDealer::create([
            'title' => 'Retention Report',
            'route' => 'dealer.report.service-retention',
            'parent_id' => $report->id,
            'order' => 1,
            'created_by' => 1,
            'date_created' => now()
        ]);
    }
}
