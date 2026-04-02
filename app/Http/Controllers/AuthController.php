<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\UserWrsAfterSalesApiService;
use Illuminate\Auth\GenericUser;
// use Illuminate\Support\Facades\Cache;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\RateLimiter;
// use Laravel\Socialite\Facades\Socialite;
// use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function loginWrsAfterSales(Request $request, UserWrsAfterSalesApiService $UserWrsAfterSalesApiService)
    {
        
        $username = $request->input('username');
        $password = $request->input('password');
        $loginAs = $request->input('login_as');

        try {

            $response = $UserWrsAfterSalesApiService->login($username, $password, $loginAs);
            // dd($response);
            if ($response['code'] !== 200 || $response['status'] !== 'SUCCESS') {
                return back()->withErrors([
                    'login' => $response['message'] ?? 'Login gagal'
                ]);
            }
            else 
            {
                $user = $response['data'];

                session([
                    'user.loginAs'  => $loginAs,
                    'user.id'       => $user['kd_atpm_user'],
                    'user.name'     => $user['nm_atpm_user'],
                    'user.username' => $user['username'],
                    'user.email'    => $user['email'],
                    'user.level'     => $user['atpm_level']['nm_atpm_level'],
                    'user.dept'     => $user['atpm_dept']['nm_atpm_department'],
                ]);


                $user = new GenericUser([
                    'id' => $response['data']['kd_atpm_user'],
                    'name' => $response['data']['nm_atpm_user'],
                ]);

                Auth::login($user);


                if($loginAs == 'atpm')
                {
                    return redirect()->route('atpm.aftersales.home');
                }
                else 
                {
                    // return redirect()->route('dealer.aftersales.home');
                }

                
            }

        } catch (\Illuminate\Http\Client\RequestException $e) {
            return back()->withErrors([
                'login' => 'Server API tidak merespon'
            ]);
        } catch (\Exception $e) {
            return back()->withErrors([
                'login' => 'Terjadi kesalahan'
            ]);
        }
    }

}