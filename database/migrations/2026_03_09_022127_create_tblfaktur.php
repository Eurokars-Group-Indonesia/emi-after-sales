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
        Schema::create('tblfaktur', function (Blueprint $table) {

            $table->string('no_faktur_request');

            $table->date('tgl_faktur_request')->nullable();
            $table->string('fk_dealer')->nullable();

            $table->boolean('is_sama_customer')->default(false);
            $table->boolean('is_sama_pemilik')->default(false);
            $table->boolean('is_sama_pemesan')->default(false);

            $table->text('pemakai_nm_customer')->nullable();
            $table->text('pemakai_kd_telp')->nullable();
            $table->text('pemakai_no_telp')->nullable();
            $table->text('pemakai_kd_hp')->nullable();
            $table->text('pemakai_no_hp')->nullable();
            $table->text('pemakai_alamat')->nullable();
            $table->text('pemakai_fk_kelurahan')->nullable();
            $table->date('pemakai_tgl_lahir')->nullable();
            $table->text('pemakai_jenis_kelamin')->nullable();
            $table->text('pemakai_status_perkawinan')->nullable();
            $table->text('pemakai_agama')->nullable();
            $table->text('pemakai_warga_negara')->nullable();
            $table->text('pemakai_no_id')->nullable();
            $table->text('pemakai_no_kitas')->nullable();
            $table->text('pemakai_nm_negara')->nullable();
            $table->text('pemakai_fk_occupation')->nullable();
            $table->text('pemakai_penghasilan_perbulan')->nullable();
            $table->text('pemakai_status')->nullable();
            $table->text('pemakai_status_mobil')->nullable();
            $table->text('pemakai_jml_mobil_sebelumnya')->nullable();
            $table->text('pemakai_merek_tambahan1')->nullable();
            $table->text('pemakai_type_tambahan1')->nullable();
            $table->text('pemakai_merek_tambahan2')->nullable();
            $table->text('pemakai_merek_tambahan3')->nullable();
            $table->text('pemakai_type_tambahan2')->nullable();
            $table->text('pemakai_type_tambahan3')->nullable();
            $table->text('pemakai_merek_pengganti')->nullable();
            $table->text('pemakai_type_pengganti')->nullable();
            $table->text('pemakai_alasan')->nullable();
            $table->text('pemakai_alasan_lainnya')->nullable();

            $table->string('fk_spk')->nullable();
            $table->string('fk_vin')->nullable();
            $table->string('fk_model')->nullable();
            $table->string('fk_type')->nullable();
            $table->string('fk_color')->nullable();

            $table->date('tgl_berlaku_faktur')->nullable();
            $table->text('alasan_reprint')->nullable();

            $table->text('fk_customer_perorangan')->nullable();
            $table->text('fk_customer_perusahaan')->nullable();
            $table->text('fk_customer_pemerintah')->nullable();

            $table->text('fk_user_tandatangan_faktur')->nullable();
            $table->datetime('tgl_print_faktur')->nullable();
            $table->datetime('tgl_direct')->nullable();
            $table->datetime('kita_pickup')->nullable();
            $table->datetime('cimb_sent')->nullable();

            $table->text('remark')->nullable();

            $table->text('fk_user_approval')->nullable();
            $table->datetime('tgl_approval')->nullable();
            $table->text('status_approval')->nullable();
            $table->text('note_approval')->nullable();

            $table->string('status')->default('Waiting for Approval');

            $table->text('fk_dealer_handover')->nullable();
            $table->datetime('tanggal_handover')->nullable();

            /*
            |--------------------------------------------------------------------------
            | INDEX
            |--------------------------------------------------------------------------
            */

            $table->index('no_faktur_request');
            $table->index('tgl_faktur_request');
            $table->index('tanggal_handover');
            $table->index('fk_dealer');
            $table->index('fk_spk');
            $table->index('fk_vin');
            $table->index('fk_model');
            $table->index('fk_type');
            $table->index('fk_color');
            $table->index('status');


            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblfaktur');
    }
};
