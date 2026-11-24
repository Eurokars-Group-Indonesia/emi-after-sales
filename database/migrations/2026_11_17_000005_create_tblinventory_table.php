<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tblinventory hasil Sync WRS Sales (sync_inventory.php).
 * PK = pk_id (unik di WRS Sales). Kombinasi no_vin + fk_dealer TIDAK unik di sumber
 * (126.301 baris vs 123.280 pasangan per Nov 2026), jadi hanya di-index biasa, bukan unique.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tblinventory')) {
            return;
        }

        Schema::create('tblinventory', function (Blueprint $t) {
            $t->string('no_vin', 255);
            $t->string('no_mesin', 255)->nullable();
            $t->string('fk_dealer', 255)->nullable();
            $t->string('fk_type', 255)->nullable();
            $t->string('fk_model', 255)->nullable();
            $t->string('fk_color', 255)->nullable();
            $t->text('fk_csi')->nullable();
            $t->decimal('tahun_pembuatan', 10, 0)->nullable();
            $t->text('formulir_ab')->nullable();
            $t->text('pib')->nullable();
            $t->text('tpt')->nullable();
            $t->text('sut')->nullable();
            $t->text('fk_cross')->nullable();
            $t->string('fk_faktur', 255)->nullable();
            $t->boolean('is_active')->default(false);
            $t->string('pk_id', 255)->primary()->comment('Document Numbering: tblinventory');
            $t->string('fk_spk', 255)->nullable();
            $t->text('srut')->nullable();
            $t->text('csi_fk_color')->nullable();
            $t->string('status', 100)->nullable();
            $t->dateTime('tgl_print_do')->nullable();
            $t->dateTime('tgl_print_serah_terima')->nullable();
            $t->dateTime('tgl_received')->nullable();
            $t->string('fk_customer_perorangan', 255)->nullable();
            $t->string('fk_customer_perusahaan', 255)->nullable();
            $t->string('fk_customer_pemerintah', 255)->nullable();
            $t->text('use_by');
            $t->text('use_id')->nullable();
            $t->text('fk_inventory_adjustment')->nullable();

            $t->index(['no_vin', 'fk_dealer']);
            $t->index(['fk_dealer', 'status']);
            $t->index('no_mesin');
            $t->index('fk_type');
            $t->index('fk_model');
            $t->index('fk_color');
            $t->index('fk_faktur');
            $t->index('fk_spk');
            $t->index('status');
            $t->index('tgl_received');
            $t->index('fk_customer_perorangan');
            $t->index('fk_customer_perusahaan');
            $t->index('fk_customer_pemerintah');
        });
    }

    /** Sengaja tidak drop table: project ini tidak boleh menghapus data. */
    public function down(): void
    {
    }
};
