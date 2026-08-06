<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SalesAtpmMasterMenu;
use Illuminate\Support\Facades\DB;

class SalesAtpmMasterMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        SalesAtpmMasterMenu::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $home = SalesAtpmMasterMenu::create([
            'title' => 'Home',
            'route' => 'sales.atpm.home',
            'icon'  => 'bi bi-house-fill',
            'order' => 1,
            'created_by' => 1,
            'date_created'=>now()
        ]);

        $administration = SalesAtpmMasterMenu::create([
            'title' => 'Administration',
            'icon'  => 'bi bi-building',
            'order' => 2,
                'created_by' => 1,
                'date_created'=>now()
        ]);
            
            $systemSetup = SalesAtpmMasterMenu::create([
                'title' => 'System Setup',
                'icon'  => 'bi bi-gear',
                'parent_id' => $administration->id,
                'order' => 1,
                'created_by' => 1,
                'date_created'=>now()
            ]);

                SalesAtpmMasterMenu::create([
                    'title' => 'Config',
                    'icon'  => '',
                    'route' => 'sales.atpm.system_setup.config',
                    'parent_id' => $systemSetup->id,
                    'order' => 1,
                'created_by' => 1,
                'date_created'=>now()
                ]);

                SalesAtpmMasterMenu::create([
                    'title' => 'Master Menu',
                    'icon'  => '',
                    'route' => 'sales.atpm.system_setup.sales_atpm_master_menu',
                    'parent_id' => $systemSetup->id,
                    'order' => 2,
                'created_by' => 1,
                'date_created'=>now()
                ]);

                SalesAtpmMasterMenu::create([
                    'title' => 'Master Permission',
                    'icon'  => '',
                    'route' => 'sales.atpm.system_setup.master_permission_atpm',
                    'parent_id' => $systemSetup->id,
                    'order' => 2,
                'created_by' => 1,
                'date_created'=>now()
                ]);

                SalesAtpmMasterMenu::create([
                    'title' => 'Master Menu Sales Dealer',
                    'icon'  => '',
                    'route' => 'sales.atpm.system_setup.master_menu_dealer',
                    'parent_id' => $systemSetup->id,
                    'order' => 2,
                'created_by' => 1,
                'date_created'=>now()
                ]);

            
            $ATPM = SalesAtpmMasterMenu::create([
                'title' => 'ATPM',
                'icon'  => 'bi bi-sliders',
                'parent_id' => $administration->id,
                'order' => 2,
                'created_by' => 1,
                'date_created'=>now()

            ]);

                SalesAtpmMasterMenu::create([
                    'title' => 'User',
                    'icon'  => '',
                    'route' => 'sales.atpm.user',
                    'parent_id' => $ATPM->id,
                    'order' => 1,
                'created_by' => 1,
                'date_created'=>now()
                ]);


            $dealer = SalesAtpmMasterMenu::create([
                'title' => 'Dealer',
                'icon'  => 'bi bi-sliders',
                'parent_id' => $administration->id,
                'order' => 3,
                'created_by' => 1,
                'date_created'=>now()
            ]);
            
                SalesAtpmMasterMenu::create([
                    'title' => 'User',
                    'icon'  => '',
                    'route' => 'sales.atpm.administration.dealer_user',
                    'parent_id' => $dealer->id,
                    'order' => 1,
                'created_by' => 1,
                'date_created'=>now()
                ]);




                



        $report = SalesAtpmMasterMenu::create([
            'title' => 'Report',
            'icon'  => 'bi bi-receipt',
            'order' => 3,
                'created_by' => 1,
                'date_created'=>now()
        ]);

            $salesPersonReport = SalesAtpmMasterMenu::create([
                'title' => 'Sales Person',
                'icon'  => 'bi bi-users',
                'parent_id' => $report->id,
                'order' => 1,
                'created_by' => 1,
                'date_created'=>now()
            ]);

                SalesAtpmMasterMenu::create([
                    'title' => 'Sales Person History Report',
                    'icon'  => '',
                    'route' => 'sales.atpm.report.sales_person_history',
                    'parent_id' => $salesPersonReport->id,
                    'order' => 1,
                'created_by' => 1,
                'date_created'=>now()
                ]);

                SalesAtpmMasterMenu::create([
                    'title' => 'Sales Person Productivity Report',
                    'icon'  => '',
                    'route' => 'sales.atpm.report.sales_person_productivity',
                    'parent_id' => $salesPersonReport->id,
                    'order' => 2,
                'created_by' => 1,
                'date_created'=>now()
                ]);

                SalesAtpmMasterMenu::create([
                    'title' => 'Sales Person Productivity Report',
                    'icon'  => '',
                    'route' => 'sales.atpm.report.sales_person_productivity',
                    'parent_id' => $salesPersonReport->id,
                    'order' => 3,
                'created_by' => 1,
                'date_created'=>now()
                ]);
    }
}
