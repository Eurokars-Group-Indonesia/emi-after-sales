<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserWrsAfterSalesApiService;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session()->has('user.id')) {
            $loginAs = session('user.loginAs');
            if ($loginAs === 'atpm') {
                return redirect()->route('atpm.aftersales.home');
            } elseif ($loginAs === 'dealer') {
                return redirect()->route('dealer.aftersales.home');
            }
        }
        return view('auth.login');
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

            // Gagal jika response kosong atau bukan SUCCESS
            if (empty($response) || ($response['code'] ?? null) !== 200 || ($response['status'] ?? '') !== 'SUCCESS') {
                return back()->withErrors([
                    'login' => $response['message'] ?? 'Login gagal.'
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
                return redirect()->route('atpm.aftersales.home');
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

    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect()->route('login');
    }
}
