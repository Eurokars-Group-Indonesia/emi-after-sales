<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Email Scheduler Delivery CKD - setiap hari jam 4 sore
Schedule::command('email-scheduler:delivery-ckd')->dailyAt('16:00')->timezone('Asia/Jakarta');
