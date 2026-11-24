<?php

namespace App\Services\Afsel;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sync Dealer Personel dari WRS After Sales (Postgres, read-only)
 *   master_aftersales.tbldealer_personil_aftersales → afsel_dealer_personel (MySQL)
 *
 * Dipakai untuk sync pertama saat go-live. Aman dijalankan ulang:
 *  - personel baru   → ditambahkan
 *  - personel lama   → diperbarui sesuai data WRS, KECUALI Active & Black List EMI (dikelola ATPM)
 *  - personel yang level-nya belum ada di afsel_dealer_level_personel → dilewati (FK), dilaporkan
 *  - personel yang dealer-nya belum ada di tbldealer lokal → tetap disimpan, dilaporkan sebagai peringatan
 *
 * DB sumber ber-encoding SQL_ASCII: teks bisa berisi byte Latin-1 (mis. 0xA0), jadi semua teks dinormalisasi ke UTF-8.
 */
class DealerPersonelSyncService
{
    public const SOURCE_CONNECTION = 'db_wrs_aftersales';
    public const SOURCE_TABLE = 'master_aftersales.tbldealer_personil_aftersales';
    public const TARGET_TABLE = 'afsel_dealer_personel';
    public const LAST_SYNC_CACHE_KEY = 'afsel_dealer_personel_last_sync';

    private const CHUNK = 200;

    /** Kolom yang diubah dari sisi ATPM (tidak ditimpa saat sync ulang). */
    public const ATPM_OWNED_COLUMNS = ['is_active', 'is_blacklist_atpm'];

    public function sync(string $by): array
    {
        $started = microtime(true);

        $source = DB::connection(self::SOURCE_CONNECTION)
            ->table(self::SOURCE_TABLE)
            ->orderBy('kd_dealer_personil_aftersales')
            ->get([
                'kd_dealer_personil_aftersales', 'name', 'nm_panggilan', 'fk_level_personil_aftersales', 'nik',
                'alamat', 'picture', 'picture_ext', 'tgl_keluar', 'alasan_keluar', 'is_blacklist',
                'is_blacklist_atpm', 'is_active', 'fk_dealer', 'tgl_masuk', 'no_ktp',
            ]);

        $levels   = DB::table('afsel_dealer_level_personel')->pluck('dealer_level_personel_id')->flip();
        $dealers  = DB::table('tbldealer')->pluck('kd_dealer')->flip();
        $existing = DB::table(self::TARGET_TABLE)->pluck('dealer_personel_id')->flip();

        $rows = [];
        $skippedLevel = [];
        $unknownDealer = [];

        foreach ($source as $s) {
            if (!isset($levels[$s->fk_level_personil_aftersales])) {
                $skippedLevel[] = ['id' => $s->kd_dealer_personil_aftersales, 'name' => $this->text($s->name), 'level' => $s->fk_level_personil_aftersales];
                continue;
            }

            if ($s->fk_dealer !== null && !isset($dealers[$s->fk_dealer])) {
                $unknownDealer[$s->fk_dealer] = ($unknownDealer[$s->fk_dealer] ?? 0) + 1;
            }

            $rows[] = [
                'dealer_personel_id'       => $s->kd_dealer_personil_aftersales,
                'name'                     => $this->text($s->name),
                'nm_panggilan'             => $this->nullIfBlank($s->nm_panggilan),
                'dealer_level_personel_id' => $s->fk_level_personil_aftersales,
                'nik'                      => $this->text($s->nik),
                'alamat'                   => $this->text($s->alamat),
                'picture'                  => $this->text($s->picture),
                'picture_ext'              => $this->text($s->picture_ext),
                'tgl_keluar'               => $s->tgl_keluar,
                'alasan_keluar'            => $this->nullIfBlank($s->alasan_keluar),
                'is_blacklist'             => (bool) $s->is_blacklist,
                'is_blacklist_atpm'        => (bool) $s->is_blacklist_atpm,
                'is_active'                => (bool) $s->is_active,
                'fk_dealer'                => $this->nullIfBlank($s->fk_dealer),
                'tgl_masuk'                => $s->tgl_masuk,
                'no_ktp'                   => $this->nullIfBlank($s->no_ktp),
            ];
        }

        $inserted = collect($rows)->filter(fn ($r) => !isset($existing[$r['dealer_personel_id']]))->count();
        // Active & Black List EMI dikelola ATPM di aplikasi ini → hanya diisi saat personel baru,
        // tidak ditimpa data WRS ketika sync diulang.
        $updateColumns = array_values(array_diff(array_keys($rows[0] ?? []), ['dealer_personel_id', ...self::ATPM_OWNED_COLUMNS]));

        DB::transaction(function () use ($rows, $updateColumns) {
            foreach (array_chunk($rows, self::CHUNK) as $chunk) {
                DB::table(self::TARGET_TABLE)->upsert($chunk, ['dealer_personel_id'], $updateColumns);
            }
        });

        $result = [
            'source_total'   => $source->count(),
            'synced'         => count($rows),
            'inserted'       => $inserted,
            'updated'        => count($rows) - $inserted,
            'skipped_level'  => $skippedLevel,
            'unknown_dealer' => $unknownDealer,
            'duration_sec'   => round(microtime(true) - $started, 2),
            'synced_by'      => $by,
            'synced_at'      => now()->toDateTimeString(),
        ];

        Cache::forever(self::LAST_SYNC_CACHE_KEY, $result);
        Log::info('[DealerPersonelSync] selesai', collect($result)->except(['skipped_level'])->all() + ['skipped_level_count' => count($skippedLevel)]);

        return $result;
    }

    public function lastSync(): ?array
    {
        return Cache::get(self::LAST_SYNC_CACHE_KEY);
    }

    /** Teks wajib (NOT NULL): UTF-8 valid + trim. */
    private function text($value): string
    {
        return $this->nullIfBlank($value) ?? '';
    }

    /** Teks opsional: UTF-8 valid + trim (termasuk non-breaking space), string kosong → null. */
    private function nullIfBlank($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;
        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }

        $value = preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $value);

        return $value === '' ? null : $value;
    }
}
