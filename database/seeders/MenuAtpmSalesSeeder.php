<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MenuAtpmSales;
use Illuminate\Support\Facades\DB;

class MenuAtpmSalesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        MenuAtpmSales::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $home = MenuAtpmSales::create([
            'title' => 'Home',
            'route' => 'sales.atpm.home',
            'icon'  => 'bi bi-house-fill',
            'order' => 1,
            'created_by' => 1, 
            'date_created' => now()
        ]);

# =================================================================================

        $systemSetup = MenuAtpmSales::create([
            'title' => 'System Setup',
            'icon'  => 'bi bi-gear',
            'order' => 1,
            'created_by' => 1, 
            'date_created' => now()
        ]);

            MenuAtpmSales::create([
                'title' => 'Config',
                'icon'  => '',
                'route' => 'sales.atpm.system_setup.config',
                'parent_id' => $systemSetup->id,
                'order' => 1,
                'created_by' => 1, 
                'date_created' => now()
            ]);

            MenuAtpmSales::create([
                'title' => 'Sales ATPM Master Menu',
                'icon'  => '',
                'route' => 'sales.atpm.system_setup.sales_atpm_master_menu',
                'parent_id' => $systemSetup->id,
                'order' => 2,
                'created_by' => 1, 
                'date_created' => now()
            ]);

            
            MenuAtpmSales::create([
                'title' => 'Sales ATPM Master Permission',
                'icon'  => '',
                'route' => 'sales.atpm.system_setup.sales_atpm_master_permission',
                'parent_id' => $systemSetup->id,
                'order' => 2,
                'created_by' => 1, 
                'date_created' => now()
            ]);

            MenuAtpmSales::create([
                'title' => 'Master Menu Dealer',
                'icon'  => '',
                'route' => 'sales.atpm.system_setup.master_menu_dealer',
                'parent_id' => $systemSetup->id,
                'order' => 2,
                'created_by' => 1, 
                'date_created' => now()
            ]);

# =================================================================================

        $administration = MenuAtpmSales::create([
            'title' => 'Administration',
            'icon'  => 'bi bi-building',
            'order' => 2,
            'created_by' => 1, 
            'date_created' => now()
        ]);
            $ATPM = MenuAtpmSales::create([
                'title' => 'ATPM',
                'icon'  => 'bi bi-sliders',
                'parent_id' => $administration->id,
                'order' => 1,
                'created_by' => 1, 
                'date_created' => now()
            ]);
                MenuAtpmSales::create([
                    'title' => 'User',
                    'icon'  => '',
                    'route' => 'sales.atpm.user_index',
                    'parent_id' => $ATPM->id,
                    'order' => 1,
                    'created_by' => 1, 
                    'date_created' => now()
                ]);
            
            $dealer = MenuAtpmSales::create([
                'title' => 'Dealer',
                'icon'  => 'bi bi-sliders',
                'parent_id' => $administration->id,
                'order' => 3,
                'created_by' => 1, 
                'date_created' => now()
            ]);
                MenuAtpmSales::create([
                    'title' => 'User',
                    'icon'  => '',
                    'route' => 'sales.atpm.administration.dealer_user',
                    'parent_id' => $dealer->id,
                    'order' => 1,
                    'created_by' => 1, 
                    'date_created' => now()
                ]);
            
# =================================================================================

        $report = MenuAtpmSales::create([
            'title' => 'Report',
            'icon'  => 'bi bi-receipt',
            'order' => 3,
            'created_by' => 1, 
            'date_created' => now()
        ]);

            $salesPersonReport = MenuAtpmSales::create([
                'title' => 'Sales Person',
                'icon'  => 'bi bi-users',
                'parent_id' => $report->id,
                'order' => 1,
                'created_by' => 1, 
                'date_created' => now()
            ]);

                MenuAtpmSales::create([
                    'title' => 'Sales Person History Report',
                    'icon'  => '',
                    'route' => 'sales.atpm.report.sales_person_history',
                    'parent_id' => $salesPersonReport->id,
                    'order' => 1,
                    'created_by' => 1, 
                    'date_created' => now()
                ]);

                MenuAtpmSales::create([
                    'title' => 'Sales Person Productivity Report',
                    'icon'  => '',
                    'route' => 'sales.atpm.report.sales_person_productivity',
                    'parent_id' => $salesPersonReport->id,
                    'order' => 2,
                    'created_by' => 1, 
                    'date_created' => now()
                ]);

                MenuAtpmSales::create([
                    'title' => 'Sales Person Productivity Report',
                    'icon'  => '',
                    'route' => 'sales.atpm.report.sales_person_productivity',
                    'parent_id' => $salesPersonReport->id,
                    'order' => 3,
                    'created_by' => 1, 
                    'date_created' => now()
                ]);









            
            

                


            
            

                


            
            
                












                



        
    }
}
