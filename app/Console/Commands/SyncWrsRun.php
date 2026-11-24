<?php

namespace App\Console\Commands;

use App\Services\SyncWrs\SyncWrsRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Sync WRS Sales → WRS Connect After Sales.
 *
 *   php artisan sync-wrs:run inventory   # color, model, model_color, samsat, type, spk_retail, inventory
 *   php artisan sync-wrs:run faktur      # tblfaktur
 *   php artisan sync-wrs:run faktur --dry-run   # cek tanpa menulis data
 *   php artisan sync-wrs:run inventory --unlock # lepas lock jika proses mati di tengah jalan
 *   php artisan sync-wrs:run --queued    # eksekusi permintaan "Jalankan Sekarang" dari menu (dipanggil scheduler tiap menit)
 */
class SyncWrsRun extends Command
{
    protected $signature = 'sync-wrs:run
                            {job? : inventory | faktur}
                            {--queued : Jalankan permintaan manual yang menunggu di antrean}
                            {--trigger=console : schedule | console}
                            {--dry-run : Hanya hitung insert/update & tampilkan contoh perubahan, tanpa menulis data}
                            {--unlock : Lepas lock job yang macet (proses mati) & tandai log RUNNING sebagai FAILED}';

    protected $description = 'Sync data WRS Sales ke WRS Connect After Sales (tanpa hapus data)';

    public function handle(SyncWrsRunner $runner): int
    {
        @set_time_limit(0);
        DB::connection()->disableQueryLog();

        $echo = fn (string $line) => $this->line($line);

        if ($this->option('queued')) {
            $n = $runner->processQueue($echo);
            if ($n > 0) {
                $this->info("{$n} antrean sync diproses.");
            }

            return self::SUCCESS;
        }

        $job = (string) $this->argument('job');
        if (!isset($runner->jobs()[$job])) {
            $this->error('Job harus salah satu dari: ' . implode(', ', array_keys($runner->jobs())));

            return self::INVALID;
        }

        if ($this->option('unlock')) {
            $runner->forceUnlock($job);
            $this->info("Lock sync {$job} dilepas.");

            return self::SUCCESS;
        }

        $dryRun  = (bool) $this->option('dry-run');
        $trigger = $dryRun ? 'dry-run' : (in_array($this->option('trigger'), ['schedule', 'console'], true) ? $this->option('trigger') : 'console');
        $this->info("Sync {$job} dimulai" . ($dryRun ? ' (DRY RUN, tidak menulis data)' : '') . ' ...');

        $logId = $runner->run($job, $trigger, $trigger === 'schedule' ? 'SCHEDULER' : (get_current_user() ?: 'console'), null, $echo, $dryRun);
        $log   = DB::table(SyncWrsRunner::LOG)->where('id', $logId)->first();

        $this->info("Sync {$job} selesai: {$log->status} (log #{$logId}). {$log->message}");

        return in_array($log->status, [SyncWrsRunner::SUCCESS, SyncWrsRunner::SKIPPED], true) ? self::SUCCESS : self::FAILURE;
    }
}
