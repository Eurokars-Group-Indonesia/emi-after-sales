<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Email Scheduler Delivery CKD - setiap hari jam 4 sore
Schedule::command('email-scheduler:delivery-ckd')->dailyAt('16:00')->timezone('Asia/Jakarta');

// Sync WRS Sales → WRS Connect After Sales (jadwal di config/sync_wrs.php / .env SYNC_WRS_*_CRON)
//  - inventory : setiap hari 03:00 WIB
//  - faktur    : 07:00 WIB lalu setiap jam s/d 23:00
// Job sendiri memakai Cache lock, jadwal yang bentrok dicatat SKIPPED di monitoring.
foreach (config('sync_wrs.jobs', []) as $syncJob => $syncCfg) {
    Schedule::command("sync-wrs:run {$syncJob} --trigger=schedule")
        ->cron($syncCfg['cron'])
        ->timezone(config('sync_wrs.timezone', 'Asia/Jakarta'))
        ->runInBackground()
        ->description("Sync WRS Sales: {$syncCfg['label']}");
}

// Permintaan "Jalankan Sekarang" dari menu monitoring
Schedule::command('sync-wrs:run --queued')
    ->everyMinute()
    ->withoutOverlapping((int) ceil(config('sync_wrs.lock_seconds', 14400) / 60))
    ->runInBackground()
    ->description('Sync WRS Sales: antrean manual');

// Sync WRS Connect After Sales → MSI (Customer CSI), pengganti upload_customer_csi.php
//  - setiap hari 21:00 WIB (config/sync_msi.php / .env SYNC_MSI_CRON)
Schedule::command('sync-msi:run --trigger=schedule')
    ->cron(config('sync_msi.cron', '0 21 * * *'))
    ->timezone(config('sync_msi.timezone', 'Asia/Jakarta'))
    ->runInBackground()
    ->description('Sync MSI: Customer CSI');

// Permintaan "Jalankan Manual" (dengan rentang tanggal) dari menu Monitoring Sync MSI
Schedule::command('sync-msi:run --queued')
    ->everyMinute()
    ->withoutOverlapping((int) ceil(config('sync_msi.lock_seconds', 14400) / 60))
    ->runInBackground()
    ->description('Sync MSI: antrean manual');
