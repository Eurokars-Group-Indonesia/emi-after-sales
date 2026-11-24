<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master kendaraan hasil Sync WRS Sales (sync_color.php, sync_model.php, sync_model_color.php, sync_type.php).
 *
 * Catatan konversi dari DDL PostgreSQL ke MySQL:
 *  - nvarchar(255) / kolom PK text → VARCHAR(255) (MySQL tidak bisa PK / index pada TEXT tanpa prefix).
 *  - kolom fk_* yang di-index → VARCHAR(255); kolom lain tetap TEXT.
 *  - numeric → DECIMAL(20,2).
 *  - tblmodel SUDAH ADA (migration 2026_03_05_091043_create_tblmodel) dengan kolom yang sama, jadi tidak dibuat ulang.
 *  - tblsamsat_jenis / tblsamsat_model / tblsamsat_type ikut dibuat karena disinkron oleh sync_type.php.
 * Semua create memakai guard hasTable → aman dijalankan ulang & tidak menyentuh data yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tblcolor')) {
            Schema::create('tblcolor', function (Blueprint $t) {
                $t->string('kd_color', 255)->primary();
                $t->text('nm_color_id');
                $t->text('nm_color_global')->nullable();
                $t->boolean('is_wrs')->default(true);
                $t->boolean('is_wrs_aftersales')->default(true);

                $t->index('is_wrs_aftersales');
            });
        }

        if (!Schema::hasTable('tblmodel')) {
            Schema::create('tblmodel', function (Blueprint $t) {
                $t->string('kd_model', 255)->primary();
                $t->text('nm_model');
                $t->text('jenis_kendaraan');
                $t->boolean('is_wrs')->default(false);
                $t->text('model_year')->nullable();
                $t->text('engine_type')->nullable();
                $t->text('vehicle_type')->nullable();
                $t->text('wmi')->nullable();
                $t->text('hand_over_picture')->nullable();
                $t->text('hand_over_picture_ext')->nullable();
                $t->boolean('is_wrs_aftersales')->default(true);

                $t->index('is_wrs_aftersales');
            });
        }

        if (!Schema::hasTable('tblmodel_color')) {
            Schema::create('tblmodel_color', function (Blueprint $t) {
                $t->string('kd_model_color', 255)->primary()->comment('Document Numbering: tblmodel_color');
                $t->string('fk_model', 255);
                $t->string('fk_color', 255);

                $t->index(['fk_model', 'fk_color']);
                $t->index('fk_color');
            });
        }

        foreach (['jenis', 'model', 'type'] as $samsat) {
            $table = 'tblsamsat_' . $samsat;
            if (!Schema::hasTable($table)) {
                Schema::create($table, function (Blueprint $t) use ($samsat) {
                    $t->string('kd_samsat_' . $samsat, 255)->primary();
                    $t->text('nm_samsat_' . $samsat);
                    $t->boolean('is_active')->nullable()->default(true);
                });
            }
        }

        if (!Schema::hasTable('tbltype')) {
            Schema::create('tbltype', function (Blueprint $t) {
                $t->string('kd_type', 255)->primary();
                $t->text('nm_type');
                $t->text('msc_code')->nullable();
                $t->string('fk_model', 255)->nullable();
                $t->string('fk_samsat_model', 255)->nullable();
                $t->string('fk_samsat_jenis', 255)->nullable();
                $t->string('fk_samsat_type', 255)->nullable();
                $t->string('isi_silinder', 20)->nullable();
                $t->text('fuel')->nullable();
                $t->decimal('harga', 20, 2)->nullable();
                $t->boolean('is_wrs')->default(false);
                $t->text('tpt')->nullable();
                $t->text('sut')->nullable();
                $t->string('pk_id', 255)->comment('Document Numbering: tbltype');
                $t->boolean('is_wrs_aftersales')->default(false);

                $t->unique('pk_id');
                $t->index('fk_model');
                $t->index('is_wrs_aftersales');
            });
        }
    }

    /** Sengaja tidak drop table: project ini tidak boleh menghapus data. */
    public function down(): void
    {
    }
};
