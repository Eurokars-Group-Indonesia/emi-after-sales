<?php

namespace App\Console\Commands;

use App\Services\SyncMsi\SyncMsiRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Sync WRS Connect After Sales → MSI (Customer CSI). Pengganti upload_customer_csi.php.
 *
 *   php artisan sync-msi:run                                   # rentang default: tblsetting.last_update_csi s/d hari ini
 *   php artisan sync-msi:run --from=2026-08-01 --to=2026-08-31 # rentang manual (last_update_csi tidak diubah)
 *   php artisan sync-msi:run --dry-run                         # hitung insert/update/skip tanpa menulis ke MSI / WRS Connect
 *   php artisan sync-msi:run --unlock                          # lepas lock jika proses mati di tengah jalan
 *   php artisan sync-msi:run --queued                          # eksekusi permintaan dari menu (dipanggil scheduler tiap menit)
 */
class SyncMsiRun extends Command
{
    protected $signature = 'sync-msi:run
                            {--from= : Tanggal mulai log_action_date (YYYY-MM-DD), default tblsetting.last_update_csi}
                            {--to= : Tanggal akhir log_action_date (YYYY-MM-DD), default hari ini}
                            {--queued : Jalankan permintaan manual yang menunggu di antrean}
                            {--trigger=console : schedule | console}
                            {--dry-run : Hanya baca & hitung, tanpa menulis data}
                            {--unlock : Lepas lock yang macet & tandai log RUNNING sebagai FAILED}';

    protected $description = 'Sync customer CSI dari WRS Connect After Sales ke database MSI (tanpa hapus data)';

    public function handle(SyncMsiRunner $runner): int
    {
        @set_time_limit(0);
        DB::connection()->disableQueryLog();

        $echo = fn (string $line) => $this->line($line);

        if ($this->option('unlock')) {
            $runner->forceUnlock();
            $this->info('Lock Sync MSI dilepas.');

            return self::SUCCESS;
        }

        if ($this->option('queued')) {
            $n = $runner->processQueue($echo);
            if ($n > 0) {
                $this->info("{$n} antrean Sync MSI diproses.");
            }

            return self::SUCCESS;
        }

        $dryRun  = (bool) $this->option('dry-run');
        $trigger = $dryRun ? 'dry-run' : (in_array($this->option('trigger'), ['schedule', 'console'], true) ? $this->option('trigger') : 'console');
        $this->info('Sync MSI dimulai' . ($dryRun ? ' (DRY RUN, tidak menulis data)' : '') . ' ...');

        $logId = $runner->run(
            $trigger,
            $trigger === 'schedule' ? 'SCHEDULER' : (get_current_user() ?: 'console'),
            null,
            $echo,
            $dryRun,
            $this->option('from') ?: null,
            $this->option('to') ?: null,
        );
        $log = DB::table(SyncMsiRunner::LOG)->where('id', $logId)->first();

        $this->info("Sync MSI selesai: {$log->status} (log #{$logId}). {$log->message}");

        return in_array($log->status, [SyncMsiRunner::SUCCESS, SyncMsiRunner::SKIPPED], true) ? self::SUCCESS : self::FAILURE;
    }
}
