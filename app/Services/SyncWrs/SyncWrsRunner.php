<?php

namespace App\Services\SyncWrs;

use Cron\CronExpression;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Menjalankan job Sync WRS Sales (inventory / faktur) dan mencatat hasilnya ke tr_sync_wrs_log(+_detail).
 *
 *  - Satu job hanya boleh jalan satu proses (Cache lock "sync-wrs:{job}"), jadwal yang bentrok dicatat SKIPPED.
 *  - Permintaan "Jalankan Sekarang" dari menu disimpan sebagai log QUEUED, lalu dieksekusi oleh scheduler
 *    (sync-wrs:run --queued tiap menit) — web request tidak pernah menjalankan sync langsung.
 *  - Log RUNNING yang tertinggal (container restart / proses mati) otomatis ditandai FAILED saat job berikutnya mulai.
 */
class SyncWrsRunner
{
    public const LOG    = 'tr_sync_wrs_log';
    public const DETAIL = 'tr_sync_wrs_log_detail';

    public const QUEUED  = 'QUEUED';
    public const RUNNING = 'RUNNING';
    public const SUCCESS = 'SUCCESS';
    public const PARTIAL = 'PARTIAL';
    public const FAILED  = 'FAILED';
    public const SKIPPED = 'SKIPPED';

    public function __construct(private SyncConnectionManager $connections)
    {
    }

    public function jobs(): array
    {
        return config('sync_wrs.jobs', []);
    }

    public function job(string $code): array
    {
        $job = $this->jobs()[$code] ?? null;
        if (!$job) {
            throw new RuntimeException("Job sync '{$code}' tidak dikenal.");
        }

        return $job;
    }

    public function nextRunAt(string $code): ?Carbon
    {
        try {
            $tz = config('sync_wrs.timezone');

            return Carbon::instance((new CronExpression($this->job($code)['cron']))->getNextRunDate(now($tz), 0, false, $tz));
        } catch (Throwable) {
            return null;
        }
    }

    /** Antrekan eksekusi manual. */
    public function requestManual(string $code, string $by): int
    {
        $this->job($code);

        return DB::transaction(function () use ($code, $by) {
            $pending = DB::table(self::LOG)->where('job_code', $code)
                ->whereIn('status', [self::QUEUED, self::RUNNING])
                ->lockForUpdate()
                ->first();
            if ($pending) {
                throw new RuntimeException($pending->status === self::RUNNING
                    ? 'Sync ini sedang berjalan. Tunggu sampai selesai.'
                    : 'Sync ini sudah ada di antrean dan akan dijalankan scheduler dalam ±1 menit.');
            }

            return $this->insertLog($code, 'manual', $by, self::QUEUED, ['queued_at' => now()]);
        });
    }

