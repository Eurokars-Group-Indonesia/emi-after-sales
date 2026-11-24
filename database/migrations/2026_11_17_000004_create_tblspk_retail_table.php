<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tblspk_retail hasil Sync WRS Sales (sync_spk.php).
 * Konversi MySQL: PK & kolom yang di-index → VARCHAR(255), numeric → DECIMAL(20,2), kolom lain tetap TEXT
 * (tabel ini ±95 kolom; kalau semua VARCHAR(255) utf8mb4 melewati batas row size MySQL 65.535 byte).
 */
return new class extends Migration
{
    private const TEXT_COLUMNS = [
        'tipe_hot_prospect', 'fk_hot_prospect_perorangan', 'fk_hot_prospect_perusahaan', 'fk_hot_prospect_pemerintah',
    ];

    private const TEXT_COLUMNS_2 = [
        'cara_byr', 'fk_leasing_company', 'tenor',
    ];

    private const PEMESAN_COLUMNS = [
        'tipe_pemesan', 'pemesan_nm_perusahaan', 'pemesan_alamat_perusahaan', 'pemesan_kd_telp_perusahaan',
        'pemesan_no_telp_perusahaan', 'pemesan_no_npwp_perusahaan', 'pemesan_status_perusahaan',
        'pemesan_bidang_usaha_perusahaan', 'pemesan_contact_person_perusahaan', 'pemesan_nm_customer', 'pemesan_kd_telp',
        'pemesan_no_telp', 'pemesan_kd_hp', 'pemesan_no_hp', 'pemesan_alamat', 'pemesan_fk_kelurahan', 'pemesan_warga_negara',
        'pemesan_no_id', 'pemesan_no_kitas', 'pemesan_nm_negara', 'pemesan_nm_pemerintah', 'pemesan_alamat_pemerintah',
        'pemesan_kd_telp_pemerintah', 'pemesan_no_telp_pemerintah', 'pemesan_status_pemerintah',
        'pemesan_bidang_usaha_pemerintah', 'pemesan_contact_person_pemerintah',
        'unit_fk_interior',
    ];

    private const STNK_COLUMNS = [
        'tipe_stnk', 'stnk_nm_perusahaan', 'stnk_alamat_perusahaan', 'stnk_kd_telp_perusahaan', 'stnk_no_telp_perusahaan',
        'stnk_no_npwp_perusahaan', 'stnk_status_perusahaan', 'stnk_bidang_usaha_perusahaan', 'stnk_contact_person_perusahaan',
        'stnk_nama', 'stnk_kd_telp', 'stnk_no_telp', 'stnk_kd_hp', 'stnk_no_hp', 'stnk_alamat', 'stnk_fk_kelurahan',
        'stnk_warga_negara', 'stnk_no_id', 'stnk_no_kitas', 'stnk_nm_negara', 'stnk_nm_pemerintah', 'stnk_alamat_pemerintah',
        'stnk_kd_telp_pemerintah', 'stnk_no_telp_pemerintah', 'stnk_status_pemerintah', 'stnk_bidang_usaha_pemerintah',
        'stnk_contact_person_pemerintah', 'fk_spk_fleet', 'fk_spk_fleet_unit', 'fk_user_approval', 'tgl_approval',
        'status_approval', 'note_approval',
    ];

    public function up(): void
    {
        if (Schema::hasTable('tblspk_retail')) {
            return;
        }

        Schema::create('tblspk_retail', function (Blueprint $t) {
            $t->string('no_spk_mmi', 255)->primary()->comment('Document Numbering: tblspk_retail');
            $t->dateTime('tgl_spk_mmi')->nullable();
            $t->string('no_spk_dealer', 255);
            $t->string('tipe_spk', 100)->nullable();
            $t->string('fk_dealer', 255)->nullable();
            $t->string('fk_sales', 255)->nullable();
            foreach (self::TEXT_COLUMNS as $c) {
                $t->text($c)->nullable();
            }
            $t->string('fk_customer_perorangan', 255)->nullable();
            $t->string('fk_customer_perusahaan', 255)->nullable();
            $t->string('fk_customer_pemerintah', 255)->nullable();
            foreach (self::TEXT_COLUMNS_2 as $c) {
                $t->text($c)->nullable();
            }
            $t->decimal('uang_muka', 20, 2)->nullable();
            $t->date('estimasi_tgl_from')->nullable();
            $t->date('estimasi_tgl_to')->nullable();
            $t->text('fk_sumber_customer')->nullable();
            $t->boolean('is_pemesan_sama_customer')->default(false);
            foreach (self::PEMESAN_COLUMNS as $c) {
                $t->text($c)->nullable();
            }
            $t->string('unit_fk_model', 255)->nullable();
            $t->string('unit_fk_type', 255)->nullable();
            $t->string('unit_fk_color', 255)->nullable();
            $t->text('unit_aksesoris')->nullable();
            $t->decimal('unit_harga_satuan', 20, 2)->nullable();
            $t->boolean('is_stnk_sama_customer')->default(false);
            $t->boolean('is_stnk_sama_pemesan')->default(false);
            foreach (self::STNK_COLUMNS as $c) {
                $t->text($c)->nullable();
            }
            $t->string('status', 100)->default('Waiting for Approval');

            $t->index('no_spk_dealer');
            $t->index(['fk_dealer', 'tgl_spk_mmi']);
            $t->index('tgl_spk_mmi');
            $t->index('fk_sales');
            $t->index('status');
            $t->index('fk_customer_perorangan');
            $t->index('fk_customer_perusahaan');
            $t->index('fk_customer_pemerintah');
            $t->index('unit_fk_model');
            $t->index('unit_fk_type');
            $t->index('unit_fk_color');
        });
    }

    /** Sengaja tidak drop table: project ini tidak boleh menghapus data. */
    public function down(): void
    {
    }
};
