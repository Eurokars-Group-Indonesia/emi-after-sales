<?php

namespace App\Repositories;
use Illuminate\Support\Facades\DB;

interface ServiceHistoryRepositoryInterface
{
    public function getAllServiceHistory($srcFromDate, $srcToDate, $srcModel, $srcVin, $srcText);
}

class ServiceHistoryRepository implements ServiceHistoryRepositoryInterface
{

    protected $columnDt = [
                    'tanggal_faktur', 'no_vin', 'fk_model', 'nm_model', 'fk_type', 
                    'nomor_polisi', 'nama_customer', 'alamat', 'tblkpi.fk_dealer', 'nm_dealer', 'telephone_1', 
                    'telephone_2', 'customer_request'
                ];

    public function getAllServiceHistory($srcFromDate, $srcToDate, $srcModel, $srcVin, $srcText)
    {

        $query = DB::table('tblkpi')
                    ->select(
                        'tblkpi.tanggal_faktur as date_service',
                        'tblcustomer.no_vin',
                        'tblcustomer.fk_model',
                        'tblmodel.nm_model',
                        'tblcustomer.fk_type',
                        'tblcustomer.nomor_polisi',
                        'tblcustomer.nama_customer',
                        'tblcustomer.alamat',
                        'tblkpi.fk_dealer as fk_dealer_service',
                        'tbldealer.nm_dealer as dealer_service',
                        'tblcustomer.telephone_1',
                        'tblcustomer.telephone_2',
                        'tblkpi.customer_request'
                    )
                    ->join('tblcustomer', 'tblcustomer.kd_customer', '=', 'tblkpi.fk_customer')
                    ->join('tblmodel', 'tblcustomer.fk_model', '=', 'tblmodel.kd_model')
                    ->join('tbldealer', 'tbldealer.kd_dealer', '=', 'tblkpi.fk_dealer');

        if ($srcFromDate != null && $srcToDate != null) {

            $srcFromDate = date('Y-m-d', strtotime($srcFromDate));
            $srcFromDate = $srcFromDate.' 00:00:00';

            $srcToDate = date('Y-m-d', strtotime($srcToDate));
            $srcToDate = $srcToDate.' 23:59:59';

            $query->whereBetween('tblkpi.tanggal_faktur', [
                $srcFromDate,
                $srcToDate
            ]);

        }
        

        if($srcModel != null) {
            $query->where('tblcustomer.fk_model', $srcModel);
        }

        if($srcVin != null) {
            $query->where('tblcustomer.no_vin', 'like', '%' . $srcVin . '%');
        }

        $query->whereDate('tblkpi.tanggal_faktur', '<=', today());
        


        // $i  = 0;

        // if($srcText != null && $srcText != '') {
        //     foreach ($this->columnDt as $item) {
        //         if ($srcText)
        //         {
        //             if($i == 0) {
        //                 $query->where($item, 'like', '%' . $srcText . '%');
        //             } else {
        //                 $query->orWhere($item, 'like', '%' . $srcText . '%');
        //             }
        //         }
        //         $i++;
        //     }

        // }

        if (!empty($srcText)) {
            $query->where(function ($q) use ($srcText) {
                foreach ($this->columnDt as $item) {
                    $q->orWhere($item, 'like', "%{$srcText}%");
                }
            });
        }


        $query->orderBy('tblkpi.tanggal_faktur', 'desc');

        $query->limit(1000);
        return $query->get();

        // return $query->paginate(100);
    }



    //  where tc.fk_model in 
    //         (
    //            '.$model.'
    //         )
    //         and tc.no_vin like "%'.$vin.'%"


    // public function getModel()
    // {
    //     return DB::connection('mysql')->table('tblmodel')->where('is_wrs_aftersales', true)->orderBy('nm_model', 'asc')->get();
    // }

    // public function getModelOther()
    // {
    //     return DB::connection('mysql')
    //             ->table('model_other')
    //             ->join('tblmodel', 'model_other.kd_model', '=', 'tblmodel.kd_model')
    //             ->get();
    // }

    // public function modelOtherStore($kd_model)
    // {
    //     return DB::connection('mysql')
    //         ->table('model_other')
    //         ->insert([
    //             'kd_model' => $kd_model,
    //         ]);
    // }
    
    // /* 
    //     Hapus list model yang ada di Model Other
    // */
    // public function getModelExcludeInOther()
    // {
    //     return DB::connection('mysql')
    //         ->table('tblmodel')
    //         ->select('kd_model', 'nm_model')
    //         ->where('is_wrs_aftersales', true)
    //         ->whereNotIn('kd_model', function ($query) {
    //             $query->select('kd_model')->from('model_other');
    //         })
    //         ->orderBy('nm_model')
    //         ->get();
    // }

    // public function getModelOtherArray()
    // {
    //     return DB::connection('mysql')
    //         ->table('model_other')
    //         ->where('is_active', true)
    //         ->pluck('kd_model')   // ambil kolom saja
    //         ->toArray();
    // }
}