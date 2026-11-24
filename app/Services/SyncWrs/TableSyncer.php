<?php

namespace App\Services\SyncWrs;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Sinkron satu tabel (step) dari DB sumber ke DB default aplikasi.
 *
 *  - Sumber dibaca per blok dengan keyset pagination (WHERE key > last ORDER BY key LIMIT n) → hemat memori.
 *  - Per blok: ambil baris target yang key-nya sama, lalu
 *      belum ada  → INSERT (kecuali status masuk skip_insert, mis. 'Canceled')
 *      sudah ada  → UPDATE hanya kolom yang nilainya berubah; kalau tidak ada perubahan → unchanged
 *  - Tulis per blok dalam 1 transaksi; jika blok gagal, diulang per baris agar baris lain tetap masuk
 *    dan baris yang gagal dicatat (key + pesan error).
 *  - TIDAK ADA DELETE. Baris yang hilang dari sumber dibiarkan di target.
 */
class TableSyncer
{
    /** @var array<string, array{kind: string, scale?: int, max?: string}> */
    private array $types = [];

    private array $stats = [
        'read' => 0, 'inserted' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'failed' => 0,
        'warnings' => [], 'errors' => [], 'diffs' => [],
    ];

    public function __construct(
        private string $source,
        private array $step,
        private int $chunk = 1000,
        private int $maxErrorSamples = 20,
        private string $timezone = 'Asia/Jakarta',
        private bool $dryRun = false,
    ) {
    }

    /**
     * @param  callable(array): void  $progress  dipanggil setelah setiap blok
     */
    public function run(?callable $progress = null): array
    {
        [$srcKey] = $this->step['key'];
        $this->types = $this->targetTypes();

        $skipCol  = $this->step['skip_insert'][0] ?? null;
        $srcCols  = array_values(array_unique(array_merge(array_values($this->step['columns']), $skipCol ? [$skipCol] : [])));
        $last     = null;

        do {
            $query = DB::connection($this->source)->table($this->step['source'])
                ->select($srcCols)
                ->orderBy($srcKey)
                ->limit($this->chunk);
            if ($last !== null) {
                $query->where($srcKey, '>', $last);
            }

            $rows = $query->get();
            if ($rows->isEmpty()) {
                break;
            }

            $last = $rows->last()->{$srcKey};
            $this->stats['read'] += $rows->count();
            $this->processChunk($rows);

            if ($progress) {
                $progress($this->stats);
            }
        } while ($rows->count() === $this->chunk);

        return $this->stats;
    }

    private function processChunk(Collection $rows): void
    {
        [$srcKey, $tgtKey] = $this->step['key'];
        $columns        = $this->step['columns'];
        $insertOnly     = array_flip($this->step['insert_only'] ?? []);
        $insertDefaults = $this->normalizeRow($this->step['insert_defaults'] ?? []);
        $updateDefaults = $this->normalizeRow($this->step['update_defaults'] ?? []);
        [$skipCol, $skipValues] = $this->step['skip_insert'] ?? [null, []];

        // 1) Siapkan data sumber dalam format target
        $prepared = [];
        foreach ($rows as $row) {
            $key  = (string) $row->{$srcKey};
            $data = [];
            foreach ($columns as $tgtCol => $srcCol) {
                $data[$tgtCol] = $this->normalize($tgtCol, $row->{$srcCol} ?? null, $key);
            }
            $prepared[] = [
                'key'  => $key,
                'data' => $data,
                'skip' => $skipCol !== null && in_array($row->{$skipCol} ?? null, $skipValues, true),
            ];
        }

        // 2) Baris target yang sudah ada (match key mengikuti collation MySQL yang case-insensitive)
        $compareCols = array_values(array_unique(array_merge(array_keys($columns), array_keys($updateDefaults))));
        // Dipecah per 200 key: IN (...) dengan ratusan parameter prepared statement di MySQL 8 bisa berhenti memakai
        // index (terukur: 1000 key = ±20 detik, 200 key = 0,04 detik).
        $existing = collect();
        foreach (array_chunk(array_column($prepared, 'key'), 200) as $keys) {
            DB::table($this->step['target'])->whereIn($tgtKey, $keys)->get($compareCols)
                ->each(function ($r) use ($existing, $tgtKey) {
                    $existing->put(mb_strtolower((string) $r->{$tgtKey}), $r);
                });
        }

        // 3) Tentukan insert / update / unchanged / skipped
        $inserts = [];
        $updates = [];
        foreach ($prepared as $p) {
            $current = $existing->get(mb_strtolower($p['key']));

            if (!$current) {
                if ($p['skip']) {
                    $this->stats['skipped']++;
                } else {
                    $inserts[] = $p['data'] + $insertDefaults;
                }
                continue;
            }

            $desired = array_diff_key($p['data'], [$tgtKey => true], $insertOnly) + $updateDefaults;
            $changes = [];
            foreach ($desired as $col => $value) {
                if ($this->normalize($col, $current->{$col} ?? null) !== $value) {
                    $changes[$col] = $value;
                }
            }

            if ($changes) {
                $updates[] = ['key' => (string) $current->{$tgtKey}, 'changes' => $changes];
                if (count($this->stats['diffs']) < 10) {
                    $diff = [];
                    foreach ($changes as $col => $new) {
                        $diff[$col] = [$this->normalize($col, $current->{$col} ?? null), $new]; // [lama, baru]
                    }
                    $this->stats['diffs'][] = ['key' => $p['key'], 'changes' => $diff];
                }
            } else {
                $this->stats['unchanged']++;
            }
        }

        if (!$inserts && !$updates) {
            return;
        }

        if ($this->dryRun) {
            $this->stats['inserted'] += count($inserts);
            $this->stats['updated']  += count($updates);

            return;
        }

        // 4) Tulis per blok dalam satu transaksi; jika gagal ulang per baris
        $table     = $this->step['target'];
        $batchSize = max(1, min(500, intdiv(20000, max(1, count($columns) + count($insertDefaults)))));

        try {
            DB::transaction(function () use ($table, $tgtKey, $inserts, $updates, $batchSize) {
                foreach (array_chunk($inserts, $batchSize) as $batch) {
                    DB::table($table)->insert($batch);
                }
                foreach ($updates as $u) {
                    DB::table($table)->where($tgtKey, $u['key'])->update($u['changes']);
                }
            });

            $this->stats['inserted'] += count($inserts);
            $this->stats['updated']  += count($updates);
        } catch (Throwable) {
            // Blok gagal → transaksi di-rollback. Ulang insert dengan bisection (bagi dua terus sampai ketemu baris
            // yang bermasalah) supaya baris lain tetap masuk tanpa harus insert satu per satu.
            foreach (array_chunk($inserts, $batchSize) as $batch) {
                $this->insertBisect($table, $tgtKey, $batch);
            }
            foreach ($updates as $u) {
                try {
                    DB::table($table)->where($tgtKey, $u['key'])->update($u['changes']);
                    $this->stats['updated']++;
                } catch (Throwable $e) {
                    $this->fail($u['key'], 'update', $e);
                }
            }
        }
    }

