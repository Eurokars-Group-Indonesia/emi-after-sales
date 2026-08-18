<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
// use Yajra\DataTables\Facades\DataTables;
// use Illuminate\Support\Facades\Validator;
// use App\Repositories\ModelRepository;
// use App\Repositories\ServiceHistoryRepository;

class AtpmSalesSystemSetupController
{



    // protected $modelRepo, $serviceHistoryRepo;

    // public function __construct(ModelRepository $ModelRepository, ServiceHistoryRepository $ServiceHistoryRepository)
    // {
    //     $this->modelRepo = $ModelRepository;
    //     $this->serviceHistoryRepo = $ServiceHistoryRepository;
    // }

    // public function index()
    // {
    //     // dd('test');

    //     $data['dataModel'] = $this->modelRepo->getModel();

    //     return view('atpm.page_vehicle.V_service_history', $data);
    // }

    // public function service_history_datatable(Request $request)
    // {

    //     $srcFromDate = $request->input('srcFromDate');
    //     $srcToDate = $request->input('srcToDate');
    //     $srcModel = $request->input('srcModel');
    //     $srcVin = $request->input('srcVin');
    //     $srcText = null; //;$request->input('srcText');

    //     // echo $srcFromDate.' '.$srcToDate.' '.$srcModel.' '.$srcVin.' '.$srcText;
    //     // dd('====================================');

    //     $query = $this->serviceHistoryRepo->getAllServiceHistory($srcFromDate, $srcToDate, $srcModel, $srcVin, $srcText);
    //     // dd($query);

    //     return DataTables::of($query)
    //         ->addIndexColumn()
    //         ->addColumn('action', function($row){
    //             return '<a href="'.route('aftersales.atpm.model_other_edit').'" class="btn btn-sm btn-primary">Edit</a>';
    //         })
    //         ->addColumn('date_service', function($row){
    //             $date_service = null;

    //             if($row->date_service != null) {
    //                 $date_service = date('m-d-Y', strtotime($row->date_service));
    //             }

    //             return $date_service;
    //         })
    //         ->rawColumns(['action', 'date_service'])
    //         ->make(true);
    // }

    // public function atpm_model_other_create()
    // {
    //     $data['dataModel'] = $this->modelRepo->getModelExcludeInOther();
    //     return view('atpm.page_model.model_other_index_create', $data);
    // }

    // public function atpm_model_other_store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'kd_model' => 'required',
    //     ], [
    //         'kd_model.required' => 'Model belum dipilih.',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Validation failed',
    //             'errors' => $validator->errors()
    //         ]);
    //     }
    //     // return response()->json([
    //     //     'success' => true,
    //     //     'message' => 'Data berhasil diproses'
    //     // ]);

    //     $kd_model = $request->input('kd_model');

    //     try {
    //         $this->modelRepo->modelOtherStore($kd_model);

    //         return response()->json([
    //             'status'  => true,
    //             'message' => 'Data berhasil ditambahkan.',
    //             'errors'  => ''
    //         ]);
    //     } catch (\Illuminate\Database\QueryException $e) {
    //         // Duplicate entry
    //         if ($e->getCode() === '23000') {
    //             return response()->json([
    //                 'status'  => false,
    //                 'message' => 'Model ini sudah ada di daftar Model Other.',
    //                 'errors'  => ''
    //             ]);
    //         }

    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'Gagal menyimpan data. Silakan coba lagi.',
    //             'errors'  => ''
    //         ]);
    //     }

    // }

    // public function atpm_model_other_edit()
    // {
    //     dd('edit');
    // }
}
