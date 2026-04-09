<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tblkpi', function (Blueprint $table) {
            $table->string('kd_kpi');
            $table->string('fk_work_planning')->nullable();
            $table->string('fk_dealer');
            $table->string('fk_customer')->nullable();
            $table->string('fk_category_1_vehicles')->nullable();
            $table->string('fk_category_2_vehicles')->nullable();
            $table->text('washing')->nullable()->default(null);
            $table->text('customer_request')->nullable();
            $table->datetime('tanggal_faktur')->nullable()->default(null);
            $table->unsignedBigInteger('labor_sales')->nullable()->default(null);
            $table->unsignedBigInteger('part_sales')->nullable()->default(null);
            $table->text('fk_engine_oli')->nullable();
            $table->double('qty_engine_oli')->nullable()->default(null);
            $table->unsignedBigInteger('total_engine_oli')->nullable()->default(null);
            $table->text('fk_transmisi_oli')->nullable();
            $table->double('qty_transmisi_oli')->nullable()->default(null);
            $table->unsignedBigInteger('total_transmisi_oli')->nullable()->default(null);
            $table->unsignedBigInteger('qty_tire_sales')->nullable()->default(null);
            $table->unsignedBigInteger('hrg_tire_sales')->nullable()->default(null);
            $table->unsignedBigInteger('qty_battery_sales')->nullable()->default(null);
            $table->unsignedBigInteger('hrg_battery_sales')->nullable()->default(null);
            $table->unsignedBigInteger('aksesoris')->nullable()->default(null);
            $table->unsignedBigInteger('chemical_sales')->nullable()->default(null);
            $table->double('other_sales')->nullable()->default(null);
            $table->double('srt')->nullable()->default(null);
            $table->double('tgl_selesai_aktual')->nullable()->default(null);
            $table->unsignedBigInteger('labor_sales_2')->nullable()->default(null);
            $table->unsignedBigInteger('part_sales_2')->nullable()->default(null);
            $table->text('fk_engine_oli_2')->nullable();
            $table->double('qty_engine_oli_2')->nullable()->default(null);
            $table->unsignedBigInteger('total_engine_oli_2')->nullable()->default(null);
            $table->text('fk_transmisi_oli_2')->nullable();
            $table->double('qty_transmisi_oli_2')->nullable()->default(null);
            $table->unsignedBigInteger('total_transmisi_oli_2')->nullable()->default(null);
            $table->unsignedBigInteger('qty_tire_sales_2')->nullable()->default(null);
            $table->unsignedBigInteger('hrg_tire_sales_2')->nullable()->default(null);
            $table->unsignedBigInteger('qty_battery_sales_2')->nullable()->default(null);
            $table->unsignedBigInteger('hrg_battery_sales_2')->nullable()->default(null);
            $table->unsignedBigInteger('aksesoris_2')->nullable()->default(null);
            $table->unsignedBigInteger('chemical_sales_2')->nullable()->default(null);
            $table->double('other_sales_2')->nullable()->default(null);
            $table->double('srt_2')->nullable()->default(null);
            $table->double('tgl_selesai_aktual_2')->nullable()->default(null);
            $table->unsignedBigInteger('labor_sales_3')->nullable()->default(null);
            $table->unsignedBigInteger('part_sales_3')->nullable()->default(null);
            $table->text('fk_engine_oli_3')->nullable()->default(null);
            $table->double('qty_engine_oli_3')->nullable()->default(null);
            $table->unsignedBigInteger('total_engine_oli_3')->nullable()->default(null);
            $table->text('fk_transmisi_oli_3')->nullable()->default(null);
            $table->double('qty_transmisi_oli_3')->nullable()->default(null);
            $table->unsignedBigInteger('total_transmisi_oli_3')->nullable()->default(null);
            $table->unsignedBigInteger('qty_tire_sales_3')->nullable()->default(null);
            $table->unsignedBigInteger('hrg_tire_sales_3')->nullable()->default(null);
            $table->unsignedBigInteger('qty_battery_sales_3')->nullable()->default(null);
            $table->unsignedBigInteger('hrg_battery_sales_3')->nullable()->default(null);
            $table->unsignedBigInteger('aksesoris_3')->nullable()->default(null);
            $table->unsignedBigInteger('chemical_sales_3')->nullable()->default(null);
            $table->double('other_sales_3')->nullable()->default(null);
            $table->double('srt_3')->nullable()->default(null);
            $table->double('tgl_selesai_aktual_3')->nullable()->default(null);
            $table->tinyInteger('pro_active_customer_contact')->nullable()->default(null);
            $table->tinyInteger('fixed_appointments_before_customer_visit')->nullable()->default(null);
            $table->tinyInteger('interactive_reception')->nullable()->default(null);
            $table->tinyInteger('fixed_repair_price_before_service')->nullable()->default(null);
            $table->tinyInteger('part_pre_picking_before_scheduled_service')->nullable()->default(null);
            $table->tinyInteger('all_parts_available_from_warehouse')->nullable()->default(null);
            $table->tinyInteger('invoice_ready_before_vehicles_return')->nullable()->default(null);
            $table->tinyInteger('explanation_of_work_done_on_vehicle')->nullable()->default(null);
            $table->tinyInteger('follow_up_call_within_5_days')->nullable()->default(null);
            $table->tinyInteger('repeated_repair')->nullable()->default(null);
            $table->unsignedBigInteger('counter_follow_up')->default(0);
            $table->unsignedBigInteger('counter_reminder')->default(0);
            $table->datetime('tgl_follow_up')->nullable()->default(null);
            $table->text('status_konfirmasi_1_follow_up')->nullable();
            $table->text('return_job_booking')->nullable();
            $table->text('fk_booking')->nullable();
            $table->text('status_konfirmasi_2_follow_up')->nullable();
            $table->text('catatan_follow_up')->nullable();
            $table->datetime('tgl_reminder')->nullable()->default(null);
            $table->text('status_konfirmasi_reminder')->nullable();
            $table->text('fk_booking_reminder')->nullable();
            $table->text('alasan_1_reminder')->nullable();
            $table->text('alasan_2_reminder')->nullable();
            $table->text('catatan_reminder')->nullable();
            $table->datetime('reschedule_reminder')->nullable()->default(null);
            $table->text('tgl_akan_di_follow_dari')->nullable()->default(null);
            $table->datetime('tgl_akan_di_follow_sampai')->nullable()->default(null);
            $table->datetime('tgl_akan_di_reminder')->nullable()->default(null);
            $table->unsignedBigInteger('odo_reminder')->nullable()->default(0);
            $table->text('no_polisi_terakhir')->nullable();
            $table->tinyInteger('is_backdate_kpi')->default(null);
            $table->text('catatan_service')->nullable();
            $table->text('pertanyaan_1')->nullable();
            $table->text('pertanyaan_2')->nullable();
            $table->text('pertanyaan_3')->nullable();
            $table->text('pertanyaan_4')->nullable();
            $table->text('pertanyaan_5')->nullable();
            $table->text('pertanyaan_6')->nullable();
            $table->text('pertanyaan_7')->nullable();
            $table->text('pertanyaan_8')->nullable();
            $table->text('pertanyaan_9')->nullable();
            $table->text('pertanyaan_10')->nullable();
            $table->text('jawaban_1')->nullable();
            $table->text('jawaban_2')->nullable();
            $table->text('jawaban_3')->nullable();
            $table->text('jawaban_4')->nullable();
            $table->text('jawaban_5')->nullable();
            $table->text('jawaban_6')->nullable();
            $table->text('jawaban_7')->nullable();
            $table->text('jawaban_8')->nullable();
            $table->text('jawaban_9')->nullable();
            $table->text('jawaban_10')->nullable();
            $table->text('jenis_jawaban_1')->nullable();
            $table->text('jenis_jawaban_2')->nullable();
            $table->text('jenis_jawaban_3')->nullable();
            $table->text('jenis_jawaban_4')->nullable();
            $table->text('jenis_jawaban_5')->nullable();
            $table->text('jenis_jawaban_6')->nullable();
            $table->text('jenis_jawaban_7')->nullable();
            $table->text('jenis_jawaban_8')->nullable();
            $table->text('jenis_jawaban_9')->nullable();
            $table->text('jenis_jawaban_10')->nullable();
            $table->text('status_update_nomor_tlp')->nullable();
            $table->text('service_type')->nullable();
            $table->tinyInteger('uploaded_to_msi')->nullable()->default(0);


            
            // Index gabungan (composite index)
            $table->index('kd_kpi');
            $table->index('fk_work_planning');
            $table->index('fk_dealer');
            $table->index('fk_customer');
            $table->index('fk_category_1_vehicles');
            $table->index('fk_category_2_vehicles');
            $table->index('tanggal_faktur');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblkpi');
    }
};
