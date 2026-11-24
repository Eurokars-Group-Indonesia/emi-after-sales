<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tblcustomer_log (dari WRS After Sales lama, master_aftersales.tblcustomer_log).
 * Snapshot tblcustomer setiap kali data customer ditambah / diubah, plus info aksi (log_action_*).
 * Sumber utama Sync MSI: customer dengan log_action_date di rentang tanggal sync yang dikirim ke MSI.
 *
 * Tipe kolom data customer mengikuti tblcustomer di WRS Connect.
 * pk_id_log diisi dari Document Numbering (table_name = tblcustomer_log, format yyyymmdd + 20 digit, reset harian):
 *     app(DocumentNumberService::class)->next('tblcustomer_log')
 * log_action_mode mengikuti WRS lama, mis. 'I' (insert), 'UA' (update - data sesudah diubah).
 * log_action_userid = kd user WRS (mis. ms_afsel_dealer_user.kd_dealer_user) → dicocokkan ke dbo.users.kd_user_wrs di MSI.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tblcustomer_log')) {
            return;
        }

        Schema::create('tblcustomer_log', function (Blueprint $t) {
            $t->string('pk_id_log', 28)->primary()->comment('Document Numbering: tblcustomer_log');
            $t->string('kd_customer', 255)->nullable();
            $t->string('nomor_polisi', 30)->nullable();
            $t->string('type_customer', 255)->nullable();
            $t->string('nama_customer', 110)->nullable();
            $t->string('telephone_1', 150)->nullable();
            $t->string('telephone_2', 150)->nullable();
            $t->text('alamat')->nullable();
            $t->string('email', 151)->nullable();
            $t->string('no_vin', 22)->nullable();
            $t->string('no_mesin', 217)->nullable();
            $t->string('fk_model', 27)->nullable();
            $t->string('fk_type', 20)->nullable();
            $t->string('fk_color', 19)->nullable();
            $t->string('tahun', 4)->nullable();
            $t->dateTime('tanggal_penyerahan')->nullable();
            $t->dateTime('tanggal_last_service')->nullable();
            $t->string('fk_dealer', 17)->nullable();
            $t->string('status_customer', 20)->nullable()->default('Waiting for Approval');
            $t->text('nm_pemakai')->nullable();
            $t->string('fk_user_approval_1', 28)->nullable();
            $t->dateTime('tgl_approval_1')->nullable();
            $t->string('status_approval_1', 255)->nullable();
            $t->text('note_approval_1')->nullable();
            $t->decimal('last_odo_meter', 19, 2)->nullable();
            $t->string('fk_religion', 28)->nullable();
            $t->string('nm_dpn_pemakai', 5)->nullable();
            $t->string('nm_dpn_customer', 5)->nullable();
            $t->dateTime('tanggal_lahir')->nullable();
            $t->string('counter_repurchase', 3)->nullable();
            $t->string('log_action_userid', 28);
            $t->string('log_action_username', 255);
            $t->dateTime('log_action_date');
            $t->string('log_action_mode', 2);

            $t->index('log_action_date');
            $t->index(['kd_customer', 'log_action_date']);
        });
    }

    /** Sengaja tidak drop table: project ini tidak boleh menghapus data. */
    public function down(): void
    {
    }
};
