<?php

namespace App\Services\SyncMsi;

use App\Services\SyncWrs\SyncConnectionManager;
use Cron\CronExpression;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Menjalankan Sync MSI (Customer CSI) dan mencatat hasilnya ke tr_sync_msi_log(+_detail).
 *
 *  - Hanya satu proses pada satu waktu (Cache lock "sync-msi"); jadwal yang bentrok dicatat SKIPPED.
 *  - "Jalankan Manual" dari menu disimpan sebagai log QUEUED (beserta rentang tanggal), lalu dieksekusi scheduler
 *    (sync-msi:run --queued tiap menit). Web request tidak pernah menjalankan sync langsung.
 *  - Log RUNNING yang tertinggal (container restart / proses mati) otomatis ditandai FAILED.
 *
 * Aturan commit (mengikuti script lama):
 *  - Setiap baris ke MSI ditulis dalam transaksinya sendiri; error → baris itu di-rollback, baris lain jalan terus.
 *  - tblkpi.uploaded_to_msi & tblsetting.last_update_csi HANYA diubah jika ada baris yang diproses dan SEMUA sukses
 *    (tidak ada yang dilewati / gagal). Kalau tidak, keduanya tidak disentuh sehingga rentang yang sama dikirim ulang.
 *  - last_update_csi hanya dimajukan untuk rentang default (last_update_csi s/d hari ini), bukan rentang manual pilihan.
 */
class SyncMsiRunner
{
    public const LOG    = 'tr_sync_msi_log';
    public const DETAIL = 'tr_sync_msi_log_detail';

    public const QUEUED  = 'QUEUED';
    public const RUNNING = 'RUNNING';
    public const SUCCESS = 'SUCCESS';
    public const PARTIAL = 'PARTIAL';
    public const FAILED  = 'FAILED';
    public const SKIPPED = 'SKIPPED';

    private const LOCK = 'sync-msi';

    /** Berhenti jika sebanyak ini baris berturut-turut gagal (mis. koneksi MSI putus di tengah jalan). */
    private const MAX_CONSECUTIVE_FAILS = 50;

    public function __construct(private SyncConnectionManager $connections, private CsiCustomerSource $source)
    {
    }

    public function now(): Carbon
    {
        return now(config('sync_msi.timezone', 'Asia/Jakarta'));
    }

    public function setting(): ?object
    {
        return DB::table('tblsetting')->first(['filter_csi', 'last_update_csi']);
    }

    /**
     * Rentang default: tanggal last_update_csi s/d hari ini (last_update_csi kosong → hari ini).
     *
     * @return array{0: Carbon, 1: Carbon, 2: ?Carbon} [from, to, last_update_csi]
     */
    public function defaultRange(): array
    {
        $today      = $this->now()->startOfDay();
        $setting    = $this->setting();
        $lastUpdate = $setting?->last_update_csi ? Carbon::parse($setting->last_update_csi, $today->getTimezone()) : null;

        return [$lastUpdate ? $lastUpdate->copy()->startOfDay() : $today->copy(), $today, $lastUpdate];
    }

