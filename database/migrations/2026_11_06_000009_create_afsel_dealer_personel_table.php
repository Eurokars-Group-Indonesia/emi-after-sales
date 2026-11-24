<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master Dealer Personel After Sales.
 * Sumber (sync pertama saat go-live): wrs_aftersales → master_aftersales.tbldealer_personil_aftersales
 *   kd_dealer_personil_aftersales → dealer_personel_id
 *   fk_level_personil_aftersales  → dealer_level_personel_id
 *
 * Relasi:
 *  - dealer_level_personel_id → afsel_dealer_level_personel (FOREIGN KEY)
 *  - fk_dealer                → tbldealer.kd_dealer (index saja, tanpa constraint: tbldealer tidak punya
 *                               primary key dan diisi ulang oleh job sync, constraint bisa menggagalkan reload)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afsel_dealer_personel', function (Blueprint $t) {
            $t->string('dealer_personel_id', 255)->primary();
            $t->text('name');
            $t->text('nm_panggilan')->nullable();
            $t->string('dealer_level_personel_id', 255);
            $t->string('nik', 255);
            $t->text('alamat');
            $t->text('picture');
            $t->text('picture_ext');
            $t->dateTime('tgl_keluar')->nullable();
            $t->text('alasan_keluar')->nullable();
            $t->boolean('is_blacklist')->default(false);
            $t->boolean('is_blacklist_atpm')->default(false);
            $t->boolean('is_active')->default(false);
            $t->string('fk_dealer', 255)->nullable();
            $t->dateTime('tgl_masuk')->nullable();
            $t->string('no_ktp', 255)->nullable();

            $t->foreign('dealer_level_personel_id', 'afsel_dealer_personel_level_foreign')
                ->references('dealer_level_personel_id')->on('afsel_dealer_level_personel')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $t->index('dealer_level_personel_id', 'afsel_dealer_personel_level_index');
            $t->index(['fk_dealer', 'is_active'], 'afsel_dealer_personel_dealer_active_index');
            $t->index('nik', 'afsel_dealer_personel_nik_index');
            $t->index('no_ktp', 'afsel_dealer_personel_no_ktp_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afsel_dealer_personel');
    }
};
