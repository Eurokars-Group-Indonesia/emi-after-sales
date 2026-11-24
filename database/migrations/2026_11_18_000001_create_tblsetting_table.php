<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tblsetting (dari WRS After Sales lama, public.tblsetting).
 * Dipakai Sync MSI: filter_csi (filter WHERE tambahan) & last_update_csi (tanggal awal default sync).
 * Struktur mengikuti tabel lama (tanpa primary key). Data awal diisi oleh Database\Seeders\TblSettingSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tblsetting')) {
            return;
        }

        Schema::create('tblsetting', function (Blueprint $t) {
            $t->smallInteger('current_month');
            $t->integer('current_year');
            $t->text('postgre_url')->nullable();
            $t->decimal('weeks', 20, 2)->nullable()->default(1);
            $t->text('target_booking')->nullable();
            $t->text('target_mandatory_parts')->nullable();
            $t->text('target_reminder')->nullable();
            $t->text('target_retention')->nullable();
            $t->decimal('passing_point', 20, 2)->nullable();
            $t->text('filter_csi')->nullable()->comment('Potongan WHERE tambahan untuk Sync MSI (Customer CSI)');
            $t->dateTime('last_update_csi')->nullable()->comment('Tanggal awal default Sync MSI; dimajukan setelah sync sukses');
        });
    }

    /** Sengaja tidak drop table: project ini tidak boleh menghapus data. */
    public function down(): void
    {
    }
};
