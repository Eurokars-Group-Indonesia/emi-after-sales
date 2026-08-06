<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

use App\Repositories\SalesAtpmMasterPermissionRepository;

class SalesAtpmMasterPermissionController
{
    protected $salesAtpmMasterPermissionRepo;

    public function __construct(SalesAtpmMasterPermissionRepository $SalesAtpmMasterPermissionRepository)
    {
        $this->SalesAtpmMasterPermissionRepo = $SalesAtpmMasterPermissionRepository;
    }

    public function index()
    {
        return view('sales.atpm.page_sales_atpm_master_permission.sales_atpm_master_permission');
    }

    public function sales_atpm_master_permission_datatable(Request $request)
    {
        $srcText = $request->input('srcText');

        $query = $this->SalesAtpmMasterPermissionRepo->findAll($srcText);
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
    
    // public function master_menu_atpm()
    // {
    //     return view('sales.atpm.page_master_menu_atpm.master_menu_atpm');
    // }

    

    // public function atpm_user_sync()
    // {
    //     try
    //     {   
    //         // todo: masih gak pake API
    //         $wrsAsDataUser = DB::connection('db_wrs_aftersales')
    //             ->table('master_aftersales.tblatpm_user')
    //             ->get();

    //         // foreach ($wrsAsDataUser as $row) {
    //         //     DB::connection('mysql')
    //         //         ->table('tblatpm_user')
    //         //         ->insert([
    //         //             'kd_atpm_user' => $row->kd_atpm_user,
    //         //             'nm_atpm_user' => $row->nm_atpm_user,
    //         //             'username' => $row->username,
    //         //             'password' => $row->password,
    //         //             'fk_atpm_level' => $row->fk_atpm_level,
    //         //             'fk_atpm_department' => $row->fk_atpm_department,
    //         //             'email' => $row->email,
    //         //             'picture' => $row->picture,
    //         //             'picture_ext' => $row->picture_ext,
    //         //             'tgl_masuk' => $row->tgl_masuk,
    //         //         ]);
    //         // }

    //         foreach ($wrsAsDataUser as $row) {
    //             DB::connection('mysql')
    //                 ->table('tblatpm_user')
    //                 ->updateOrInsert(
    //                     ['kd_atpm_user' => $row->kd_atpm_user], // kondisi (unique key)
    //                     [
    //                         'nm_atpm_user' => $row->nm_atpm_user,
    //                         'username' => $row->username,
    //                         'password' => $row->password,
    //                         'fk_atpm_level' => $row->fk_atpm_level,
    //                         'fk_atpm_department' => $row->fk_atpm_department,
    //                         'email' => $row->email,
    //                         'picture' => $row->picture,
    //                         'picture_ext' => $row->picture_ext,
    //                         'tgl_masuk' => $row->tgl_masuk,
    //                     ]
    //                 );
    //         }

    //         return response()->json([
    //             'message' => 'Sync ATPM User success',
    //             'error' => ''
    //         ], 200);
    //     }
    //     catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Sync ATPM User failed',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function atpm_user_edit_menu_permission()
    // {
    //     return view('aftersales.atpm.page_user.atpm_user_menu_permission');
    // }






    // // public function index()
    // // {
    // //     $data['dataDealer'] = DB::connection('db_wrs_aftersales')->table('tbldealer')->where('is_active', true)->orderBy('nm_dealer')->get();
    // //     $data['dataModel'] = DB::connection('db_wrs_aftersales')->table('tblmodel')->where('is_wrs_aftersales', true)->orderBy('kd_model', 'asc')->get();
    // //     $data['dataUio'] = DB::connection('mysql')->table('tbluio')->where('is_active', true)->get();
    // // // dd($data['dataUio']);
    // //     return view('atpm.report_retention.index', $data);
    // // }

    // // public function retrieve(Request $request)
    // // {

    // //     $validator = Validator::make($request->all(), [
    // //         'kd_dealer' => 'required',
    // //         'tahun' => 'required',
    // //         'category_customer' => 'required',
    // //         'kd_model' => 'required',
    // //         'uio' => 'required',
    // //         'including_vin' => 'required',
    // //     ], [
    // //         'kd_dealer.required' => 'Dealer belum dipilih.',
    // //         'tahun.required' => 'Tahun belum dipilih.',
    // //         'category_customer.required' => 'Category Customer belum dipilih.',
    // //         'kd_model.required' => 'Model belum dipilih.',
    // //         'uio.required' => 'UIO belum dipilih.',
    // //         'including_vin.required' => 'Including VIN sold by other dealer belum dipilih.'
    // //     ]);

    // //     if ($validator->fails()) {
    // //         return response()->json([
    // //             'status' => false,
    // //             'message' => 'Validasi gagal',
    // //             'errors' => $validator->errors()
    // //         ]);
    // //     }
    // //     // return response()->json([
    // //     //     'success' => true,
    // //     //     'message' => 'Data berhasil diproses'
    // //     // ]);

    // //     $kd_dealer = $request->input('kd_dealer');
    // //     $tahun = $request->input('tahun');
    // //     $category_customer = $request->input('category_customer');
    // //     $kd_model = $request->input('kd_model');
    // //     $uio = $request->input('uio');
    // //     $including_vin = $request->input('including_vin');

    // //     // dd($kd_model);

    // //     $reportRetention = DB::connection('mysql')
    // //         ->select(
    // //             'CALL sp_rpt_retention_report(CAST(? AS JSON),?,?,CAST(? AS JSON),?,?)', 
    // //             [json_encode($kd_dealer), $tahun, $category_customer, json_encode($kd_model), $uio, $including_vin]
    // //     );

    // //     // dd($reportRetention);

    // //     return response()->json([
    // //         'status'=> true,
    // //         'reportRetention'=>$reportRetention
    // //     ]);
    // // }
}
