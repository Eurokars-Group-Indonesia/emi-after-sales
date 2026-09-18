<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

use App\Repositories\SalesAtpmAccessRepository;

class SalesAtpmSystemSetupMasterMenuController
{
    protected $SalesAtpmAccessRepo;

    public function __construct(SalesAtpmAccessRepository $SalesAtpmAccessRepository)
    {
        $this->SalesAtpmAccessRepo = $SalesAtpmAccessRepository;
    }

    public function sales_atpm_master_menu()
    {
        return view('sales.atpm.page_sales_atpm_master_menu.sales_atpm_master_menu');
    }

    public function sales_atpm_master_menu_datatable(Request $request)
    {
        $srcText = $request->input('srcText');

        $query = $this->SalesAtpmAccessRepo->findAllDatatableMasterMenu($srcText);
        // dd($query);
        
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('z_is_active', function($row){
                if($row->is_active == true) {
                    $z_is_active = '<span class="badge bg-success">Active</span>';
                } else if($row->is_active == false) {
                    $z_is_active = '<span class="badge bg-danger">Inactive</span>';
                }
                return $z_is_active;
            })
            ->addColumn('action', function($row){
                
                $action = '';
                $action .= '<div class="btn-group">';

                if($row->is_active == true) {
                    // <a href="'.route('aftersales.atpm.atpm_user_menu_permission').'" class="btn btn-xs-mzd btn-primary">Edit Menu & Permission</a>
                    $action .= '<button type="button" class="btn btn-fi btn-fi-warning btn-fi-sm">
                                    <i class="bi bi-trash"></i>
                                </button>';
                } else {
                    $action .= '<button type="button" class="btn btn-fi btn-fi-primary btn-fi-sm">
                                    <i class="bi bi-unlock-fill"></i>
                                </button>';
                }
            

                $action .= '<button type="button" class="btn btn-fi btn-fi-primary btn-fi-sm">
                                <i class="bi bi-pencil"></i>
                            </button>';

                $action .= '</div>';

                return $action;
            })
            ->rawColumns(['action', 'z_is_active'])
            ->make(true);
    }
}
