<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Repositories\ModelRepository;

class AtpmReportRetentionController
{
    protected $modelRepo;

    public function __construct(ModelRepository $ModelRepository)
    {
        $this->modelRepo = $ModelRepository;
    }

    public function index()
    {
        $data['dataDealer'] = DB::connection('db_wrs_aftersales')->table('tbldealer')->where('is_active', true)->orderBy('nm_dealer')->get();
        $data['dataModel'] = $this->modelRepo->getModelExcludeInOther();
        // dd($data['dataModel']);

        DB::connection('db_wrs_aftersales')->table('tblmodel')->where('is_wrs_aftersales', true)->orderBy('kd_model', 'asc')->get();
        $data['dataUio'] = DB::connection('mysql')->table('tbluio')->where('is_active', true)->get();
        // dd($data['dataUio']);

        // $data['isSyncRunning'] = DB::table('sync_logs')
        //                 ->where('status', 'RUNNING')
        //                 ->where('job_name', 'sync_pentaho')
        //                 ->get();

        $dataSyncLogs = DB::table('sync_logs')
                    ->where('job_name', 'sync_pentaho')
                    ->orderBy('start_time', 'desc')
                    ->first();
        
        // dd($data);

        if($dataSyncLogs->status == 'RUNNING') {
            $data['isSyncRunning'] = true;
        } else {
            $data['isSyncRunning'] = false;
        }

        return view('atpm.report_retention.index', $data);
    }

    public function retrieve(Request $request)
    {

        $validator = Validator::make($request->all(), [
            // 'kd_dealer' => 'required',
            'tahun' => 'required',
            'category_customer' => 'required',
            // 'kd_model' => 'required',
            'uio' => 'required',
            'including_vin' => 'required',
        ], [
            'kd_dealer.required' => 'Dealer belum dipilih.',
            'tahun.required' => 'Tahun belum dipilih.',
            'category_customer.required' => 'Category Customer belum dipilih.',
            'kd_model.required' => 'Model belum dipilih.',
            'uio.required' => 'UIO belum dipilih.',
            'including_vin.required' => 'Including VIN sold by other dealer belum dipilih.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ]);
        }
        // return response()->json([
        //     'success' => true,
        //     'message' => 'Data berhasil diproses'
        // ]);

        $kd_dealer = $request->input('kd_dealer');
        $tahun = $request->input('tahun');
        $category_customer = $request->input('category_customer');
        $kd_model = $request->input('kd_model');
        $uio = $request->input('uio');
        $including_vin = $request->input('including_vin');

        // setting kd_model dan kd_dealer

        
        if($kd_dealer == null)
        {
             $kd_dealer = DB::table('tbldealer')
                ->pluck('kd_dealer')   // ambil kolom saja
                ->toArray();           // jadi array
        }

        if($kd_model == null)
        {
            $kd_model = DB::table('tblmodel')
                ->pluck('kd_model')
                ->toArray();
        }

        // jika kd_model milih other: maka ambil dari table model_user
        if (in_array("327", $kd_model)) {
            $qModelOtherArray = $this->modelRepo->getModelOtherArray();
            $kd_model = array_unique(array_merge($kd_model, $qModelOtherArray));
        }

       
        // // Ini jalan
        // $reportRetention = DB::connection('mysql')
        //     ->select(
        //         'CALL sp_generateReportRetention(CAST(? AS JSON),?,?,CAST(? AS JSON),?,?)', 
        //         [json_encode($kd_dealer), $tahun, $category_customer, json_encode($kd_model), $uio, $including_vin]
        // );

        // // dd($reportRetention);

        // return response()->json([
        //     'status'=> true,
        //     'reportRetention'=>$reportRetention
        // ]);
        // // ./Ini jalan

        $pdo = DB::connection('mysql')->getPdo();

        $stmt = $pdo->prepare("
            CALL sp_generateReportRetention(
                CAST(? AS JSON),
                ?,
                ?,
                CAST(? AS JSON),
                ?,
                ?
            )
        ");

        // dd(json_encode($kd_dealer));

        $stmt->execute([
            json_encode($kd_dealer),
            $tahun,
            $category_customer,
            json_encode($kd_model),
            $uio,
            $including_vin
        ]);

        // Result summary report
        $resultSummaryReport = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->nextRowset();

        // result detail customer visit
        $resultDetailCustomerVisit = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->nextRowset();
        
        // result detail faktur
        $resultDetailUio = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->nextRowset();

        $resultDetailGap = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return response()->json([
            'status' => true,
            'reportRetention' => [
                'resultSummaryReport' => $resultSummaryReport,
                'resultDetailCustomerVisit' => $resultDetailCustomerVisit,
                'resultDetailUio' => $resultDetailUio,
                'resultDetailGap' => $resultDetailGap
            ]
        ]);
    }
}
