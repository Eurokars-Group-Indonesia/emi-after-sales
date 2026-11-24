<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tblwork_planning (dari WRS After Sales lama, transaksi_aftersales.tblwork_planning).
 * Dipakai Sync MSI untuk nama Service Advisor (fk_service_advisor → afsel_dealer_personel).
 *
 * Penomoran lewat Document Numbering (format yyyymmdd + 20 digit, nomor urut reset ke 1 setiap ganti tanggal):
 *   kd_work_planning → table_name 'tblwork_planning'         : app(DocumentNumberService::class)->next('tblwork_planning')
 *   pk_id            → table_name 'tblwork_planning_pk_id'   : app(DocumentNumberService::class)->next('tblwork_planning_pk_id')
 *                      (pengganti DEFAULT nextserial('transaksi_aftersales.tblwork_planning.pk_id') di PostgreSQL)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tblwork_planning')) {
            return;
        }

        Schema::create('tblwork_planning', function (Blueprint $t) {
            $t->string('kd_work_planning', 28)->primary()->comment('Document Numbering: tblwork_planning');
            $t->string('fk_booking', 28)->nullable();
            $t->string('fk_dealer', 17);
            $t->dateTime('tgl_work_planning');
            $t->decimal('stall_ke', 20, 2)->nullable();
            $t->string('fk_customer', 255);
            $t->text('no_repair_order');
            $t->text('customer_request');
            $t->dateTime('tgl_datang');
            $t->time('jam_datang');
            $t->dateTime('perkiraan_waktu_selesai');
            $t->string('fk_group_technician', 28);
            $t->string('fk_technician', 28);
            $t->string('fk_service_advisor', 28)->nullable();
            $t->string('status_1', 255);
            $t->string('status_2', 255)->nullable();
            $t->string('ketersediaan_part', 255)->nullable();
            $t->string('konfirmasi_waktu_tiba', 255)->nullable();
            $t->string('status', 255)->nullable()->default('Processing');
            $t->string('pk_id', 28)->unique()->comment('Document Numbering: tblwork_planning_pk_id');
            $t->dateTime('tgl_datang_sebenarnya')->nullable();
            $t->dateTime('perkiraan_waktu_selesai_sebenarnya')->nullable();
            $t->string('fk_technician_2', 28)->nullable();
            $t->double('total_kerja')->default(0);
            $t->dateTime('tgl_mulai_process')->nullable();
            $t->boolean('is_backdate_work_planning')->default(false);
            $t->dateTime('tgl_pending')->nullable();

            $t->index(['fk_customer', 'tgl_work_planning']);
            $t->index(['fk_dealer', 'tgl_work_planning']);
            $t->index('tgl_work_planning');
        });
    }

    /** Sengaja tidak drop table: project ini tidak boleh menghapus data. */
    public function down(): void
    {
    }
};
