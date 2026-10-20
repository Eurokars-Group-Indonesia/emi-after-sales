<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Kirim email via Microsoft Graph API (OAuth2 client credentials).
 * Kredensial diambil dari config/services.php -> ms_graph_mail (.env: MS_GRAPH_*).
 */
class MicrosoftGraphMailService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('services.ms_graph_mail');
    }

    protected function http()
    {
        $options = [];
        if (!empty($this->config['proxy'])) {
            $options['proxy'] = $this->config['proxy'];
        }

        return Http::withOptions($options)->timeout(60);
    }

    protected function getAccessToken()
    {
        foreach (['tenant_id', 'client_id', 'client_secret', 'from_address'] as $key) {
            if (empty($this->config[$key])) {
                throw new \Exception("Konfigurasi email OAuth belum diisi: {$key} (cek MS_GRAPH_* di .env)");
            }
        }

        return Cache::remember('ms_graph_mail_token', 3000, function () {
            $response = $this->http()->asForm()->post(
                'https://login.microsoftonline.com/' . $this->config['tenant_id'] . '/oauth2/v2.0/token',
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->config['client_id'],
                    'client_secret' => $this->config['client_secret'],
                    'scope' => 'https://graph.microsoft.com/.default',
                ]
            );

            if ($response->failed()) {
                throw new \Exception('Gagal ambil token OAuth: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    /**
     * @param array $to         daftar email penerima
     * @param array $attachments [['name' => 'file.xlsx', 'content' => <binary>, 'mime' => '...']]
     */
    public function send(array $to, string $subject, string $htmlBody, array $attachments = [])
    {
        $token = $this->getAccessToken();

        $message = [
            'subject' => $subject,
            'from' => ['emailAddress' => [
                'address' => $this->config['from_address'],
                'name' => $this->config['from_name'],
            ]],
            'body' => ['contentType' => 'HTML', 'content' => $htmlBody],
            'toRecipients' => array_map(fn ($email) => ['emailAddress' => ['address' => $email]], $to),
            'attachments' => array_map(fn ($file) => [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $file['name'],
                'contentType' => $file['mime'] ?? 'application/octet-stream',
                'contentBytes' => base64_encode($file['content']),
            ], $attachments),
        ];

        $response = $this->http()->withToken($token)->post(
            'https://graph.microsoft.com/v1.0/users/' . $this->config['from_address'] . '/sendMail',
            ['message' => $message, 'saveToSentItems' => false]
        );

        if ($response->status() === 401) {
            Cache::forget('ms_graph_mail_token');
        }

        if ($response->failed()) {
            throw new \Exception('Gagal kirim email: ' . $response->body());
        }

        return true;
    }
}
