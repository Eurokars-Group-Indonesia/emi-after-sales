<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengganti database/migrations/ms_model_other.php (tanpa prefix timestamp sehingga berjalan
 * setelah 2026_11_18_000001_alter_ms_model_other_audit_columns dan membuat migrate gagal).
 * Dijaga hasTable agar aman di environment yang tabelnya sudah dibuat oleh migration lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ms_model_other')) {
            return;
        }

        Schema::create('ms_model_other', function (Blueprint $table) {
            $table->id();
            $table->string('kd_model')->unique();
            $table->boolean('is_active')->default(true);

            // Diperlebar ke varchar(255) oleh 2026_11_18_000001_alter_ms_model_other_audit_columns.
            $table->unsignedBigInteger('created_by');
            $table->dateTime('date_created');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->dateTime('date_updated')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_model_other');
    }
};
