<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Repositories\ModelRepository;

class AfterSalesAtpmModelOtherController
{
    protected $modelRepo;

    public function __construct(ModelRepository $ModelRepository)
    {
        $this->modelRepo = $ModelRepository;
    }

    public function index()
    {
        $models = $this->modelRepo->getModelOther();

        return view('aftersales.atpm.page_model.model_other_index', [
            'countTotal'      => $models->count(),
            'countActive'     => $models->where('is_active', true)->count(),
            'availableModels' => $this->modelRepo->getModelExcludeInOther(),
            // Semua model After Sales + kode yang sudah dipakai, untuk pilihan model di modal Ubah.
            'allModels'       => $this->modelRepo->getModel(),
            'takenCodes'      => $models->pluck('kd_model')->map(fn ($kd) => (string) $kd)->values(),
            'canCreate'       => afsel_can('model-other.create'),
            'canEdit'         => afsel_can('model-other.edit'),
        ]);
    }

    public function atpm_model_other_datatable()
    {
        $query = $this->modelRepo->getModelOther();

        // Kolom aksi (tombol Ubah) dirender di view berdasarkan permission.
        return DataTables::of($query)
            ->addIndexColumn()
            ->make(true);
    }

    public function atpm_model_other_create()
    {
        $data['dataModel'] = $this->modelRepo->getModelExcludeInOther();
        return view('aftersales.atpm.page_model.model_other_create', $data);
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
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ]);
        }
        // return response()->json([
        //     'success' => true,
        //     'message' => 'Data berhasil diproses'
        // ]);

        $kd_model = $request->input('kd_model');

        try {
            $this->modelRepo->modelOtherStore($kd_model);

            return response()->json([
                'status'  => true,
                'message' => 'Data berhasil ditambahkan.',
                'errors'  => ''
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Duplicate entry
            if ($e->getCode() === '23000') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Model ini sudah ada di daftar Model Other.',
                    'errors'  => ''
                ]);
            }

            return response()->json([
                'status'  => false,
                'message' => 'Gagal menyimpan data. Silakan coba lagi.',
                'errors'  => ''
            ]);
        }

    }

    public function atpm_model_other_edit()
    {
        // Ubah data dilakukan lewat modal di halaman daftar.
        return redirect()->route('aftersales.atpm.model_other');
    }

    public function atpm_model_other_update(Request $request, $id)
    {
        $row = $this->modelRepo->findModelOther($id);
        if (!$row) {
            return response()->json(['status' => false, 'message' => 'Data Model Other tidak ditemukan.', 'errors' => ''], 404);
        }

        $validator = Validator::make($request->all(), [
            'kd_model'  => [
                'required',
                Rule::exists('mysql.tblmodel', 'kd_model'),
                Rule::unique('mysql.ms_model_other', 'kd_model')->ignore($id),
            ],
            'is_active' => 'required|boolean',
        ], [
            'kd_model.required' => 'Model belum dipilih.',
            'kd_model.exists'   => 'Model tidak ditemukan.',
            'kd_model.unique'   => 'Model ini sudah ada di daftar Model Other.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ]);
        }

        try {
            $this->modelRepo->modelOtherUpdate($id, $request->input('kd_model'), (bool) $request->input('is_active'));

            return response()->json([
                'status'  => true,
                'message' => 'Data berhasil diperbarui.',
                'errors'  => '',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            report($e);

            return response()->json([
                'status'  => false,
                'message' => 'Gagal menyimpan data. Silakan coba lagi.',
                'errors'  => '',
            ]);
        }
    }
}
