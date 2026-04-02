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
        Schema::create('tblkpi_log', function (Blueprint $table) {

            $table->string('kd_kpi')->nullable();
            $table->text('fk_work_planning')->nullable();
            $table->text('fk_dealer');
            $table->text('fk_customer');
            $table->text('fk_category_1_vehicles')->nullable();
            $table->text('fk_category_2_vehicles')->nullable();

            $table->boolean('washing')->nullable();
            $table->text('customer_request')->nullable();
            $table->datetime('tanggal_faktur')->nullable();

            $table->decimal('labor_sales', 15, 2)->nullable();
            $table->decimal('part_sales', 15, 2)->nullable();

            $table->text('fk_engine_oli')->nullable();
            $table->float('qty_engine_oli')->nullable();
            $table->decimal('total_engine_oli', 15, 2)->nullable();

            $table->text('fk_transmisi_oli')->nullable();
            $table->float('qty_transmisi_oli')->nullable();
            $table->decimal('total_transmisi_oli', 15, 2)->nullable();

            $table->decimal('qty_tire_sales', 15, 2)->nullable();
            $table->decimal('hrg_tire_sales', 15, 2)->nullable();

            $table->decimal('qty_battery_sales', 15, 2)->nullable();
            $table->decimal('hrg_battery_sales', 15, 2)->nullable();

            $table->decimal('aksesoris', 15, 2)->nullable();
            $table->decimal('chemical_sales', 15, 2)->nullable();
            $table->decimal('other_sales', 15, 2)->nullable();

            $table->double('srt')->nullable();
            $table->double('tgl_selesai_aktual')->nullable();

            // ================== REPEAT 2 ==================
            $table->decimal('labor_sales_2', 15, 2)->nullable();
            $table->decimal('part_sales_2', 15, 2)->nullable();
            $table->text('fk_engine_oli_2')->nullable();
            $table->float('qty_engine_oli_2')->nullable();
            $table->decimal('total_engine_oli_2', 15, 2)->nullable();
            $table->text('fk_transmisi_oli_2')->nullable();
            $table->float('qty_transmisi_oli_2')->nullable();
            $table->decimal('total_transmisi_oli_2', 15, 2)->nullable();
            $table->decimal('qty_tire_sales_2', 15, 2)->nullable();
            $table->decimal('hrg_tire_sales_2', 15, 2)->nullable();
            $table->decimal('qty_battery_sales_2', 15, 2)->nullable();
            $table->decimal('hrg_battery_sales_2', 15, 2)->nullable();
            $table->decimal('aksesoris_2', 15, 2)->nullable();
            $table->decimal('chemical_sales_2', 15, 2)->nullable();
            $table->decimal('other_sales_2', 15, 2)->nullable();
            $table->double('srt_2')->nullable();
            $table->double('tgl_selesai_aktual_2')->nullable();

            // ================== REPEAT 3 ==================
            $table->decimal('labor_sales_3', 15, 2)->nullable();
            $table->decimal('part_sales_3', 15, 2)->nullable();
            $table->text('fk_engine_oli_3')->nullable();
            $table->float('qty_engine_oli_3')->nullable();
            $table->decimal('total_engine_oli_3', 15, 2)->nullable();
            $table->text('fk_transmisi_oli_3')->nullable();
            $table->double('qty_transmisi_oli_3')->nullable();
            $table->decimal('total_transmisi_oli_3', 15, 2)->nullable();
            $table->decimal('qty_tire_sales_3', 15, 2)->nullable();
            $table->decimal('hrg_tire_sales_3', 15, 2)->nullable();
            $table->decimal('qty_battery_sales_3', 15, 2)->nullable();
            $table->decimal('hrg_battery_sales_3', 15, 2)->nullable();
            $table->decimal('aksesoris_3', 15, 2)->nullable();
            $table->decimal('chemical_sales_3', 15, 2)->nullable();
            $table->double('other_sales_3')->nullable();
            $table->double('srt_3')->nullable();
            $table->double('tgl_selesai_aktual_3')->nullable();

            // ================== KPI FLAGS ==================
            $table->boolean('pro_active_customer_contact')->nullable();
            $table->boolean('fixed_appointments_before_customer_visit')->nullable();
            $table->boolean('interactive_reception')->nullable();
            $table->boolean('fixed_repair_price_before_service')->nullable();
            $table->boolean('part_pre_picking_before_scheduled_service')->nullable();
            $table->boolean('all_parts_available_from_warehouse')->nullable();
            $table->boolean('invoice_ready_before_vehicles_return')->nullable();
            $table->boolean('explanation_of_work_done_on_vehicle')->nullable();
            $table->boolean('follow_up_call_within_5_days')->nullable();
            $table->boolean('repeated_repair')->nullable();

            $table->decimal('counter_follow_up', 15, 2)->default(0)->nullable();
            $table->decimal('counter_reminder', 15, 2)->default(0)->nullable();

            $table->datetime('tgl_follow_up')->nullable();
            $table->text('status_konfirmasi_1_follow_up')->nullable();
            $table->text('return_job_booking')->nullable();
            $table->text('fk_booking')->nullable();
            $table->text('status_konfirmasi_2_follow_up')->nullable();
            $table->text('catatan_follow_up')->nullable();

            $table->datetime('tgl_reminder')->nullable();
            $table->text('status_konfirmasi_reminder')->nullable();
            $table->text('fk_booking_reminder')->nullable();
            $table->text('alasan_1_reminder')->nullable();
            $table->text('alasan_2_reminder')->nullable();
            $table->text('catatan_reminder')->nullable();
            $table->datetime('reschedule_reminder')->nullable();

            $table->datetime('tgl_akan_di_follow_dari')->nullable();
            $table->datetime('tgl_akan_di_follow_sampai')->nullable();
            $table->datetime('tgl_akan_di_reminder')->nullable();

            $table->decimal('odo_reminder', 15, 2)->default(0)->nullable();
            $table->text('no_polisi_terakhir')->nullable();

            $table->boolean('is_backdate_kpi')->default(false);

            $table->text('catatan_service')->nullable();

            // ================== SURVEY ==================
            for ($i = 1; $i <= 10; $i++) {
                $table->text("pertanyaan_$i")->nullable();
                $table->text("jawaban_$i")->nullable();
                $table->text("jenis_jawaban_$i")->nullable();
            }

            $table->text('status_update_nomor_tlp')->nullable();
            $table->text('service_type')->nullable();
            $table->boolean('uploaded_to_msi')->default(false)->nullable();

            // ================== LOG ==================
            $table->text('log_action_userid')->nullable();
            $table->text('log_action_username')->nullable();
            $table->datetime('log_action_date')->nullable();
            $table->string('log_action_mode', 2)->nullable();

            // PK
            $table->text('pk_id_log');
            

            // Index gabungan (composite index)
            $table->index(['kd_kpi', 'fk_dealer', 'fk_customer', 'fk_category_1_vehicles', 'fk_category_2_vehicles', 'customer_request', 'tanggal_faktur']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblkpi_log');
    }
};
