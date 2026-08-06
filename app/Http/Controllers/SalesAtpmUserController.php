<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use App\Repositories\SalesAtpmUserMenuRepository;

class SalesAtpmUserController
{
    protected $salesAtpmUserMenuRepo;
    public function __construct(SalesAtpmUserMenuRepository $SalesAtpmUserMenuRepository)
    {
        $this->salesAtpmUserMenuRepo = $SalesAtpmUserMenuRepository;
    }

    public function index()
    {
        return view('sales.atpm.page_user.index');
    }

    public function user_datatable()
    {
        $query = DB::table('ms_sales_atpm_user')->get();
        // dd($query);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('action', function($row){
                
                $action = '';

                $action .= '<a href="'.route('sales.atpm.edit_user_menu', ['kd_atpm_user' => base64_encode($row->kd_atpm_user)]).'" class="btn-fi btn-fi-primary btn-fi-sm">Menu</a> ';
                $action .= '<a href="'.route('sales.atpm.edit_user_permission', ['kd_atpm_user' => base64_encode($row->kd_atpm_user)]).'" class="btn-fi btn-fi-primary btn-fi-sm">Permission</a>';

                return $action;
            })
            ->addColumn('z_is_active', function($row){
                if($row->is_active == 1) {
                    $z_is_active = '<span class="badge bg-success">Active</span>';
                } else if($row->is_active == 0) {
                    $z_is_active = '<span class="badge bg-danger">Inactive</span>';
                }
                return $z_is_active;
            })
            ->rawColumns(['action', 'z_is_active'])
            ->make(true);
    }

    public function userSync()
    {
        try
        {   
            // todo: masih gak pake API
            $wrsSalesDataUser = DB::connection('db_wrs_sales')
                ->table('tblatpm_user')
                ->get();

            // dd($wrsSalesDataUser);

            foreach ($wrsSalesDataUser as $row) {
                DB::connection('mysql')
                    ->table('ms_sales_atpm_user')
                    ->updateOrInsert(
                        ['kd_atpm_user' => $row->kd_atpm_user], // kondisi (unique key)
                        [
                            'nm_atpm_user' => $row->nm_atpm_user,
                            'username' => $row->username,
                            'password' => $row->password,
                            'fk_atpm_level' => $row->fk_atpm_level,
                            'fk_atpm_department' => $row->fk_atpm_department,
                            'email' => $row->email,
                            'picture' => $row->picture,
                            'picture_ext' => $row->picture_ext,
                            'tgl_masuk' => $row->tgl_masuk,
                            'is_active' => $row->is_active,
                            'created_by' => session('user.id'),
                            'date_created' => now()
                        ]
                    );
            }

            return response()->json([
                'message' => 'Sync User Success',
                'error' => ''
            ], 200);
        }
        catch (\Exception $e) {
            return response()->json([
                'message' => 'Sync User Failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function userMenu()
    {
        return view('sales.atpm.page_user_menu.user_menu_index');
    }

    public function editUserMenu(Request $request)
    {
        $kd_atpm_user = base64_decode($request->route('kd_atpm_user'));
        // dd($kd_atpm_user);
        
        $data['dataMenuUser'] = $this->salesAtpmUserMenuRepo->findBykd($kd_atpm_user);
        dd($data['dataMenuUser']);

        return view('sales.atpm.page_user.atpm_user_menu', $data);
    }
    






    // public function index()
    // {
    //     $data['dataDealer'] = DB::connection('db_wrs_aftersales')->table('tbldealer')->where('is_active', true)->orderBy('nm_dealer')->get();
    //     $data['dataModel'] = DB::connection('db_wrs_aftersales')->table('tblmodel')->where('is_wrs_aftersales', true)->orderBy('kd_model', 'asc')->get();
    //     $data['dataUio'] = DB::connection('mysql')->table('tbluio')->where('is_active', true)->get();
    // // dd($data['dataUio']);
    //     return view('atpm.report_retention.index', $data);
    // }

    // public function retrieve(Request $request)
    // {

    //     $validator = Validator::make($request->all(), [
    //         'kd_dealer' => 'required',
    //         'tahun' => 'required',
    //         'category_customer' => 'required',
    //         'kd_model' => 'required',
    //         'uio' => 'required',
    //         'including_vin' => 'required',
    //     ], [
    //         'kd_dealer.required' => 'Dealer belum dipilih.',
    //         'tahun.required' => 'Tahun belum dipilih.',
    //         'category_customer.required' => 'Category Customer belum dipilih.',
    //         'kd_model.required' => 'Model belum dipilih.',
    //         'uio.required' => 'UIO belum dipilih.',
    //         'including_vin.required' => 'Including VIN sold by other dealer belum dipilih.'
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Validasi gagal',
    //             'errors' => $validator->errors()
    //         ]);
    //     }
    //     // return response()->json([
    //     //     'success' => true,
    //     //     'message' => 'Data berhasil diproses'
    //     // ]);

    //     $kd_dealer = $request->input('kd_dealer');
    //     $tahun = $request->input('tahun');
    //     $category_customer = $request->input('category_customer');
    //     $kd_model = $request->input('kd_model');
    //     $uio = $request->input('uio');
    //     $including_vin = $request->input('including_vin');

    //     // dd($kd_model);

    //     $reportRetention = DB::connection('mysql')
    //         ->select(
    //             'CALL sp_rpt_retention_report(CAST(? AS JSON),?,?,CAST(? AS JSON),?,?)', 
    //             [json_encode($kd_dealer), $tahun, $category_customer, json_encode($kd_model), $uio, $including_vin]
    //     );

    //     // dd($reportRetention);

    //     return response()->json([
    //         'status'=> true,
    //         'reportRetention'=>$reportRetention
    //     ]);
    // }
}
