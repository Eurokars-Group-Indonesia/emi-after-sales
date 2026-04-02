<?php

namespace App\Repositories;
use Illuminate\Support\Facades\DB;

interface ModelRepositoryInterface
{
    public function getModel();
    public function modelOtherStore($kd_model);
    public function getModelExcludeInOther();
    public function getModelOtherArray();
}

class ModelRepository implements ModelRepositoryInterface
{
    public function getModel()
    {
        return DB::connection('mysql')->table('tblmodel')->where('is_wrs_aftersales', true)->orderBy('nm_model', 'asc')->get();
    }

    public function getModelOther()
    {
        return DB::connection('mysql')
                ->table('model_other')
                ->join('tblmodel', 'model_other.kd_model', '=', 'tblmodel.kd_model')
                ->get();
    }

    public function modelOtherStore($kd_model)
    {
        return DB::connection('mysql')
            ->table('model_other')
            ->insert([
                'kd_model' => $kd_model,
            ]);
    }
    
    /* 
        Hapus list model yang ada di Model Other
    */
    public function getModelExcludeInOther()
    {
        return DB::connection('mysql')
            ->table('tblmodel')
            ->select('kd_model', 'nm_model')
            ->where('is_wrs_aftersales', true)
            ->whereNotIn('kd_model', function ($query) {
                $query->select('kd_model')->from('model_other');
            })
            ->orderBy('nm_model')
            ->get();
    }

    public function getModelOtherArray()
    {
        return DB::connection('mysql')
            ->table('model_other')
            ->where('is_active', true)
            ->pluck('kd_model')   // ambil kolom saja
            ->toArray();
    }

    

   

    
}