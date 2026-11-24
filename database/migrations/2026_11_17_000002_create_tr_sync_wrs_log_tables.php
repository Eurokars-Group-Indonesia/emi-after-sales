<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log Sync WRS Sales → WRS Connect After Sales.
 *  - tr_sync_wrs_log        : satu baris per eksekusi job (inventory / faktur)
 *  - tr_sync_wrs_log_detail : satu baris per tabel (step) di dalam eksekusi
 *
 * Status log   : QUEUED (diminta manual, menunggu scheduler) | RUNNING | SUCCESS | PARTIAL | FAILED | SKIPPED
 * Status detail: RUNNING | SUCCESS | PARTIAL | FAILED
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tr_sync_wrs_log')) {
            Schema::create('tr_sync_wrs_log', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('job_code', 50)->comment('inventory | faktur');
                $t->string('trigger_type', 20)->default('schedule')->comment('schedule | manual | console');
                $t->string('triggered_by', 255)->nullable();
                $t->string('connection_name', 100)->nullable();
                $t->string('status', 20)->default('QUEUED');
                $t->dateTime('queued_at')->nullable();
                $t->dateTime('started_at')->nullable();
                $t->dateTime('finished_at')->nullable();
                $t->unsignedInteger('duration_sec')->nullable();
                $t->unsignedInteger('total_read')->default(0);
                $t->unsignedInteger('total_inserted')->default(0);
                $t->unsignedInteger('total_updated')->default(0);
                $t->unsignedInteger('total_unchanged')->default(0);
                $t->unsignedInteger('total_skipped')->default(0);
                $t->unsignedInteger('total_failed')->default(0);
                $t->text('message')->nullable();
                $t->string('host', 100)->nullable()->comment('Hostname container/server yang menjalankan');
                $t->dateTime('date_created');
                $t->dateTime('date_updated')->nullable();

                $t->index(['job_code', 'started_at']);
                $t->index(['job_code', 'status']);
                $t->index('status');
            });
        }

        if (!Schema::hasTable('tr_sync_wrs_log_detail')) {
            Schema::create('tr_sync_wrs_log_detail', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('log_id');
                $t->unsignedSmallInteger('step_order');
                $t->string('step_code', 50);
                $t->string('legacy_script', 100)->nullable()->comment('Script PHP lama yang digantikan');
                $t->string('source_table', 100);
                $t->string('target_table', 100);
                $t->string('status', 20)->default('RUNNING');
                $t->dateTime('started_at')->nullable();
                $t->dateTime('finished_at')->nullable();
                $t->unsignedInteger('duration_sec')->nullable();
                $t->unsignedInteger('rows_read')->default(0);
                $t->unsignedInteger('rows_inserted')->default(0);
                $t->unsignedInteger('rows_updated')->default(0);
                $t->unsignedInteger('rows_unchanged')->default(0);
                $t->unsignedInteger('rows_skipped')->default(0);
                $t->unsignedInteger('rows_failed')->default(0);
                $t->text('message')->nullable();
                $t->longText('error_samples')->nullable()->comment('JSON contoh baris gagal (maks 20)');
                $t->dateTime('date_created');
                $t->dateTime('date_updated')->nullable();

                $t->index(['log_id', 'step_order']);
                $t->index(['step_code', 'started_at']);
            });
        }
    }

    /** Sengaja tidak drop table: project ini tidak boleh menghapus data. */
    public function down(): void
    {
    }
};
