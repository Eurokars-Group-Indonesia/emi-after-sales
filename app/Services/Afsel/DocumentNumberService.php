<?php

namespace App\Services\Afsel;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Nomor dokumen format WRS: yyyymmdd + 20 digit nomor urut per hari, mis. 2026111100000000000000000001.
 * Counter disimpan di ms_afsel_atpm_document_number (satu baris per table_name).
 *
 * Aman dipakai bersamaan oleh banyak dealer:
 *  - baris counter dikunci (SELECT ... FOR UPDATE) di transaksi PENDEK miliknya sendiri, lalu langsung commit,
 *    jadi upload yang lama tidak menahan kunci & tidak membuat dealer lain menunggu;
 *  - nomor diambil sekaligus per blok (reserve N nomor = 1x lock);
 *  - nomor tidak pernah turun di bawah nomor terbesar hari ini yang sudah ada di tabel tujuan
 *    (mis. data dari sync WRS), sehingga tidak bentrok dengan kode yang sudah ada.
 * Jika proses setelah reserve gagal/rollback, nomor yang sudah direserve tidak dipakai lagi (boleh loncat).
 */
class DocumentNumberService
{
    public const TABLE = 'ms_afsel_atpm_document_number';

    /** Kolom kode di tabel tujuan, untuk menjaga agar nomor tidak bentrok dengan data yang sudah ada. */
    public const KEY_COLUMNS = [
        'tblcustomer' => 'kd_customer',
        'tblkpi'      => 'kd_kpi',
        'tblcategory_vehicles'   => 'kd_category_vehicles',
        'tblcategory_2_vehicles' => 'kd_category_2_vehicles',
        // Tabel hasil Sync WRS Sales: nomor lokal tidak boleh bentrok dengan kode dari WRS Sales
        'tblmodel_color' => 'kd_model_color',
        'tbltype'        => 'pk_id',
        'tblspk_retail'  => 'no_spk_mmi',
        'tblinventory'   => 'pk_id',
        // Sumber Sync MSI (Customer CSI)
        'tblcustomer_log'  => 'pk_id_log',
        'tblwork_planning' => 'kd_work_planning',
        // Kolom nomor kedua di tabel yang sama: [tabel, kolom]
        'tblwork_planning_pk_id' => ['tblwork_planning', 'pk_id'],
    ];

    private const SEQ_LENGTH = 20;

    public function next(string $tableName): string
    {
        return $this->reserve($tableName, 1)[0];
    }

    /**
     * Ambil $count nomor berurutan sekaligus.
     *
     * @return string[]
     */
    public function reserve(string $tableName, int $count): array
    {
        if ($count < 1) {
            return [];
        }

        $today  = now()->toDateString();
        $prefix = now()->format('Ymd');
        $by     = (string) (session('user.id') ?: 'SYSTEM');

        [$first, $last] = DB::transaction(function () use ($tableName, $count, $today, $prefix, $by) {
            $row = $this->lockRow($tableName, $by);

            $current = ($row->last_date && substr((string) $row->last_date, 0, 10) === $today)
                ? (int) $row->last_number
                : 0;   // hari berganti → mulai lagi dari 1

            $current = max($current, $this->maxExistingSequence($tableName, $prefix));
            $last    = $current + $count;

            DB::table(self::TABLE)->where('DocNumId', $row->DocNumId)->update([
                'last_number'  => $last,
                'last_date'    => $today,
                'updated_by'   => $by,
                'date_updated' => now(),
            ]);

            return [$current + 1, $last];
        });

        $numbers = [];
        for ($n = $first; $n <= $last; $n++) {
            $numbers[] = $this->format($prefix, $n);
        }

        return $numbers;
    }

    /** Preview nomor berikutnya (tanpa mengubah counter). */
    public function preview(object $row): string
    {
        $today = now()->toDateString();
        $last  = ($row->last_date && substr((string) $row->last_date, 0, 10) === $today) ? (int) $row->last_number : 0;

        return $this->format(now()->format('Ymd'), $last + 1);
    }

    public function format(string $prefix, int $number): string
    {
        $seq = (string) $number;
        if (strlen($seq) > self::SEQ_LENGTH) {
            throw new RuntimeException('Nomor urut dokumen melebihi 20 digit.');
        }

        return $prefix . str_pad($seq, self::SEQ_LENGTH, '0', STR_PAD_LEFT);
    }

    private function lockRow(string $tableName, string $by): object
    {
        $row = DB::table(self::TABLE)->where('table_name', $tableName)->lockForUpdate()->first();
        if ($row) {
            return $row;
        }

        // table_name belum terdaftar → dibuat otomatis (insertOrIgnore aman jika dibuat bersamaan)
        DB::table(self::TABLE)->insertOrIgnore([
            'table_name'   => $tableName,
            'last_number'  => 0,
            'last_date'    => null,
            'created_by'   => $by,
            'date_created' => now(),
        ]);

        return DB::table(self::TABLE)->where('table_name', $tableName)->lockForUpdate()->first();
    }

    /** Nomor urut terbesar hari ini yang sudah ada di tabel tujuan (pakai index kode, sangat cepat). */
    private function maxExistingSequence(string $tableName, string $prefix): int
    {
        $column = self::KEY_COLUMNS[$tableName] ?? null;
        $table  = $tableName;
        if (is_array($column)) {
            [$table, $column] = $column;
        }
        if (!$column || !Schema::hasTable($table)) {
            return 0;
        }

        $max = DB::table($table)
            ->where($column, '>=', $prefix . str_repeat('0', self::SEQ_LENGTH))
            ->where($column, '<=', $prefix . str_repeat('9', self::SEQ_LENGTH))
            ->whereRaw('CHAR_LENGTH(' . $column . ') = ?', [8 + self::SEQ_LENGTH])
            ->max($column);

        return $max ? (int) ltrim(substr($max, 8), '0') : 0;
    }
}
