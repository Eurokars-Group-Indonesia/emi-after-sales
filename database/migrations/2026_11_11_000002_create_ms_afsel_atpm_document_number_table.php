<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penomoran dokumen format WRS: yyyymmdd + 20 digit nomor urut (mis. 2026111100000000000000000001).
 * Satu baris per table_name. last_number = nomor urut terakhir yang sudah dipakai pada last_date;
 * jika hari berganti, nomor urut kembali ke 1 (lihat App\Services\Afsel\DocumentNumberService).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ms_afsel_atpm_document_number')) {
            Schema::create('ms_afsel_atpm_document_number', function (Blueprint $t) {
                $t->bigIncrements('DocNumId');
                $t->string('table_name', 100)->unique();
                $t->unsignedBigInteger('last_number')->default(0);
                $t->date('last_date')->nullable()->comment('Tanggal nomor urut terakhir; beda hari = reset ke 1');
                $t->string('created_by', 255);
                $t->dateTime('date_created');
                $t->string('updated_by', 255)->nullable();
                $t->dateTime('date_updated')->nullable();
            });
        }

        foreach (['tblcustomer', 'tblkpi'] as $table) {
            if (!DB::table('ms_afsel_atpm_document_number')->where('table_name', $table)->exists()) {
                DB::table('ms_afsel_atpm_document_number')->insert([
                    'table_name'   => $table,
                    'last_number'  => 0,      // nomor pertama yang dipakai = 1
                    'last_date'    => null,
                    'created_by'   => 'SYSTEM-MIGRATION',
                    'date_created' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_afsel_atpm_document_number');
    }
};
