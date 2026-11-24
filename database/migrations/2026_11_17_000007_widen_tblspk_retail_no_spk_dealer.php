<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * tblspk_retail.no_spk_dealer di WRS Sales ada yang > 255 karakter (data lama, mis. 904 karakter),
 * sehingga VARCHAR(255) membuat baris itu gagal disinkron. Kolom dilebarkan ke TEXT (data tetap utuh)
 * dan index diganti prefix index 100 karakter (MySQL tidak bisa index penuh pada TEXT).
 * Aman dijalankan ulang: hanya berjalan jika kolom masih VARCHAR.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tblspk_retail')) {
            return;
        }

        $column = collect(Schema::getColumns('tblspk_retail'))->firstWhere('name', 'no_spk_dealer');
        if (!$column || strtolower($column['type_name']) !== 'varchar') {
            return;
        }

        $hasIndex = collect(Schema::getIndexes('tblspk_retail'))->contains('name', 'tblspk_retail_no_spk_dealer_index');

        DB::statement('ALTER TABLE `tblspk_retail` '
            . ($hasIndex ? 'DROP INDEX `tblspk_retail_no_spk_dealer_index`, ' : '')
            . 'MODIFY `no_spk_dealer` TEXT NOT NULL, '
            . 'ADD INDEX `tblspk_retail_no_spk_dealer_index` (`no_spk_dealer`(100))');
    }

    /** Sengaja kosong: tidak mengecilkan kolom agar tidak ada data yang terpotong. */
    public function down(): void
    {
    }
};
