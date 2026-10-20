<?php

namespace App\Services;

use App\Exports\EmailSchedulerCkdExport;
use App\Repositories\EmailSchedulerCkdRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class EmailSchedulerCkdService
{
    const JOB_NAME = 'EMAIL_SCHEDULER_DELIVERY_CKD';
    const LOG_TABLE = 'tr_email_scheduler_ckd_log';
    const RETRY_LOG_TABLE = 'tr_email_scheduler_ckd_retry_log';

    protected $repo;
    protected $mailer;

    public function __construct(EmailSchedulerCkdRepository $repo, MicrosoftGraphMailService $mailer)
    {
        $this->repo = $repo;
        $this->mailer = $mailer;
    }

    /**
     * Jalankan pengiriman. Jika $logId diisi (retry), log yang sama di-update,
     * dan data yang dikirim adalah data tanggal log tersebut dibuat.
     * Setiap retry juga dicatat di tr_email_scheduler_ckd_retry_log.
     */
    public function run($logId = null, $retryBy = null)
    {
        $setup = $this->repo->getSetup();
        $rawEmailTo = $setup->email_to ?? null;
        $retryLogId = null;

        if ($logId) {
            $log = DB::table(self::LOG_TABLE)->where('id', $logId)->first();
            if (!$log) {
                return ['status' => false, 'message' => 'Log tidak ditemukan'];
            }
            $tanggal = Carbon::parse($log->created_at)->toDateString();

            // retry selalu pakai email_to terbaru dari setup
            DB::table(self::LOG_TABLE)->where('id', $logId)->update([
                'email_to' => $rawEmailTo,
                'updated_at' => now(),
            ]);

            $retryLogId = DB::table(self::RETRY_LOG_TABLE)->insertGetId([
                'fk_email_scheduler_ckd_log' => $logId,
                'email_to' => $rawEmailTo,
                'retry_date' => now(),
                'retry_by' => $retryBy,
                'date_sent' => null,
                'status_sent' => 'NOT SENT',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $tanggal = now()->toDateString();
            $logId = DB::table(self::LOG_TABLE)->insertGetId([
                'job_name' => self::JOB_NAME,
                'email_to' => $rawEmailTo,
                'date_sent' => null,
                'status_sent' => 'NOT SENT',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        try {
            $emailTo = $this->parseEmails($rawEmailTo ?? '');

            if (empty($emailTo)) {
                throw new \Exception('Email To kosong / tidak valid di ms_setup_email_scheduler_ckd');
            }

            $data = $this->repo->getDeliveryData($tanggal);

            $fileName = 'Delivery_CKD_' . Carbon::parse($tanggal)->format('Ymd') . '.xlsx';
            $fileContent = Excel::raw(new EmailSchedulerCkdExport($data), \Maatwebsite\Excel\Excel::XLSX);

            $subject = 'Delivery CKD - Data Input : ' . Carbon::parse($tanggal)->format('d M Y');
            $body = view('emails.email_scheduler_ckd', [
                'tanggal' => Carbon::parse($tanggal)->format('d M Y'),
                'totalRow' => $data->count(),
            ])->render();

            $this->mailer->send($emailTo, $subject, $body, [[
                'name' => $fileName,
                'content' => $fileContent,
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]]);

            DB::table(self::LOG_TABLE)->where('id', $logId)->update([
                'date_sent' => now(),
                'status_sent' => 'SUCCESS',
                'updated_at' => now(),
            ]);

            $this->updateRetryLog($retryLogId, 'SUCCESS');

            DB::table('ms_setup_email_scheduler_ckd')->where('id', $setup->id)->update([
                'date_sent' => now(),
                'status_sent' => 'SUCCESS',
                'updated_at' => now(),
            ]);

            return ['status' => true, 'message' => 'Email berhasil dikirim ke ' . implode(', ', $emailTo)];
        } catch (\Throwable $e) {
            DB::table(self::LOG_TABLE)->where('id', $logId)->update([
                'date_sent' => now(),
                'status_sent' => 'FAILED',
                'updated_at' => now(),
            ]);

            $this->updateRetryLog($retryLogId, 'FAILED');

            Log::error('Email Scheduler CKD failed', ['log_id' => $logId, 'error' => $e->getMessage()]);

            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    protected function updateRetryLog($retryLogId, $status)
    {
        if (!$retryLogId) {
            return;
        }

        DB::table(self::RETRY_LOG_TABLE)->where('id', $retryLogId)->update([
            'date_sent' => now(),
            'status_sent' => $status,
            'updated_at' => now(),
        ]);
    }

    /**
     * email_to boleh lebih dari satu, dipisah koma / titik koma.
     */
    public function parseEmails($emailTo)
    {
        return collect(preg_split('/[,;]+/', (string) $emailTo))
            ->map(fn ($email) => trim($email))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();
    }
}
