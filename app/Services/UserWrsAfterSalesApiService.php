<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class UserWrsAfterSalesApiService
{
    private function baseHttp()
    {
        return Http::baseUrl(config('services.api_wrs_aftersales.base_url'))
            ->asJson()
            ->timeout(10);
    }

    public function login($username, $password, $loginAs)
    {
        $endpoints = [
            'atpm'   => '/wrs-aftersales/api/v1/atpm-user/login',
            'dealer' => '/wrs-aftersales/api/v1/dealer-user/login',
        ];

        if (!isset($endpoints[$loginAs])) {
            return ['code' => 400, 'status' => 'FAILED', 'message' => 'Login type tidak valid'];
        }

        return $this->baseHttp()
            ->post($endpoints[$loginAs], [
                'username' => $username,
                'password' => $password,
            ])
            ->json();
    }

    public function atpm_users()
    {
        //
    }
}