    /** Jalankan semua permintaan manual yang masih QUEUED. */
    public function processQueue(?callable $output = null): int
    {
        $count = 0;
        $queued = DB::table(self::LOG)->where('status', self::QUEUED)->orderBy('id')->get();

        foreach ($queued as $log) {
            if (!isset($this->jobs()[$log->job_code])) {
                $this->finishLog($log->id, self::FAILED, "Job '{$log->job_code}' tidak dikenal.");
                continue;
            }
            $output && $output("Menjalankan antrean #{$log->id} ({$log->job_code})");
            if ($this->run($log->job_code, 'manual', $log->triggered_by, $log->id, $output) !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return int|null id log; null jika permintaan antrean belum bisa jalan karena job masih berjalan
     */
    public function run(string $code, string $trigger = 'schedule', ?string $by = null, ?int $logId = null, ?callable $output = null, bool $dryRun = false): ?int
    {
        $job  = $this->job($code);
        $lock = Cache::lock("sync-wrs:{$code}", (int) config('sync_wrs.lock_seconds', 14400));

        if (!$lock->get() && !$this->releaseStaleLock($code, $lock)) {
            if ($logId) {
                return null; // tetap QUEUED, dicoba lagi menit berikutnya
            }
            $id = $this->insertLog($code, $trigger, $by, self::SKIPPED, ['message' => 'Dilewati: job yang sama masih berjalan.']);
            $this->finishLog($id, self::SKIPPED, 'Dilewati: job yang sama masih berjalan.');

            return $id;
        }

        $connection = null;

        try {
            $this->markStaleRuns($code);

            $startedAt = now();
            if ($logId) {
                DB::table(self::LOG)->where('id', $logId)->update([
                    'status' => self::RUNNING, 'started_at' => $startedAt, 'connection_name' => $job['connection'],
                    'host' => gethostname() ?: null, 'date_updated' => now(),
                ]);
            } else {
                $logId = $this->insertLog($code, $trigger, $by, self::RUNNING, ['started_at' => $startedAt]);
            }

            try {
                $connection = $this->connections->connect($job['connection']);
            } catch (Throwable $e) {
                $this->finishLog($logId, self::FAILED, Str::limit($e->getMessage(), 1000));
                Log::error("[sync-wrs:{$code}] koneksi gagal: " . $e->getMessage());

                return $logId;
            }

            $totals   = ['read' => 0, 'inserted' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'failed' => 0];
            $statuses = [];

            foreach (array_values($job['steps']) as $i => $stepCode) {
                $step = config("sync_wrs.steps.{$stepCode}");
                $output && $output("  - {$stepCode} ...");

                [$status, $stats] = $this->runStep($logId, $i + 1, $stepCode, $step, $connection, $dryRun);
                $statuses[] = $status;
                foreach ($totals as $k => $v) {
                    $totals[$k] = $v + ($stats[$k] ?? 0);
                }
                $output && $output(sprintf('    %s read=%d insert=%d update=%d same=%d skip=%d fail=%d',
                    $status, $stats['read'] ?? 0, $stats['inserted'] ?? 0, $stats['updated'] ?? 0,
                    $stats['unchanged'] ?? 0, $stats['skipped'] ?? 0, $stats['failed'] ?? 0));
                if ($output && $dryRun) {
                    foreach ($stats['diffs'] ?? [] as $d) {
                        $output('      ~ ' . $d['key'] . ': ' . json_encode($d['changes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    }
                }
                if ($output) {
                    foreach ($stats['errors'] ?? [] as $e) {
                        $output("      ! {$e['key']} ({$e['action']}): {$e['error']}");
                    }
                }
            }

            $final = match (true) {
                !array_diff($statuses, [self::SUCCESS])  => self::SUCCESS,
                !array_diff($statuses, [self::FAILED])   => self::FAILED,
                default                                  => self::PARTIAL,
            };

            $failedSteps = count(array_filter($statuses, fn ($s) => $s !== self::SUCCESS));
            $message = $final === self::SUCCESS
                ? 'Selesai tanpa error.'
                : "{$failedSteps} dari " . count($statuses) . ' tabel bermasalah. Lihat detail.';

            $this->finishLog($logId, $final, ($dryRun ? '[DRY RUN - tidak ada data ditulis] ' : '') . $message, $totals);

            return $logId;
        } catch (Throwable $e) {
            if ($logId) {
                $this->finishLog($logId, self::FAILED, Str::limit($e->getMessage(), 1000));
            }
            Log::error("[sync-wrs:{$code}] " . $e->getMessage(), ['exception' => $e]);

            return $logId;
        } finally {
            if ($connection) {
                $this->connections->disconnect($connection);
            }
            $lock->release();
        }
    }

    /** @return array{0: string, 1: array} */
    private function runStep(int $logId, int $order, string $code, ?array $step, string $connection, bool $dryRun = false): array
    {
        $now      = now();
        $detailId = DB::table(self::DETAIL)->insertGetId([
            'log_id' => $logId, 'step_order' => $order, 'step_code' => $code,
            'legacy_script' => $step['legacy'] ?? null,
            'source_table'  => $step['source'] ?? '-', 'target_table' => $step['target'] ?? '-',
            'status' => self::RUNNING, 'started_at' => $now, 'date_created' => $now,
        ]);

        if (!$step) {
            $this->finishStep($detailId, $now, self::FAILED, "Definisi step '{$code}' tidak ada di config/sync_wrs.php.", []);

            return [self::FAILED, []];
        }

        try {
            $syncer = new TableSyncer(
                $connection,
                $step,
                (int) config('sync_wrs.chunk', 1000),
                (int) config('sync_wrs.max_error_samples', 20),
                (string) config('sync_wrs.timezone', 'Asia/Jakarta'),
                $dryRun,
            );

            $stats = $syncer->run(function (array $s) use ($detailId, $logId) {
                DB::table(self::DETAIL)->where('id', $detailId)->update($this->countColumns($s) + ['date_updated' => now()]);
                DB::table(self::LOG)->where('id', $logId)->update(['date_updated' => now()]); // heartbeat
            });

            $status = $stats['failed'] > 0 ? self::PARTIAL : self::SUCCESS;
            $this->finishStep($detailId, $now, $status, $this->warningMessage($stats), $stats);

            return [$status, $stats];
        } catch (Throwable $e) {
            Log::error("[sync-wrs] step {$code} gagal: " . $e->getMessage());
            $this->finishStep($detailId, $now, self::FAILED, Str::limit($e->getMessage(), 2000), []);

            return [self::FAILED, []];
        }
    }

    private function finishStep(int $detailId, $startedAt, string $status, ?string $message, array $stats): void
    {
        DB::table(self::DETAIL)->where('id', $detailId)->update(($stats ? $this->countColumns($stats) : []) + [
            'status'        => $status,
            'finished_at'   => now(),
            'duration_sec'  => (int) abs(now()->diffInSeconds($startedAt)),
            'message'       => $message,
            'error_samples' => !empty($stats['errors']) ? json_encode($stats['errors'], JSON_UNESCAPED_UNICODE) : null,
            'date_updated'  => now(),
        ]);
    }

    private function countColumns(array $s): array
    {
        return [
            'rows_read' => $s['read'], 'rows_inserted' => $s['inserted'], 'rows_updated' => $s['updated'],
            'rows_unchanged' => $s['unchanged'], 'rows_skipped' => $s['skipped'], 'rows_failed' => $s['failed'],
        ];
    }

    private function warningMessage(array $stats): ?string
    {
        $parts = [];
        if ($stats['failed'] > 0) {
            $parts[] = "{$stats['failed']} baris gagal ditulis (lihat contoh error).";
        }
        foreach ($stats['warnings'] as $what => $w) {
            $parts[] = "Peringatan {$what}: {$w['count']} nilai" . (($w['nulled'] ?? true) ? ' dikosongkan (NULL)' : '')
                . ', mis. key ' . implode(', ', $w['examples']) . '.';
        }

        return $parts ? implode(' ', $parts) : null;
    }

    private function insertLog(string $code, string $trigger, ?string $by, string $status, array $extra = []): int
    {
        return DB::table(self::LOG)->insertGetId($extra + [
            'job_code'        => $code,
            'trigger_type'    => $trigger,
            'triggered_by'    => $by,
            'connection_name' => $this->job($code)['connection'],
            'status'          => $status,
            'host'            => gethostname() ?: null,
            'date_created'    => now(),
        ]);
    }

    private function finishLog(int $logId, string $status, ?string $message, array $totals = []): void
    {
        $log = DB::table(self::LOG)->where('id', $logId)->first(['started_at']);
        $now = now();

        DB::table(self::LOG)->where('id', $logId)->update([
            'status'          => $status,
            'finished_at'     => $now,
            'duration_sec'    => $log && $log->started_at ? (int) abs($now->diffInSeconds(Carbon::parse($log->started_at))) : null,
            'message'         => $message,
            'total_read'      => $totals['read'] ?? 0,
            'total_inserted'  => $totals['inserted'] ?? 0,
            'total_updated'   => $totals['updated'] ?? 0,
            'total_unchanged' => $totals['unchanged'] ?? 0,
            'total_skipped'   => $totals['skipped'] ?? 0,
            'total_failed'    => $totals['failed'] ?? 0,
            'date_updated'    => $now,
        ]);
    }

    /**
     * Lock masih dipegang, tapi apakah prosesnya masih hidup? Proses yang jalan meng-update date_updated log
     * (heartbeat) setiap blok. Jika log RUNNING tidak ada progres > stale_minutes, proses dianggap mati
     * (container restart / kill) → lock dilepas paksa supaya jadwal berikutnya tidak tertahan sampai lock kedaluwarsa.
     */
    private function releaseStaleLock(string $code, $lock): bool
    {
        $running = DB::table(self::LOG)->where('job_code', $code)->where('status', self::RUNNING)->orderByDesc('id')->first();
        if (!$running) {
            return false; // proses lain baru saja mengambil lock dan belum menulis log
        }

        $heartbeat = Carbon::parse($running->date_updated ?? $running->started_at ?? $running->date_created);
        if ($heartbeat->gt(now()->subMinutes((int) config('sync_wrs.stale_minutes', 30)))) {
            return false;
        }

        Log::warning("[sync-wrs:{$code}] lock dilepas paksa: log #{$running->id} tidak ada progres sejak {$heartbeat}.");
        $this->forceUnlock($code);

        return $lock->get();
    }

    /** Lepas lock & tandai log RUNNING sebagai FAILED (dipakai php artisan sync-wrs:run {job} --unlock). */
    public function forceUnlock(string $code): void
    {
        $this->job($code);
        Cache::lock("sync-wrs:{$code}")->forceRelease();
        $this->markStaleRuns($code);
    }

    /** Lock sudah kita pegang → log RUNNING lain untuk job ini pasti sisa proses yang mati. */
    private function markStaleRuns(string $code): void
    {
        $stale = DB::table(self::LOG)->where('job_code', $code)->where('status', self::RUNNING)->pluck('id');
        if ($stale->isEmpty()) {
            return;
        }

        $msg = 'Proses terhenti sebelum selesai (container restart / proses dimatikan).';
        DB::table(self::LOG)->whereIn('id', $stale)->update([
            'status' => self::FAILED, 'finished_at' => now(), 'message' => $msg, 'date_updated' => now(),
        ]);
        DB::table(self::DETAIL)->whereIn('log_id', $stale)->where('status', self::RUNNING)->update([
            'status' => self::FAILED, 'finished_at' => now(), 'message' => $msg, 'date_updated' => now(),
        ]);
    }
}
