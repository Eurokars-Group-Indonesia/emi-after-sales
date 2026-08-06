<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SalesAtpmMasterPermission;
use Illuminate\Support\Facades\DB;

class SalesAtpmMasterPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        SalesAtpmMasterPermission::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        # SYSTEM SETUP
        

        SalesAtpmMasterPermission::create([
            'description' => 'Config - View',
            'permission' => 'system_setup.config.view',
            'group'       => 'System Setup',
                'created_by' => 1,
                'date_created'=>now()
        ]);
        SalesAtpmMasterPermission::create([
            'description' => 'Sales ATPM Master Menu - View',
            'permission' => 'system_setup.master_menu_atpm.view',
            'group'       => 'System Setup',
                'created_by' => 1,
                'date_created'=>now()
        ]);
        SalesAtpmMasterPermission::create([
            'description' => 'Master Menu Dealer - View',
            'permission' => 'system_setup.master_menu_dealer.view',
            'group'       => 'System Setup',
                'created_by' => 1,
                'date_created'=>now()
        ]);



        # ADMINISTRATION - ATPM USER
        SalesAtpmMasterPermission::create([
            'description' => 'Administration - ATPM User - View',
            'permission' => 'administration.atpm.user.view',
            'group'       => 'Administration ATPM User',
                'created_by' => 1,
                'date_created'=>now()
        ]);
        SalesAtpmMasterPermission::create([
            'description' => 'Administration - ATPM User - Create',
            'permission' => 'administration.atpm.user.create',
            'group'       => 'Administration ATPM User',
                'created_by' => 1,
                'date_created'=>now()
        ]);
        SalesAtpmMasterPermission::create([
            'description' => 'Administration - ATPM User - Edit',
            'permission' => 'administration.atpm.user.edit',
            'group'       => 'Administration ATPM User',
                'created_by' => 1,
                'date_created'=>now()
        ]);
        SalesAtpmMasterPermission::create([
            'description' => 'Administration - ATPM User - Delete',
            'permission' => 'administration.atpm.user.delete',
            'group'       => 'Administration ATPM User',
                'created_by' => 1,
                'date_created'=>now()
        ]);



        # ADMINISTRATION - DEALER USER
        SalesAtpmMasterPermission::create([
            'description' => 'Administration - Dealer User - View',
            'permission' => 'administration.atpm.user.view',
            'group'       => 'Administration Dealer User',
                'created_by' => 1,
                'date_created'=>now()
        ]);
        SalesAtpmMasterPermission::create([
            'description' => 'Administration - Dealer User - Create',
            'permission' => 'administration.atpm.user.create',
            'group'       => 'Administration Dealer User',
                'created_by' => 1,
                'date_created'=>now()
        ]);
        SalesAtpmMasterPermission::create([
            'description' => 'Administration - Dealer User - Edit',
            'permission' => 'administration.atpm.user.edit',
            'group'       => 'Administration Dealer User',
                'created_by' => 1,
                'date_created'=>now()
        ]);
        SalesAtpmMasterPermission::create([
            'description' => 'Administration - Dealer User - Delete',
            'permission' => 'administration.atpm.user.delete',
            'group'       => 'Administration Dealer Delete',
                'created_by' => 1,
                'date_created'=>now()
        ]);




        # REPORT - SALES PERSON
        SalesAtpmMasterPermission::create([
            'description' => 'Report - Sales Person - Sales Person History Report',
            'permission' => 'report.sales_person.sales_person_history_report.view',
            'group'       => 'Report Sales Person',
                'created_by' => 1,
                'date_created'=>now()
        ]);

        SalesAtpmMasterPermission::create([
            'description' => 'Report - Sales Person - Sales Person Productivity Report',
            'permission' => 'report.sales_person.sales_person_productivity_report.view',
            'group'       => 'Report Sales Person',
                'created_by' => 1,
                'date_created'=>now()
        ]);
        
        SalesAtpmMasterPermission::create([
            'description' => 'Report - Sales Person - National Sales Person Productivity Report',
            'permission' => 'report.sales_person.national_sales_person_productivity_report.view',
            'group'       => 'Report Sales Person',
                'created_by' => 1,
                'date_created'=>now()
        ]);
    }
}
