<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Setting koneksi database sumber untuk Sync WRS Sales → WRS Connect After Sales.
 * Satu baris per koneksi (dipanggil berdasarkan `name`, mis. WRS_SALES).
 * Password disimpan terenkripsi (Crypt / APP_KEY) — jika APP_KEY diganti, password harus diisi ulang lewat menu.
 *
 * Baris WRS_SALES diisi awal dari config database.connections.db_wrs_sales (.env DB_WRSSALES_*),
 * hanya jika belum ada. Setelah itu dikelola dari menu Utility > Koneksi Database Sync.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ms_sync_db_connection')) {
            Schema::create('ms_sync_db_connection', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('name', 100)->unique()->comment('Kode koneksi, dipakai oleh job sync (mis. WRS_SALES)');
                $t->string('driver', 20)->default('pgsql');
                $t->string('hostname', 255);
                $t->string('username', 255);
                $t->text('password')->nullable()->comment('Terenkripsi (Crypt::encryptString)');
                $t->string('database', 255);
                $t->string('port', 10)->default('5432');
                $t->text('remarks')->nullable();
                $t->boolean('is_active')->default(true)->comment('false = semua job yang memakai koneksi ini dilewati');
                $t->string('created_by', 255);
                $t->dateTime('date_created');
                $t->string('updated_by', 255)->nullable();
                $t->dateTime('date_updated')->nullable();

                $t->index('is_active');
            });
        }

        $src = config('database.connections.db_wrs_sales', []);

        if (!empty($src['host']) && !DB::table('ms_sync_db_connection')->where('name', 'WRS_SALES')->exists()) {
            DB::table('ms_sync_db_connection')->insert([
                'name'         => 'WRS_SALES',
                'driver'       => 'pgsql',
                'hostname'     => $src['host'],
                'username'     => (string) ($src['username'] ?? ''),
                'password'     => !empty($src['password']) ? Crypt::encryptString((string) $src['password']) : null,
                'database'     => (string) ($src['database'] ?? 'wrs'),
                'port'         => (string) ($src['port'] ?? '5432'),
                'remarks'      => 'Database WRS Sales (sumber sync inventory & faktur). Diisi otomatis dari .env DB_WRSSALES_*.',
                'is_active'    => true,
                'created_by'   => 'SYSTEM-MIGRATION',
                'date_created' => now(),
            ]);
        }
    }

    /** Sengaja tidak drop table: project ini tidak boleh menghapus data. */
    public function down(): void
    {
    }
};
