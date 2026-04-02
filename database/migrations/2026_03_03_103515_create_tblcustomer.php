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
            
        Schema::create('tblcustomer', function (Blueprint $table) {

            $table->string('kd_customer')->primaryKey();
            $table->string('nomor_polisi', 30)->nullable();
            $table->string('type_customer', 34)->nullable();
            $table->string('nama_customer', 110)->nullable();
            $table->string('telephone_1', 150)->nullable();
            $table->string('telephone_2', 150)->nullable();
            $table->text('alamat')->nullable();
            $table->string('email', 151)->nullable();
            $table->string('no_vin', 22)->nullable();
            $table->string('no_mesin', 217)->nullable();
            $table->string('fk_model', 27)->nullable();
            $table->string('fk_type', 20)->nullable();
            $table->string('fk_color', 19)->nullable();
            $table->string('tahun',4)->nullable();
            $table->datetime('tanggal_penyerahan')->nullable();
            $table->datetime('tanggal_last_service')->nullable();
            $table->string('fk_dealer', 17)->nullable();
            $table->string('status_customer', 20)->default('Waiting for Approval')->nullable();
            $table->text('nm_pemakai')->nullable();
            $table->string('fk_user_approval', 28)->nullable();
            $table->datetime('tgl_approval')->nullable();
            $table->string('status_approval')->nullable();
            $table->text('note_approval')->nullable();
            $table->decimal('last_odo_meter', 19,2)->nullable();
            $table->string('fk_religion', 28)->nullable();
            $table->string('nm_dpn_pemakai', 5)->nullable();
            $table->string('nm_dpn_customer', 5)->nullable();
            $table->datetime('tanggal_lahir')->nullable();
            $table->string('counter_repurchase', 3)->nullable();

            $table->index('kd_customer');
            $table->index('tanggal_last_service');
            $table->index('fk_dealer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblcustomer');
    }
};
