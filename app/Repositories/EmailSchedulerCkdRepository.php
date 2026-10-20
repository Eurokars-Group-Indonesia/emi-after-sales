<?php

namespace App\Repositories;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmailSchedulerCkdRepository
{
    public function getSetup()
    {
        return DB::table('ms_setup_email_scheduler_ckd')->orderBy('id')->first();
    }

    /**
     * Data delivery CKD yang dikirim sebagai attachment Excel.
     * Sumber data: koneksi db_wrs_sales (.env: DB_WRSSALES_*).
     */
    public function getDeliveryData($tanggal)
    {
        // return DB::connection('db_ckd')
        //     ->table('nama_tabel_delivery_ckd')
        //     ->select('kd_dealer', 'dealer', 'no_vin', 'kd_model', 'type', 'kd_type', 'type_name', 'tgl_delivery')
        //     ->whereDate('tgl_delivery', $tanggal)
        //     ->orderBy('kd_dealer')
        //     ->get();

        $table =  DB::connection('db_wrs_sales')
                    ->table('tblbast_log as tbast_log')
                    ->join('tblinventory as tinv', 'tinv.no_vin', '=', 'tbast_log.fk_vin')
                    ->join('tblmodel as tmodel', 'tmodel.kd_model', '=', 'tinv.fk_model')
                    ->join('tbltype as ttype', 'ttype.kd_type', '=', 'tinv.fk_type')
                    ->join('tbldealer as tdealer', 'tdealer.kd_dealer', '=', 'tbast_log.dealer_info')
                    ->where('tinv.is_active', true)
                    ->where('tbast_log.log_action_date', '>=', Carbon::parse($tanggal)->startOfDay())
                    ->where('tbast_log.log_action_date', '<', Carbon::parse($tanggal)->addDay()->startOfDay())
                    ->select([
                        'tdealer.kd_dealer as kd_dealer',
                        'tdealer.nm_dealer as nm_dealer',
                        'tinv.no_vin',
                        'tinv.fk_model as kd_model',
                        'tmodel.nm_model',
                        'tinv.fk_type as kd_type',
                        'ttype.nm_type',
                        'tbast_log.tgl_info as tgl_delivery',
                        'tbast_log.log_action_date',
                        'tbast_log.log_action_mode',
                    ])
                    ->get();

                    return $table;
    }
}