    public function nextRunAt(): ?Carbon
    {
        try {
            $tz = config('sync_msi.timezone', 'Asia/Jakarta');

            return Carbon::instance((new CronExpression(config('sync_msi.cron')))->getNextRunDate(now($tz), 0, false, $tz));
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Validasi rentang manual. null = pakai default.
     *
     * @return array{0: Carbon, 1: Carbon, 2: bool} [from, to, is_default_range]
     */
    public function resolveRange(?string $from, ?string $to): array
    {
        [$defFrom, $defTo] = $this->defaultRange();

        $f = $from ? $this->parseDate($from, 'Tanggal mulai') : $defFrom->copy();
        $t = $to ? $this->parseDate($to, 'Tanggal akhir') : $defTo->copy();

        if ($f->gt($t)) {
            throw new RuntimeException('Tanggal mulai tidak boleh lebih besar dari tanggal akhir.');
        }
        if ($t->gt($defTo)) {
            throw new RuntimeException('Tanggal akhir tidak boleh melebihi hari ini.');
        }
        $max = (int) config('sync_msi.max_manual_days', 366);
        if ($f->diffInDays($t) + 1 > $max) {
            throw new RuntimeException("Rentang tanggal maksimal {$max} hari.");
        }

        return [$f, $t, $f->equalTo($defFrom) && $t->equalTo($defTo)];
    }

    /** Antrekan eksekusi manual (dari menu). */
    public function requestManual(string $by, ?string $from, ?string $to): int
    {
        [$f, $t, $isDefault] = $this->resolveRange($from, $to);

        return DB::transaction(function () use ($by, $f, $t, $isDefault) {
            $pending = DB::table(self::LOG)->whereIn('status', [self::QUEUED, self::RUNNING])->lockForUpdate()->first();
            if ($pending) {
                throw new RuntimeException($pending->status === self::RUNNING
                    ? 'Sync MSI sedang berjalan. Tunggu sampai selesai.'
                    : 'Sync MSI sudah ada di antrean dan akan dijalankan scheduler dalam ±1 menit.');
            }

            return $this->insertLog('manual', $by, self::QUEUED, [
                'queued_at'        => now(),
                'date_from'        => $f->toDateString(),
                'date_to'          => $t->toDateString(),
                'is_default_range' => $isDefault,
            ]);
        });
    }

    /** Jalankan semua permintaan manual yang masih QUEUED. */
    public function processQueue(?callable $output = null): int
    {
        $count = 0;

        foreach (DB::table(self::LOG)->where('status', self::QUEUED)->orderBy('id')->get() as $log) {
            $output && $output("Menjalankan antrean #{$log->id} ({$log->date_from} s/d {$log->date_to})");
            if ($this->run('manual', $log->triggered_by, $log->id, $output, false, $log->date_from, $log->date_to) !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return int|null id log; null jika permintaan antrean belum bisa jalan karena sync masih berjalan
     */
    public function run(string $trigger = 'schedule', ?string $by = null, ?int $logId = null, ?callable $output = null,
        bool $dryRun = false, ?string $from = null, ?string $to = null): ?int
    {
        $lock = Cache::lock(self::LOCK, (int) config('sync_msi.lock_seconds', 14400));

        if (!$lock->get() && !$this->releaseStaleLock($lock)) {
            if ($logId) {
                return null; // tetap QUEUED, dicoba lagi menit berikutnya
            }
            $id = $this->insertLog($trigger, $by, self::SKIPPED);
            $this->finishLog($id, self::SKIPPED, 'Dilewati: Sync MSI sebelumnya masih berjalan.');

            return $id;
        }

        $connection = null;

        try {
            $this->markStaleRuns();

            try {
                [$f, $t, $isDefault] = $this->resolveRange($from, $to);
            } catch (RuntimeException $e) {
                $logId ??= $this->insertLog($trigger, $by, self::RUNNING, ['started_at' => now()]);
                $this->finishLog($logId, self::FAILED, $e->getMessage());

                return $logId;
            }

            $setting = $this->setting();
            $start   = [
                'status'             => self::RUNNING,
                'started_at'         => now(),
                'date_from'          => $f->toDateString(),
                'date_to'            => $t->toDateString(),
                'is_default_range'   => $isDefault,
                'filter_csi'         => $setting?->filter_csi,
                'last_update_before' => $setting?->last_update_csi,
                'connection_name'    => config('sync_msi.connection'),
                'host'               => gethostname() ?: null,
                'date_updated'       => now(),
            ];

            if ($logId) {
                DB::table(self::LOG)->where('id', $logId)->update($start);
            } else {
                $logId = $this->insertLog($trigger, $by, self::RUNNING, $start);
            }

            if (!$setting) {
                $this->finishLog($logId, self::FAILED, 'tblsetting masih kosong. Jalankan: php artisan db:seed --class=TblSettingSeeder');

                return $logId;
            }

            $output && $output(sprintf('Rentang log_action_date: %s 00:00:00 s/d %s 23:59:59%s',
                $f->toDateString(), $t->toDateString(), $isDefault ? ' (default)' : ' (manual)'));

            try {
                $connection = $this->connections->connect((string) config('sync_msi.connection'));
                $writer     = new MsiMemberWriter($connection, $dryRun);
            } catch (Throwable $e) {
                $this->finishLog($logId, self::FAILED, 'Koneksi MSI gagal: ' . Str::limit($e->getMessage(), 1000));
                Log::error('[sync-msi] koneksi gagal: ' . $e->getMessage());

                return $logId;
            }

            return $this->process($logId, $writer, $f, $t, $isDefault, $setting, $output, $dryRun);
        } catch (Throwable $e) {
            if ($logId) {
                $this->finishLog($logId, self::FAILED, Str::limit($e->getMessage(), 1000));
            }
            Log::error('[sync-msi] ' . $e->getMessage(), ['exception' => $e]);

            return $logId;
        } finally {
            if ($connection) {
                $this->connections->disconnect($connection);
            }
            $lock->release();
        }
    }

    private function process(int $logId, MsiMemberWriter $writer, Carbon $from, Carbon $to, bool $isDefault, object $setting,
        ?callable $output, bool $dryRun): int
    {
        $now    = $this->now();
        $wpFrom = $from->copy()->subMonthNoOverflow(); // script lama: last_update_csi - interval '1 month'
        $stats  = ['read' => 0, 'inserted' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
        $kpi    = [];   // [kd_customer|Y-m-d => [kd_customer, Y-m-d]] untuk uploaded_to_msi
        $fails  = 0;
        $logSuccess = (bool) config('sync_msi.log_success_rows', false);
        $aborted    = null;

        foreach ($this->source->rows($from, $to, $wpFrom, $setting->filter_csi) as $row) {
            $stats['read']++;
            $r = $this->source->transform($row, $now);

            [$dealerId, $userId, $reason] = $writer->resolve($r['fk_dealer'], $r['log_id']);

            if ($reason !== null) {
                $stats['skipped']++;
                $this->detail($logId, $stats['read'], $r, self::SKIPPED, null, $reason);
                $output && $output("  ~ #{$stats['read']} {$r['vin']} dilewati: {$reason}");
            } else {
                try {
                    $res = $writer->upsert($r, $dealerId, $userId, $now);
                    $stats[$res['action'] === 'INSERT' ? 'inserted' : 'updated']++;
                    $fails = 0;

                    if ($r['handover']) {
                        $k = $r['kd_customer'] . '|' . $r['handover']->toDateString();
                        $kpi[$k] = [$r['kd_customer'], $r['handover']->toDateString()];
                    }

                    $note = (!$dryRun && $res['action'] === 'UPDATE' && $res['affected'] === 0)
                        ? 'UPDATE tidak mengubah baris (member yang ada bukan type CSI / beda dealer).'
                        : null;
                    if ($logSuccess || $note) {
                        $this->detail($logId, $stats['read'], $r, self::SUCCESS, $res['action'], $note);
                    }
                } catch (Throwable $e) {
                    $stats['failed']++;
                    $fails++;
                    $msg = Str::limit($e->getMessage(), 2000);
                    $this->detail($logId, $stats['read'], $r, self::FAILED, null, $msg);
                    $output && $output("  ! #{$stats['read']} {$r['vin']} gagal: " . Str::limit($e->getMessage(), 200));

                    if ($fails >= self::MAX_CONSECUTIVE_FAILS) {
                        $aborted = 'Dihentikan: ' . self::MAX_CONSECUTIVE_FAILS . ' baris berturut-turut gagal. Error terakhir: '
                            . Str::limit($e->getMessage(), 500);
                        break;
                    }
                }
            }

            if ($stats['read'] % 100 === 0) {
                $this->heartbeat($logId, $stats);
                $output && $output("  ... {$stats['read']} baris");
            }
        }

        // ---------- Commit sisi WRS Connect (perilaku lama: hanya jika semua sukses) ----------
        $allOk       = $aborted === null && $stats['read'] > 0 && $stats['skipped'] === 0 && $stats['failed'] === 0;
        $flagged     = 0;
        $lastUpdated = null;

        if ($allOk && !$dryRun) {
            [$flagged, $lastUpdated] = $this->commitLocal($kpi, $isDefault, $now);
        }

        $status = match (true) {
            $aborted !== null                                      => self::FAILED,
            $stats['skipped'] === 0 && $stats['failed'] === 0      => self::SUCCESS,
            $stats['inserted'] + $stats['updated'] === 0           => self::FAILED,
            default                                                => self::PARTIAL,
        };

        $msg = $aborted ?? match (true) {
            $stats['read'] === 0 => 'Tidak ada data customer di rentang ini. last_update_csi tidak diubah.',
            $allOk               => "Selesai tanpa error. uploaded_to_msi di-set untuk {$flagged} baris tblkpi"
                                    . ($lastUpdated ? ', last_update_csi → ' . $lastUpdated->toDateString() . '.' : ' (rentang manual: last_update_csi tidak diubah).'),
            default              => "{$stats['skipped']} baris dilewati & {$stats['failed']} baris gagal (lihat detail). "
                                    . 'Sesuai aturan lama, uploaded_to_msi & last_update_csi TIDAK diubah; rentang yang sama akan dikirim ulang.',
        };
        if ($dryRun) {
            $msg = '[DRY RUN - tidak ada data ditulis ke MSI / WRS Connect] ' . $msg;
        }

        $this->finishLog($logId, $status, $msg, $stats + ['kpi_flagged' => $flagged], $lastUpdated);
        $output && $output(sprintf('read=%d insert=%d update=%d skip=%d fail=%d kpi=%d',
            $stats['read'], $stats['inserted'], $stats['updated'], $stats['skipped'], $stats['failed'], $flagged));

        return $logId;
    }

    /** @return array{0: int, 1: ?Carbon} */
    private function commitLocal(array $kpi, bool $isDefault, Carbon $now): array
    {
        return DB::transaction(function () use ($kpi, $isDefault, $now) {
            $flagged = 0;

            foreach (array_chunk(array_values($kpi), 200) as $chunk) {
                $flagged += DB::table('tblkpi')
                    ->where(function ($q) use ($chunk) {
                        foreach ($chunk as [$customer, $date]) {
                            $q->orWhere(fn ($w) => $w->where('fk_customer', $customer)->where('tanggal_faktur', $date . ' 00:00:00'));
                        }
                    })
                    ->update(['uploaded_to_msi' => 1]);
            }

            $lastUpdated = null;
            if ($isDefault) {
                $lastUpdated = $now->copy()->startOfDay();
                // Script lama: update tblsetting set last_update_csi = hari ini 00:00:00 (semua baris)
                DB::table('tblsetting')->update(['last_update_csi' => $lastUpdated->format('Y-m-d H:i:s')]);
            }

            return [$flagged, $lastUpdated];
        });
    }

    private function detail(int $logId, int $rowNo, array $r, string $status, ?string $action, ?string $message): void
    {
        DB::table(self::DETAIL)->insert([
            'log_id'             => $logId,
            'row_no'             => $rowNo,
            'kd_customer'        => Str::limit($r['kd_customer'], 255, ''),
            'no_vin'             => Str::limit($r['vin'], 50, ''),
            'fk_dealer'          => Str::limit($r['fk_dealer'], 20, ''),
            'log_action_userid'  => Str::limit($r['log_id'], 28, ''),
            'log_action_date'    => $r['log_date'],
            'handover_date'      => $r['handover']?->toDateString(),
            'reason_for_service' => $r['reason'] ?: null,
            'action'             => $action,
            'status'             => $status,
            'message'            => $message,
            'date_created'       => now(),
        ]);
    }

    private function heartbeat(int $logId, array $s): void
    {
        DB::table(self::LOG)->where('id', $logId)->update([
            'total_read' => $s['read'], 'total_inserted' => $s['inserted'], 'total_updated' => $s['updated'],
            'total_skipped' => $s['skipped'], 'total_failed' => $s['failed'], 'date_updated' => now(),
        ]);
    }

    private function insertLog(string $trigger, ?string $by, string $status, array $extra = []): int
    {
        return DB::table(self::LOG)->insertGetId($extra + [
            'trigger_type'    => $trigger,
            'triggered_by'    => $by,
            'connection_name' => config('sync_msi.connection'),
            'status'          => $status,
            'host'            => gethostname() ?: null,
            'date_created'    => now(),
        ]);
    }

    private function finishLog(int $logId, string $status, ?string $message, array $totals = [], ?Carbon $lastUpdated = null): void
    {
        $log = DB::table(self::LOG)->where('id', $logId)->first(['started_at']);
        $now = now();

        DB::table(self::LOG)->where('id', $logId)->update([
            'status'            => $status,
            'finished_at'       => $now,
            'duration_sec'      => $log && $log->started_at ? (int) abs($now->diffInSeconds(Carbon::parse($log->started_at))) : null,
            'message'           => $message,
            'total_read'        => $totals['read'] ?? 0,
            'total_inserted'    => $totals['inserted'] ?? 0,
            'total_updated'     => $totals['updated'] ?? 0,
            'total_skipped'     => $totals['skipped'] ?? 0,
            'total_failed'      => $totals['failed'] ?? 0,
            'kpi_flagged'       => $totals['kpi_flagged'] ?? 0,
            'last_update_after' => $lastUpdated?->format('Y-m-d H:i:s'),
            'date_updated'      => $now,
        ]);
    }

    /**
     * Lock masih dipegang — apakah prosesnya masih hidup? Proses yang jalan meng-update date_updated log
     * setiap 100 baris. RUNNING tanpa progres > stale_minutes dianggap mati → lock dilepas paksa.
     */
    private function releaseStaleLock($lock): bool
    {
        $running = DB::table(self::LOG)->where('status', self::RUNNING)->orderByDesc('id')->first();
        if (!$running) {
            return false;
        }

        $heartbeat = Carbon::parse($running->date_updated ?? $running->started_at ?? $running->date_created);
        if ($heartbeat->gt(now()->subMinutes((int) config('sync_msi.stale_minutes', 30)))) {
            return false;
        }

        Log::warning("[sync-msi] lock dilepas paksa: log #{$running->id} tidak ada progres sejak {$heartbeat}.");
        $this->forceUnlock();

        return $lock->get();
    }

    /** Lepas lock & tandai log RUNNING sebagai FAILED (php artisan sync-msi:run --unlock). */
    public function forceUnlock(): void
    {
        Cache::lock(self::LOCK)->forceRelease();
        $this->markStaleRuns();
    }

    /** Lock sudah kita pegang → log RUNNING lain pasti sisa proses yang mati. */
    private function markStaleRuns(): void
    {
        DB::table(self::LOG)->where('status', self::RUNNING)->update([
            'status' => self::FAILED, 'finished_at' => now(),
            'message' => 'Proses terhenti sebelum selesai (container restart / proses dimatikan).', 'date_updated' => now(),
        ]);
    }

    private function parseDate(string $value, string $label): Carbon
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            throw new RuntimeException("{$label} harus berformat YYYY-MM-DD.");
        }

        try {
            $d = Carbon::createFromFormat('!Y-m-d', $value, config('sync_msi.timezone', 'Asia/Jakarta'));
        } catch (Throwable) {
            throw new RuntimeException("{$label} tidak valid.");
        }
        if (!$d || $d->format('Y-m-d') !== $value) {
            throw new RuntimeException("{$label} tidak valid.");
        }

        return $d;
    }
}
