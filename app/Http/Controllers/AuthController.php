<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserWrsAfterSalesApiService;
use App\Services\WrsSalesUserApiService;

class AuthController extends Controller
{

    public function wrsOption()
    {
        return view('wrs.option');
    }

    public function showLoginAfterSales()
    {
        if (session()->has('user.id')) {
            $loginAs = session('user.loginAs');
            if ($loginAs === 'atpm') {
                return redirect()->route('aftersales.atpm.home');
            } elseif ($loginAs === 'dealer') {
                return redirect()->route('aftersales.dealer.home');
            }
        }

        return view('aftersales.auth.login');
    }

    public function showLoginSales()
    {
        if (session()->has('user.id')) {
            $loginAs = session('user.loginAs');
            if ($loginAs === 'atpm') {
                return redirect()->route('sales.atpm.home');
            } elseif ($loginAs === 'dealer') {
                return redirect()->route('sales.dealer.home');
            }
        }

        return view('sales.auth.login');
    }

    
    public function loginWrsAfterSales(Request $request, UserWrsAfterSalesApiService $api)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
            'login_as' => 'required|in:atpm,dealer',
        ], [
            'username.required' => 'Username tidak boleh kosong.',
            'password.required' => 'Password tidak boleh kosong.',
            'login_as.required' => 'Login As tidak boleh kosong.',
            'login_as.in'       => 'Login As tidak valid.',
        ]);

        $username = $request->input('username');
        $password = $request->input('password');
        $loginAs  = $request->input('login_as');

        try {
            
            $response = $api->login($username, $password, $loginAs);

            // dd($response);
            // Gagal jika response kosong atau bukan SUCCESS
            if (empty($response) || ($response['code'] ?? null) !== 200 || ($response['status'] ?? '') !== 'SUCCESS') {

                if($response['message']=='NOT_FOUND')
                {
                    $responseMessage = 'Username Not Found';
                }
                else if($response['message']=='AUTHENTICATION_FAILED')
                {
                    $responseMessage = 'Username or Password is wrong';
                }
                else 
                {
                    $responseMessage = '';
                }



                return back()->withErrors([
                    'login' => $responseMessage ?? 'Login gagal.'
                ]);
            }

            $user = $response['data'];

            if ($loginAs === 'atpm') {
                session([
                    'user.loginAs'  => $loginAs,
                    'user.id'       => $user['kd_atpm_user'],
                    'user.name'     => $user['nm_atpm_user'],
                    'user.username' => $user['username'],
                    'user.email'    => $user['email'],
                    'user.level'    => $user['atpm_level']['nm_atpm_level'] ?? null,
                    'user.dept'     => $user['atpm_dept']['nm_atpm_department'] ?? null,
                ]);
                return redirect()->route('aftersales.atpm.home');
            }

            if ($loginAs === 'dealer') {
                session([
                    'user.loginAs'   => $loginAs,
                    'user.id'        => $user['kd_dealer_user'],
                    'user.name'      => $user['nm_dealer_user'],
                    'user.username'  => $user['username'],
                    'user.email'     => $user['email'],
                    'user.kd_dealer' => $user['fk_dealer'],
                    'user.nm_dealer' => $user['dealer']['nm_dealer'] ?? null,
                ]);
                return redirect()->route('dealer.aftersales.home');
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return back()->withErrors(['login' => 'Server API tidak dapat dihubungi.']);
        } catch (\Exception $e) {
            return back()->withErrors(['login' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }


    public function loginWrsSales(Request $request, WrsSalesUserApiService $api)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
            'login_as' => 'required|in:atpm,dealer',
        ], [
            'username.required' => 'Username tidak boleh kosong.',
            'password.required' => 'Password tidak boleh kosong.',
            'login_as.required' => 'Login As tidak boleh kosong.',
            'login_as.in'       => 'Login As tidak valid.',
        ]);

        $username = $request->input('username');
        $password = $request->input('password');
        $loginAs  = $request->input('login_as');

        try {
            
            $response = $api->login($username, $password, $loginAs);
            // dd($response);

            // Gagal jika response kosong atau bukan SUCCESS
            if (empty($response) || ($response['code'] ?? null) !== 200 || ($response['status'] ?? '') !== 'SUCCESS') {

                if($response['message']=='NOT_FOUND')
                {
                    $responseMessage = 'Username Not Found';
                }
                else if($response['message']=='AUTHENTICATION_FAILED')
                {
                    $responseMessage = 'Username or Password is wrong';
                }
                else 
                {
                    $responseMessage = '';
                }

                return back()->withErrors([
                    'login' => $responseMessage ?? 'Login gagal.'
                ]);
            }

            

            $user = $response['data'];

            if ($loginAs === 'atpm') {
                session([
                    'user.loginAs'  => $loginAs,
                    'user.id'       => $user['kd_atpm_user'],
                    'user.name'     => $user['nm_atpm_user'],
                    'user.username' => $user['username'],
                    'user.email'    => $user['email'],
                    'user.level'    => $user['atpm_level']['nm_atpm_level'] ?? null,
                    'user.dept'     => $user['atpm_dept']['nm_atpm_department'] ?? null,
                ]);
                return redirect()->route('sales.atpm.home');
            }

            if ($loginAs === 'dealer') {
                session([
                    'user.loginAs'   => $loginAs,
                    'user.id'        => $user['kd_dealer_user'],
                    'user.name'      => $user['nm_dealer_user'],
                    'user.username'  => $user['username'],
                    'user.email'     => $user['email'],
                    'user.kd_dealer' => $user['fk_dealer'],
                    'user.nm_dealer' => $user['dealer']['nm_dealer'] ?? null,
                ]);
                return redirect()->route('sales.dealer.home');
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return back()->withErrors(['login' => 'Server API tidak dapat dihubungi.']);
        } catch (\Exception $e) {
            return back()->withErrors(['login' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    public function aftersalesLogout(Request $request)
    {
        $request->session()->flush();
        return redirect()->route('loginAfterSales');
    }


    public function salesLogout(Request $request)
    {
        $request->session()->flush();
        return redirect()->route('loginSales');
    }


    
}
