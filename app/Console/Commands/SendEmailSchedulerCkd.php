<?php

namespace App\Console\Commands;

use App\Services\EmailSchedulerCkdService;
use Illuminate\Console\Command;

class SendEmailSchedulerCkd extends Command
{
    protected $signature = 'email-scheduler:delivery-ckd';

    protected $description = 'Kirim email harian Delivery CKD beserta attachment Excel';

    public function handle(EmailSchedulerCkdService $service)
    {
        $result = $service->run();

        if ($result['status']) {
            $this->info($result['message']);
            return self::SUCCESS;
        }

        $this->error($result['message']);
        return self::FAILURE;
    }
}