    private function insertBisect(string $table, string $tgtKey, array $rows): void
    {
        try {
            DB::table($table)->insert($rows);
            $this->stats['inserted'] += count($rows);
        } catch (Throwable $e) {
            if (count($rows) === 1) {
                $this->fail((string) $rows[0][$tgtKey], 'insert', $e);

                return;
            }
            $half = intdiv(count($rows), 2);
            $this->insertBisect($table, $tgtKey, array_slice($rows, 0, $half));
            $this->insertBisect($table, $tgtKey, array_slice($rows, $half));
        }
    }

    private function fail(string $key, string $action, Throwable $e): void
    {
        $this->stats['failed']++;
        if (count($this->stats['errors']) < $this->maxErrorSamples) {
            $this->stats['errors'][] = ['key' => $key, 'action' => $action, 'error' => Str::limit($e->getMessage(), 500)];
        }
    }

    private function normalizeRow(array $values): array
    {
        $out = [];
        foreach ($values as $col => $value) {
            $out[$col] = $this->normalize($col, $value);
        }

        return $out;
    }

    /**
     * Ubah nilai ke bentuk kanonik sesuai tipe kolom target (dipakai untuk menulis & membandingkan).
     * $key diisi hanya untuk nilai dari sumber, agar peringatan bisa mencatat baris mana.
     */
    private function normalize(string $col, mixed $value, ?string $key = null): mixed
    {
        if ($value === null) {
            return null;
        }

        $type = $this->types[$col] ?? ['kind' => 'string'];

        switch ($type['kind']) {
            case 'bool':
                if (is_bool($value)) {
                    return (int) $value;
                }
                $v = strtolower(trim((string) $value));
                if ($v === '') {
                    return null;
                }

                return in_array($v, ['t', 'true', '1', 'y', 'yes', 'on'], true) ? 1 : 0;

            case 'int':
                return trim((string) $value) === '' ? null : (int) $value;

            case 'decimal':
                return $this->normalizeDecimal($col, (string) $value, $type, $key);

            case 'date':
                return $this->normalizeDate($col, (string) $value, 'Y-m-d', $key);

            case 'datetime':
                return $this->normalizeDate($col, (string) $value, 'Y-m-d H:i:s', $key);

            default:
                if (is_bool($value)) {
                    return $value ? 'true' : 'false';
                }
                $value = (string) $value;

                // WRS Sales (PostgreSQL 8.2) menyimpan sebagian teks dalam byte Latin-1/Windows-1252 → ubah ke UTF-8
                if (!mb_check_encoding($value, 'UTF-8')) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
                    $this->warn($col, $key, 'teks non-UTF-8 dikonversi dari Windows-1252', false);
                }

                // Melebihi panjang VARCHAR hanya karena spasi di belakang → spasi dipotong (tidak ada isi yang hilang)
                if (isset($type['max']) && mb_strlen($value) > $type['max'] && mb_strlen(rtrim($value)) <= $type['max']) {
                    $value = rtrim($value);
                    $this->warn($col, $key, 'spasi di belakang dipotong (melebihi panjang kolom)', false);
                }

                // Sama seperti script lama (cek PHP "falsy"): '' dan '0' disimpan NULL, kecuali kolom NOT NULL
                return (($value === '' || $value === '0') && ($type['nullable'] ?? true)) ? null : $value;
        }
    }

    private function normalizeDecimal(string $col, string $value, array $type, ?string $key): ?string
    {
        $value = trim($value);
        if ($value === '' || !is_numeric($value)) {
            if ($value !== '') {
                $this->warn($col, $key, 'bukan angka');
            }

            return null;
        }

        if (stripos($value, 'e') !== false) {
            $value = number_format((float) $value, $type['scale'], '.', '');
        }

        $negative = str_starts_with($value, '-');
        [$int, $frac] = array_pad(explode('.', ltrim($value, '+-'), 2), 2, '');
        $int = ltrim($int, '0');
        $frac = substr(str_pad($frac, $type['scale'], '0'), 0, $type['scale']);

        if (strlen($int) > $type['int_digits']) {
            $this->warn($col, $key, 'di luar jangkauan DECIMAL');

            return null;
        }

        $result = ($int === '' ? '0' : $int) . ($type['scale'] > 0 ? '.' . $frac : '');
        $isZero = trim($result, '0.') === '';

        return ($negative && !$isZero ? '-' : '') . $result;
    }

    private function normalizeDate(string $col, string $value, string $format, ?string $key): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // Format umum dari DB → tidak perlu parse
        if ($format === 'Y-m-d' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }
        if ($format === 'Y-m-d H:i:s' && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        try {
            // timestamptz (mis. 2026-11-16 16:28:35+07) dikonversi ke zona waktu aplikasi
            return Carbon::parse($value, $this->timezone)->setTimezone($this->timezone)->format($format);
        } catch (Throwable) {
            $this->warn($col, $key, 'format tanggal tidak valid');

            return null;
        }
    }

    private function warn(string $col, ?string $key, string $reason, bool $nulled = true): void
    {
        if ($key === null) {
            return; // nilai dari target, bukan sumber
        }

        $id = "{$col}: {$reason}";
        $this->stats['warnings'][$id] ??= ['count' => 0, 'examples' => [], 'nulled' => $nulled];
        $this->stats['warnings'][$id]['count']++;
        if (count($this->stats['warnings'][$id]['examples']) < 5) {
            $this->stats['warnings'][$id]['examples'][] = $key;
        }
    }

    /** Tipe kolom target dari information_schema MySQL. */
    private function targetTypes(): array
    {
        $table = $this->step['target'];
        if (!Schema::hasTable($table)) {
            throw new RuntimeException("Tabel target {$table} belum ada. Jalankan php artisan migrate.");
        }

        $types = [];
        foreach (Schema::getColumns($table) as $c) {
            $nullable = (bool) $c['nullable'];
            $name = strtolower($c['type_name']);
            $full = strtolower($c['type']);

            if ($name === 'tinyint' && str_starts_with($full, 'tinyint(1)') || in_array($name, ['bool', 'boolean'], true)) {
                $types[$c['name']] = ['kind' => 'bool'];
            } elseif (in_array($name, ['tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint'], true)) {
                $types[$c['name']] = ['kind' => 'int'];
            } elseif (in_array($name, ['decimal', 'numeric'], true)) {
                preg_match('/\((\d+)\s*(?:,\s*(\d+))?\)/', $full, $m);
                $precision = (int) ($m[1] ?? 10);
                $scale     = (int) ($m[2] ?? 0);
                $types[$c['name']] = ['kind' => 'decimal', 'scale' => $scale, 'int_digits' => $precision - $scale];
            } elseif ($name === 'date') {
                $types[$c['name']] = ['kind' => 'date'];
            } elseif (in_array($name, ['datetime', 'timestamp'], true)) {
                $types[$c['name']] = ['kind' => 'datetime'];
            } else {
                $types[$c['name']] = ['kind' => 'string'];
                if (preg_match('/^(?:var)?char\((\d+)\)/', $full, $m)) {
                    $types[$c['name']]['max'] = (int) $m[1];
                }
            }
            $types[$c['name']]['nullable'] = $nullable;
        }

        $wanted  = array_merge(
            array_keys($this->step['columns']),
            array_keys($this->step['insert_defaults'] ?? []),
            array_keys($this->step['update_defaults'] ?? [])
        );
        $missing = array_diff($wanted, array_keys($types));
        if ($missing) {
            throw new RuntimeException("Kolom tidak ditemukan di tabel target {$table}: " . implode(', ', $missing));
        }

        return $types;
    }
}
