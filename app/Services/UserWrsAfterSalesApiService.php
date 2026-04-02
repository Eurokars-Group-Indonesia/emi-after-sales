<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class UserWrsAfterSalesApiService
{
    // public function getUsers()
    // {
    //     return Http::timeout(10)
    //         ->retry(3, 100)
    //         ->get('https://api.example.com/users')
    //         ->json();
    // }

    // public function createUser($data)
    // {
    //     return Http::post('https://api.example.com/users', $data)
    //         ->json();
    // }

    public function login($username, $password, $loginAs)
    {
        
        if($loginAs == 'atpm')
        {
            return Http::baseUrl(config('services.api_wrs_aftersales.base_url'))
                ->asJson()
                ->timeout(10)
                ->retry(3, 100)
                ->post('/wrs-aftersales/api/v1/atpm-user/login', [
                    'username' => $username,
                    'password' => $password,
                ])
                ->throw()
                ->json();
        }
        else if($loginAs == 'dealer')
        {
            return Http::baseUrl(config('services.api_wrs_aftersales.base_url'))
                ->asJson()
                ->timeout(10)
                ->retry(3, 100)
                ->post('/wrs-aftersales/api/v1/dealer-user/login', [
                    'username' => $username,
                    'password' => $password,
                ])
                ->throw()
                ->json();
        }
        else 
        {
            return response()->json([
                'message' => 'Login type tidak valid'
            ], 400);
        }
    }

    public function atpm_users()
    {
        // return Http::baseUrl(config('services.api_wrs_aftersales.base_url'))
        //         ->asJson()
        //         ->timeout(10)
        //         ->retry(3, 100)
        //         ->get('/api-gateway/v1/wrs/atpm-users', [
        //             'username' => $username,
        //             'password' => $password,
        //         ])
        //         ->throw()
        //         ->json();
    }
}