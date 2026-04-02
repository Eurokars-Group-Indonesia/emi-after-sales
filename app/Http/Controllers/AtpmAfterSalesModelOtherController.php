<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

use App\Repositories\ModelRepository;

class AtpmAfterSalesModelOtherController
{
    protected $modelRepo;

    public function __construct(ModelRepository $ModelRepository)
    {
        $this->modelRepo = $ModelRepository;
    }

    public function index()
    {
        // dd('test');
        return view('atpm.page_model.V_model_other');
    }

    public function atpm_model_other_datatable()
    {
        $query = $this->modelRepo->getModelOther();

        // dd($query);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('action', function($row){
                return '<a href="'.route('atpm.aftersales.model_other_edit').'" class="btn btn-sm btn-primary">Edit</a>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function atpm_model_other_create()
    {
        $data['dataModel'] = $this->modelRepo->getModel();
        return view('atpm.page_model.V_model_other_create', $data);
    }

    public function atpm_model_other_store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kd_model' => 'required',
        ], [
            'kd_model.required' => 'Model belum dipilih.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }
        // return response()->json([
        //     'success' => true,
        //     'message' => 'Data berhasil diproses'
        // ]);

        $kd_model = $request->input('kd_model');

        $callback = $this->modelRepo->modelOtherStore($kd_model);
        
        if($callback == true)
        {
            return response()->json([
                'status' => true,
                'message' => 'Insert Data Success',
                'errors' => ''
            ]);
        }
        else 
        {
            return response()->json([
                'status' => false,
                'message' => 'Insert Data Failed',
                'errors' => ''
            ]);
        }

    }

    public function atpm_model_other_edit()
    {
        dd('edit');
    }
}
