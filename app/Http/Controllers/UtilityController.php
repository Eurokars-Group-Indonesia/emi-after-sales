<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UtilityController
{

    


    public function sync()
    {
        try {

            set_time_limit(100000);
            // DB::connection('mysql')->beginTransaction();

            // # Sync Dealer
            //     DB::connection('mysql')->table('tbldealer')->truncate();

            //     echo 'Get data dealer from db WRS After Sales <br/>';
            //     $pgDataDealer = DB::connection('db_wrs_aftersales')
            //         ->table('tbldealer')
            //         ->get();

            //     echo 'Insert data dealer (mysql)';
            //     foreach ($pgDataDealer as $row) {
            //         DB::connection('mysql')
            //             ->table('tbldealer')
            //             ->insert([
            //                 'kd_dealer' => $row->kd_dealer,
            //                 'nm_dealer' => $row->nm_dealer,
            //                 'fk_dealer_region' => $row->fk_dealer_region,
            //                 'fk_dealer_group' => $row->fk_dealer_group,
            //                 'is_active' => $row->is_active
            //             ]);
            //     }
            // # ./Sync Dealer

            // # Sync Model
            //     DB::connection('mysql')->table('tblmodel')->truncate();

            //     echo 'Get data model from db WRS After Sales';
            //     $pgDataModel = DB::connection('db_wrs_aftersales')
            //         ->table('tblmodel')
            //         ->get();

            //     echo 'Insert data model (mysql)';
            //     foreach ($pgDataModel as $rowModel) {
            //         DB::connection('mysql')
            //             ->table('tblmodel')
            //             ->insert([
            //                 'kd_model' => $rowModel->kd_model,
            //                 'nm_model' => $rowModel->nm_model,
            //                 'jenis_kendaraan' => $rowModel->jenis_kendaraan,
            //                 'is_wrs' => $rowModel->is_wrs,
            //                 'model_year' => $rowModel->model_year,
            //                 'engine_type' => $rowModel->engine_type,
            //                 'vehicle_type' => $rowModel->vehicle_type,
            //                 'wmi' => $rowModel->wmi,
            //                 'hand_over_picture' => $rowModel->hand_over_picture,
            //                 'hand_over_picture_ext' => $rowModel->hand_over_picture_ext,
            //                 'is_wrs_aftersales' => $rowModel->is_wrs_aftersales
            //             ]);
            //     }
            // # ./Sync Model

            // # Sync category vehicle
            //     DB::connection('mysql')->table('tblcategory_vehicles')->truncate();

            //     echo 'Get data category vehicle from db WRS After Sales';
            //     $pgDataCategoryVehicle = DB::connection('db_wrs_aftersales')
            //         ->table('master_aftersales.tblcategory_vehicles')
            //         ->get();


            //     echo 'Insert data category vehicle (mysql)';
            //     foreach ($pgDataCategoryVehicle as $rowCategoryVehicle) {
            //         DB::connection('mysql')
            //             ->table('tblcategory_vehicles')
            //             ->insert([
            //                 'kd_category_vehicles' => $rowCategoryVehicle->kd_category_vehicles,
            //                 'nm_category_vehicles' => $rowCategoryVehicle->nm_category_vehicles,
            //                 'is_active' => $rowCategoryVehicle->is_active,
            //                 'jenis' => $rowCategoryVehicle->jenis,
            //             ]);
            //     }
            // # ./Sync category vehicle

            // # Sync category vehicle
            //     DB::connection('mysql')->table('tblcategory_2_vehicles')->truncate();

            //     echo 'Get data category 2 vehicle from db WRS After Sales';
            //     $pgDataCategory2Vehicle = DB::connection('db_wrs_aftersales')
            //         ->table('master_aftersales.tblcategory_2_vehicles')
            //         ->get();

            //     echo 'Insert data category 2 vehicle (mysql)';
            //     foreach ($pgDataCategory2Vehicle as $rowCategory2Vehicle) {
            //         DB::connection('mysql')
            //             ->table('tblcategory_2_vehicles')
            //             ->insert([
            //                 'kd_category_2_vehicles' => $rowCategory2Vehicle->kd_category_2_vehicles,
            //                 'nm_category_2_vehicles' => $rowCategory2Vehicle->nm_category_2_vehicles,
            //                 'fk_category_vehicles' => $rowCategory2Vehicle->fk_category_vehicles,
            //                 'is_active' => $rowCategory2Vehicle->is_active,
            //             ]);
            //     }
            // # ./Sync category vehicle

            // # Sync Faktur
            //     DB::connection('mysql')->table('tblfaktur')->truncate();

            //     echo 'Get data faktur from db WRS After Sales';
            //     $pgDataFaktur = DB::connection('db_wrs_aftersales')
            //         ->table('transaksi_sales.tblfaktur')
            //         // ->where('tanggal_faktur' ,'>=', '2026-01-01 00:00:00')
            //         ->get();

            //     //     dd($pgDataKpi);

            //     echo 'Insert data faktur (mysql)';
            //     foreach ($pgDataFaktur as $rowFaktur) {
            //         DB::connection('mysql')
            //             ->table('tblfaktur')
            //             ->insert([
            //                 'no_faktur_request' => $rowFaktur->no_faktur_request,
            //                 'tgl_faktur_request' => $rowFaktur->tgl_faktur_request,
            //                 'fk_dealer' => $rowFaktur->fk_dealer,

            //                 'is_sama_customer' => $rowFaktur->is_sama_customer,
            //                 'is_sama_pemilik' => $rowFaktur->is_sama_pemilik,
            //                 'is_sama_pemesan' => $rowFaktur->is_sama_pemesan,

            //                 'pemakai_nm_customer' => $rowFaktur->pemakai_nm_customer,
            //                 'pemakai_kd_telp' => $rowFaktur->pemakai_kd_telp,
            //                 'pemakai_no_telp' => $rowFaktur->pemakai_no_telp,
            //                 'pemakai_kd_hp' => $rowFaktur->pemakai_kd_hp,
            //                 'pemakai_no_hp' => $rowFaktur->pemakai_no_hp,
            //                 'pemakai_alamat' => $rowFaktur->pemakai_alamat,
            //                 'pemakai_fk_kelurahan' => $rowFaktur->pemakai_fk_kelurahan,
            //                 'pemakai_tgl_lahir' => $rowFaktur->pemakai_tgl_lahir,
            //                 'pemakai_jenis_kelamin' => $rowFaktur->pemakai_jenis_kelamin,
            //                 'pemakai_status_perkawinan' => $rowFaktur->pemakai_status_perkawinan,
            //                 'pemakai_agama' => $rowFaktur->pemakai_agama,
            //                 'pemakai_warga_negara' => $rowFaktur->pemakai_warga_negara,
            //                 'pemakai_no_id' => $rowFaktur->pemakai_no_id,
            //                 'pemakai_no_kitas' => $rowFaktur->pemakai_no_kitas,
            //                 'pemakai_nm_negara' => $rowFaktur->pemakai_nm_negara,
            //                 'pemakai_fk_occupation' => $rowFaktur->pemakai_fk_occupation,
            //                 'pemakai_penghasilan_perbulan' => $rowFaktur->pemakai_penghasilan_perbulan,
            //                 'pemakai_status' => $rowFaktur->pemakai_status,
            //                 'pemakai_status_mobil' => $rowFaktur->pemakai_status_mobil,
            //                 'pemakai_jml_mobil_sebelumnya' => $rowFaktur->pemakai_jml_mobil_sebelumnya,

            //                 'pemakai_merek_tambahan1' => $rowFaktur->pemakai_merek_tambahan1,
            //                 'pemakai_type_tambahan1' => $rowFaktur->pemakai_type_tambahan1,
            //                 'pemakai_merek_tambahan2' => $rowFaktur->pemakai_merek_tambahan2,
            //                 'pemakai_merek_tambahan3' => $rowFaktur->pemakai_merek_tambahan3,
            //                 'pemakai_type_tambahan2' => $rowFaktur->pemakai_type_tambahan2,
            //                 'pemakai_type_tambahan3' => $rowFaktur->pemakai_type_tambahan3,

            //                 'pemakai_merek_pengganti' => $rowFaktur->pemakai_merek_pengganti,
            //                 'pemakai_type_pengganti' => $rowFaktur->pemakai_type_pengganti,
            //                 'pemakai_alasan' => $rowFaktur->pemakai_alasan,
            //                 'pemakai_alasan_lainnya' => $rowFaktur->pemakai_alasan_lainnya,

            //                 'fk_spk' => $rowFaktur->fk_spk,
            //                 'fk_vin' => $rowFaktur->fk_vin,
            //                 'fk_model' => $rowFaktur->fk_model,
            //                 'fk_type' => $rowFaktur->fk_type,
            //                 'fk_color' => $rowFaktur->fk_color,

            //                 'tgl_berlaku_faktur' => $rowFaktur->tgl_berlaku_faktur,
            //                 'alasan_reprint' => $rowFaktur->alasan_reprint,

            //                 'fk_customer_perorangan' => $rowFaktur->fk_customer_perorangan,
            //                 'fk_customer_perusahaan' => $rowFaktur->fk_customer_perusahaan,
            //                 'fk_customer_pemerintah' => $rowFaktur->fk_customer_pemerintah,

            //                 'fk_user_tandatangan_faktur' => $rowFaktur->fk_user_tandatangan_faktur,

            //                 'tgl_print_faktur' => $rowFaktur->tgl_print_faktur,
            //                 'tgl_direct' => $rowFaktur->tgl_direct,
            //                 'kita_pickup' => $rowFaktur->kita_pickup,
            //                 'cimb_sent' => $rowFaktur->cimb_sent,

            //                 'remark' => $rowFaktur->remark,

            //                 'fk_user_approval' => $rowFaktur->fk_user_approval,
            //                 'tgl_approval' => $rowFaktur->tgl_approval,
            //                 'status_approval' => $rowFaktur->status_approval,
            //                 'note_approval' => str_replace("\xA0", " ", str_replace("\xC2\xA0", " ", $rowFaktur->note_approval)),



            //                 'status' => $rowFaktur->status,

            //                 'fk_dealer_handover' => $rowFaktur->fk_dealer_handover,
            //                 'tanggal_handover' => validDateTime($rowFaktur->tanggal_handover),
            //             ]);
            //     }
            // # ./Sync Faktur


            # Sync customer

            $chunkSize = 1000;

            $start = microtime(true);

            DB::connection('mysql')->table('tblcustomer')->truncate();


            DB::connection('db_wrs_aftersales')
                ->table('master_aftersales.tblcustomer')
                ->orderBy('kd_customer')
                ->chunk($chunkSize, function ($rows) {

                    $insertData = [];

                    foreach ($rows as $user) {
                        
                        $insertData[] = [
                            'kd_customer' => $user->kd_customer,
                            'nomor_polisi' => sanitizeString($user->nomor_polisi),
                            'type_customer' => $user->type_customer,
                            'nama_customer' => sanitizeString($user->nama_customer),
                            'telephone_1' => sanitizeString($user->telephone_1),
                            'telephone_2' => sanitizeString($user->telephone_2),
                            'alamat' => sanitizeString(preg_replace('/\x{00A0}/u', ' ', $user->alamat)),
                            'email' => sanitizeString($user->email),
                            'no_vin' => preg_replace('/[^\x20-\x7E]/', '', $user->no_vin),
                            'no_mesin' => sanitizeString($user->no_mesin),
                            'fk_model' => $user->fk_model,
                            'fk_type' => $user->fk_type,
                            'fk_color' => $user->fk_color,
                            'tahun' => $user->tahun,
                            'tanggal_penyerahan' => validDatetime($user->tanggal_penyerahan),
                            'tanggal_last_service' => validDatetime($user->tanggal_last_service),
                            'fk_dealer' => $user->fk_dealer,
                            'status_customer' => sanitizeString($user->status_customer),
                            'nm_pemakai' => sanitizeString($user->nm_pemakai),
                            'fk_user_approval' => $user->fk_user_approval,
                            'tgl_approval' => $user->tgl_approval,
                            'status_approval' => sanitizeString($user->status_approval),
                            'note_approval' => sanitizeString($user->note_approval),
                            'last_odo_meter' => $user->last_odo_meter,
                            'fk_religion' => $user->fk_religion,
                            'nm_dpn_pemakai' => sanitizeString($user->nm_dpn_pemakai),
                            'nm_dpn_customer' => sanitizeString($user->nm_dpn_customer),
                            'tanggal_lahir' => $user->tanggal_lahir,
                            'counter_repurchase' => $user->counter_repurchase,
                        ];
                    }

                    DB::connection('mysql')
                        ->table('tblcustomer')
                        ->insert($insertData);


                });

            $executionTime = microtime(true) - $start;

            echo "Execution time: " . number_format($executionTime, 3) . " seconds";
            
            echo 'Sync tblcustomer finish...<br>';


            # ./Sync customer

            // # Sync KPI
            //     DB::connection('mysql')->table('tblkpi')->truncate();

            //     echo 'Get data KPI from db WRS After Sales';
            //     $pgDataKpi = DB::connection('db_wrs_aftersales')
            //         ->table('transaksi_aftersales.tblkpi')
            //         ->where('tanggal_faktur' ,'>=', '2024-01-01 00:00:00')
            //         ->get();

            //     //     dd($pgDataKpi);
            //     echo 'Insert data KPI (mysql)';
            //     foreach ($pgDataKpi as $rowKpi) {
            //         DB::connection('mysql')
            //             ->table('tblkpi')
            //             ->insert([
            //                 'kd_kpi' => $rowKpi->kd_kpi,
            //                 'fk_work_planning' => $rowKpi->fk_work_planning,
            //                 'fk_dealer' => $rowKpi->fk_dealer,
            //                 'fk_category_1_vehicles' => $rowKpi->fk_category_1_vehicles,
            //                 'fk_category_2_vehicles' => $rowKpi->fk_category_2_vehicles,
            //                 'washing' => $rowKpi->washing,
            //                 'customer_request' => $rowKpi->customer_request,
            //                 'tanggal_faktur' => validDate($rowKpi->tanggal_faktur),
            //                 'labor_sales' => $rowKpi->labor_sales,
            //                 'part_sales' => $rowKpi->part_sales,
            //                 'fk_engine_oli' => $rowKpi->fk_engine_oli,
            //                 'qty_engine_oli' => $rowKpi->qty_engine_oli,
            //                 'total_engine_oli' => $rowKpi->total_engine_oli,
            //                 'fk_transmisi_oli' => $rowKpi->fk_transmisi_oli,
            //                 'qty_transmisi_oli' => $rowKpi->qty_transmisi_oli,
            //                 'total_transmisi_oli' => $rowKpi->total_transmisi_oli,
            //                 'qty_tire_sales' => $rowKpi->qty_tire_sales,
            //                 'hrg_tire_sales' => $rowKpi->hrg_tire_sales,
            //                 'qty_battery_sales' => $rowKpi->qty_battery_sales,
            //                 'hrg_battery_sales' => $rowKpi->hrg_battery_sales,
            //                 'aksesoris' => $rowKpi->aksesoris,
            //                 'chemical_sales' => $rowKpi->chemical_sales,
            //                 'other_sales' => $rowKpi->other_sales,
            //                 'srt' => $rowKpi->srt,
            //                 'tgl_selesai_aktual' => $rowKpi->tgl_selesai_aktual,
            //                 'labor_sales_2' => $rowKpi->labor_sales_2,
            //                 'part_sales_2' => $rowKpi->part_sales_2,
            //                 'fk_engine_oli_2' => $rowKpi->fk_engine_oli_2,
            //                 'qty_engine_oli_2' => $rowKpi->qty_engine_oli_2,
            //                 'total_engine_oli_2' => $rowKpi->total_engine_oli_2,
            //                 'fk_transmisi_oli_2' => $rowKpi->fk_transmisi_oli_2,
            //                 'qty_transmisi_oli_2' => $rowKpi->qty_transmisi_oli_2,
            //                 'total_transmisi_oli_2' => $rowKpi->total_transmisi_oli_2,
            //                 'qty_tire_sales_2' => $rowKpi->qty_tire_sales_2,
            //                 'hrg_tire_sales_2' => $rowKpi->hrg_tire_sales_2,
            //                 'qty_battery_sales_2' => $rowKpi->qty_battery_sales_2,
            //                 'hrg_battery_sales_2' => $rowKpi->hrg_battery_sales_2,
            //                 'aksesoris_2' => $rowKpi->aksesoris_2,
            //                 'chemical_sales_2' => $rowKpi->chemical_sales_2,
            //                 'other_sales_2' => $rowKpi->other_sales_2,
            //                 'srt_2' => $rowKpi->srt_2,
            //                 'tgl_selesai_aktual_2' => $rowKpi->tgl_selesai_aktual_2,
            //                 'labor_sales_3' => $rowKpi->labor_sales_3,
            //                 'part_sales_3' => $rowKpi->part_sales_3,
            //                 'fk_engine_oli_3' => $rowKpi->fk_engine_oli_3,
            //                 'qty_engine_oli_3' => $rowKpi->qty_engine_oli_3,
            //                 'total_engine_oli_3' => $rowKpi->total_engine_oli_3,
            //                 'fk_transmisi_oli_3' => $rowKpi->fk_transmisi_oli_3,
            //                 'qty_transmisi_oli_3' => $rowKpi->qty_transmisi_oli_3,
            //                 'total_transmisi_oli_3' => $rowKpi->total_transmisi_oli_3,
            //                 'qty_tire_sales_3' => $rowKpi->qty_tire_sales_3,
            //                 'hrg_tire_sales_3' => $rowKpi->hrg_tire_sales_3,
            //                 'qty_battery_sales_3' => $rowKpi->qty_battery_sales_3,
            //                 'hrg_battery_sales_3' => $rowKpi->hrg_battery_sales_3,
            //                 'chemical_sales_3' => $rowKpi->chemical_sales_3,
            //                 'other_sales_3' => $rowKpi->other_sales_3,
            //                 'srt_3' => $rowKpi->srt_3,
            //                 'tgl_selesai_aktual_3' => $rowKpi->tgl_selesai_aktual_3,
            //                 'pro_active_customer_contact' => $rowKpi->pro_active_customer_contact,
            //                 'fixed_appointments_before_customer_visit' => $rowKpi->fixed_appointments_before_customer_visit,
            //                 'interactive_reception' => $rowKpi->interactive_reception,
            //                 'fixed_repair_price_before_service' => $rowKpi->fixed_repair_price_before_service,
            //                 'part_pre_picking_before_scheduled_service' => $rowKpi->part_pre_picking_before_scheduled_service,
            //                 'all_parts_available_from_warehouse' => $rowKpi->all_parts_available_from_warehouse,
            //                 'invoice_ready_before_vehicles_return' => $rowKpi->invoice_ready_before_vehicles_return,
            //                 'explanation_of_work_done_on_vehicle' => $rowKpi->explanation_of_work_done_on_vehicle,
            //                 'follow_up_call_within_5_days' => $rowKpi->follow_up_call_within_5_days,
            //                 'repeated_repair' => $rowKpi->repeated_repair,
            //                 'counter_follow_up' => $rowKpi->counter_follow_up,
            //                 'counter_reminder' => $rowKpi->counter_reminder,
            //                 'tgl_follow_up' => $rowKpi->tgl_follow_up,
            //                 'status_konfirmasi_1_follow_up' => $rowKpi->status_konfirmasi_1_follow_up,
            //                 'return_job_booking' => $rowKpi->return_job_booking,
            //                 'fk_booking' => $rowKpi->fk_booking,
            //                 'status_konfirmasi_2_follow_up' => $rowKpi->status_konfirmasi_2_follow_up,
            //                 'catatan_follow_up' => $rowKpi->catatan_follow_up,
            //                 'tgl_reminder' => $rowKpi->tgl_reminder,
            //                 'status_konfirmasi_reminder' => $rowKpi->status_konfirmasi_reminder,
            //                 'fk_booking_reminder' => $rowKpi->fk_booking_reminder,
            //                 'alasan_1_reminder' => $rowKpi->alasan_1_reminder,
            //                 'alasan_2_reminder' => $rowKpi->alasan_2_reminder,
            //                 'catatan_reminder' => $rowKpi->catatan_reminder,
            //                 'reschedule_reminder' => $rowKpi->reschedule_reminder,
            //                 'tgl_akan_di_follow_dari' => $rowKpi->tgl_akan_di_follow_dari,
            //                 'tgl_akan_di_follow_sampai' => $rowKpi->tgl_akan_di_follow_sampai,
            //                 'tgl_akan_di_reminder' => $rowKpi->tgl_akan_di_reminder,
            //                 'odo_reminder' => $rowKpi->odo_reminder,
            //                 'no_polisi_terakhir' => $rowKpi->no_polisi_terakhir,
            //                 'is_backdate_kpi' => $rowKpi->is_backdate_kpi,
            //                 'catatan_service' => $rowKpi->catatan_service,
            //                 'pertanyaan_1' => $rowKpi->pertanyaan_1,
            //                 'pertanyaan_2' => $rowKpi->pertanyaan_2,
            //                 'pertanyaan_3' => $rowKpi->pertanyaan_3,
            //                 'pertanyaan_4' => $rowKpi->pertanyaan_4,
            //                 'pertanyaan_5' => $rowKpi->pertanyaan_5,
            //                 'pertanyaan_6' => $rowKpi->pertanyaan_6,
            //                 'pertanyaan_7' => $rowKpi->pertanyaan_7,
            //                 'pertanyaan_8' => $rowKpi->pertanyaan_8,
            //                 'pertanyaan_9' => $rowKpi->pertanyaan_9,
            //                 'pertanyaan_10' => $rowKpi->pertanyaan_10,
            //                 'jawaban_1' => $rowKpi->jawaban_1,
            //                 'jawaban_2' => $rowKpi->jawaban_2,
            //                 'jawaban_3' => $rowKpi->jawaban_3,
            //                 'jawaban_4' => $rowKpi->jawaban_4,
            //                 'jawaban_5' => $rowKpi->jawaban_5,
            //                 'jawaban_6' => $rowKpi->jawaban_6,
            //                 'jawaban_7' => $rowKpi->jawaban_7,
            //                 'jawaban_8' => $rowKpi->jawaban_8,
            //                 'jawaban_9' => $rowKpi->jawaban_9,
            //                 'jawaban_10' => $rowKpi->jawaban_10,
            //                 'jenis_jawaban_1' => $rowKpi->jenis_jawaban_1,
            //                 'jenis_jawaban_2' => $rowKpi->jenis_jawaban_2,
            //                 'jenis_jawaban_3' => $rowKpi->jenis_jawaban_3,
            //                 'jenis_jawaban_4' => $rowKpi->jenis_jawaban_4,
            //                 'jenis_jawaban_5' => $rowKpi->jenis_jawaban_5,
            //                 'jenis_jawaban_6' => $rowKpi->jenis_jawaban_6,
            //                 'jenis_jawaban_7' => $rowKpi->jenis_jawaban_7,
            //                 'jenis_jawaban_8' => $rowKpi->jenis_jawaban_8,
            //                 'jenis_jawaban_9' => $rowKpi->jenis_jawaban_9,
            //                 'jenis_jawaban_10' => $rowKpi->jenis_jawaban_10,
            //                 'status_update_nomor_tlp' => $rowKpi->status_update_nomor_tlp,
            //                 'service_type' => $rowKpi->service_type,
            //                 'uploaded_to_msi' => $rowKpi->uploaded_to_msi
            //             ]);
            //     }




            // # ./Sync KPI






            // DB::connection('mysql')->commit();

            return "<br><br><br> Transaction Sync successful!";
        } catch (Exception $e) {
            // DB::connection('mysql')->rollBack();

            return "<br><br><br> Transaction Sync failed: " . $e->getMessage();
        }
    }
}
