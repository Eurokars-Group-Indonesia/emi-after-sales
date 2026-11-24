<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log Sync MSI (Customer CSI).
 *  - tr_sync_msi_log        : satu baris per eksekusi (jadwal 21:00 / manual / console)
 *  - tr_sync_msi_log_detail : baris customer yang DILEWATI / GAGAL (dan sukses jika SYNC_MSI_LOG_SUCCESS_ROWS=true)
 *
 * Status log   : QUEUED (diminta manual, menunggu scheduler) | RUNNING | SUCCESS | PARTIAL | FAILED | SKIPPED
 * Status detail: SUCCESS | SKIPPED | FAILED
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tr_sync_msi_log')) {
            Schema::create('tr_sync_msi_log', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('trigger_type', 20)->default('schedule')->comment('schedule | manual | console | dry-run');
                $t->string('triggered_by', 255)->nullable();
                $t->string('connection_name', 100)->nullable();
                $t->string('status', 20)->default('QUEUED');
                $t->date('date_from')->nullable()->comment('Parameter log_action_date >= date_from 00:00:00');
                $t->date('date_to')->nullable()->comment('Parameter log_action_date <= date_to 23:59:59');
                $t->boolean('is_default_range')->default(true)->comment('true = date_from dari tblsetting.last_update_csi s/d hari ini');
                $t->text('filter_csi')->nullable()->comment('Snapshot tblsetting.filter_csi saat dijalankan');
                $t->dateTime('last_update_before')->nullable();
                $t->dateTime('last_update_after')->nullable();
                $t->dateTime('queued_at')->nullable();
                $t->dateTime('started_at')->nullable();
                $t->dateTime('finished_at')->nullable();
                $t->unsignedInteger('duration_sec')->nullable();
                $t->unsignedInteger('total_read')->default(0);
                $t->unsignedInteger('total_inserted')->default(0);
                $t->unsignedInteger('total_updated')->default(0);
                $t->unsignedInteger('total_skipped')->default(0);
                $t->unsignedInteger('total_failed')->default(0);
                $t->unsignedInteger('kpi_flagged')->default(0)->comment('Jumlah tblkpi yang di-set uploaded_to_msi = 1');
                $t->text('message')->nullable();
                $t->string('host', 100)->nullable();
                $t->dateTime('date_created');
                $t->dateTime('date_updated')->nullable();

                $t->index('started_at');
                $t->index('status');
            });
        }

        if (!Schema::hasTable('tr_sync_msi_log_detail')) {
            Schema::create('tr_sync_msi_log_detail', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('log_id');
                $t->unsignedInteger('row_no');
                $t->string('kd_customer', 255)->nullable();
                $t->string('no_vin', 50)->nullable();
                $t->string('fk_dealer', 20)->nullable();
                $t->string('log_action_userid', 28)->nullable();
                $t->dateTime('log_action_date')->nullable();
                $t->date('handover_date')->nullable()->comment('tanggal_last_service → dbo.members.handover_date');
                $t->string('reason_for_service', 20)->nullable();
                $t->string('action', 10)->nullable()->comment('INSERT | UPDATE');
                $t->string('status', 20);
                $t->text('message')->nullable();
                $t->dateTime('date_created');

                $t->index(['log_id', 'row_no']);
                $t->index('no_vin');
            });
        }
    }

    /** Sengaja tidak drop table: project ini tidak boleh menghapus data. */
    public function down(): void
    {
    }
};
