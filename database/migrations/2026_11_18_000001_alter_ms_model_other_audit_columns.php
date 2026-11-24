<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kode user ATPM berupa string 28 digit (mis. 2012090600000000000000000001) sehingga tidak muat
 * di BIGINT UNSIGNED. Samakan kolom audit ms_model_other dengan tabel afsel lain (varchar 255).
 * Hanya memperlebar tipe kolom; tidak ada data yang dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ms_model_other', function (Blueprint $table) {
            $table->string('created_by')->change();
            $table->string('updated_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ms_model_other', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->change();
            $table->unsignedBigInteger('updated_by')->nullable()->change();
        });
    }
};
