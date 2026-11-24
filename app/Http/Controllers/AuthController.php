<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserWrsAfterSalesApiService;
use App\Services\WrsSalesUserApiService;
use App\Services\WrsSalesAuthService;
use App\Services\Afsel\AfselAuthService;
use App\Services\Afsel\AfselRbacService;
use App\Services\Sales\SalesRbacService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{

    public function wrsOption()
    {
        return view('wrs.option');
    }

    public function showLoginAfterSales()
    {
        if (session()->has('user.id') && session('user.app') === 'aftersales') {
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
        if (session()->has('user.id') && session('user.app') === 'sales') {
            $loginAs = session('user.loginAs');
            if ($loginAs === 'atpm') {
                return redirect()->route('sales.atpm.home');
            } elseif ($loginAs === 'dealer') {
                return redirect()->route('sales.dealer.home');
            }
        }

        return view('sales.auth.login');
    }

    
    /**
     * Login After Sales.
     * Driver dipilih lewat config afsel.auth_driver (env AFSEL_AUTH_DRIVER):
     *  - local (default): tabel ms_afsel_atpm_user / ms_afsel_dealer_user + RBAC
     *  - api            : API_SERVICE lama
     */
    public function loginWrsAfterSales(Request $request, UserWrsAfterSalesApiService $api, AfselAuthService $auth, AfselRbacService $rbac)
    {
        if (config('afsel.auth_driver') === 'local') {
            return $this->loginAfterSalesLocal($request, $auth, $rbac);
        }

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
                    'user.app'      => 'aftersales',
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
                    'user.app'       => 'aftersales',
                    'user.loginAs'   => $loginAs,
                    'user.id'        => $user['kd_dealer_user'],
                    'user.name'      => $user['nm_dealer_user'],
                    'user.username'  => $user['username'],
                    'user.email'     => $user['email'],
                    'user.kd_dealer' => $user['fk_dealer'],
                    'user.nm_dealer' => $user['dealer']['nm_dealer'] ?? null,
                ]);
                return redirect()->route('aftersales.dealer.home');
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return back()->withErrors(['login' => 'Server API tidak dapat dihubungi.']);
        } catch (\Exception $e) {
            return back()->withErrors(['login' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }


    /**
     * Login After Sales ke tabel user lokal + RBAC.
     */
    private function loginAfterSalesLocal(Request $request, AfselAuthService $auth, AfselRbacService $rbac)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'login_as' => 'required|in:atpm,dealer',
        ], [
            'username.required' => 'Username tidak boleh kosong.',
            'password.required' => 'Password tidak boleh kosong.',
            'login_as.required' => 'Login As tidak boleh kosong.',
            'login_as.in'       => 'Login As tidak valid.',
        ]);

        $loginAs  = $request->input('login_as');
        $username = trim($request->input('username'));

        // Rate limit per username + IP
        $throttleKey = 'afsel-login:' . $loginAs . '|' . mb_strtolower($username) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withInput($request->only('username', 'login_as'))
                ->withErrors(['login' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik."]);
        }

        try {
            $result = $auth->attempt($loginAs, $username, $request->input('password'), $request->ip());
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput($request->only('username', 'login_as'))
                ->withErrors(['login' => 'Terjadi kesalahan saat login. Silakan coba lagi.']);
        }

        if (!$result['ok']) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withInput($request->only('username', 'login_as'))
                ->withErrors(['login' => $result['message']]);
        }

        RateLimiter::clear($throttleKey);

        $user = $result['user'];

        // Cegah session fixation
        $request->session()->flush();
        $request->session()->regenerate();

        if ($loginAs === 'atpm') {
            session([
                'user.app'      => 'aftersales',
                'user.loginAs'  => 'atpm',
                'user.id'       => $user->kd_atpm_user,
                'user.name'     => $user->nm_atpm_user,
                'user.username' => $user->username,
                'user.email'    => $user->email,
                'user.level'    => $user->fk_atpm_level,
                'user.dept'     => $user->fk_atpm_department,
            ]);
        } else {
            session([
                'user.app'       => 'aftersales',
                'user.loginAs'   => 'dealer',
                'user.id'        => $user->kd_dealer_user,
                'user.name'      => $user->nm_dealer_user,
                'user.username'  => $user->username,
                'user.email'     => $user->email,
                'user.kd_dealer' => $user->fk_dealer,
                'user.nm_dealer' => $user->fk_dealer
                    ? DB::table('tbldealer')->where('kd_dealer', $user->fk_dealer)->value('nm_dealer')
                    : null,
            ]);
        }

        // Multi role → pilih role dulu; 1 role → otomatis aktif
        $userId = (string) session('user.id');
        if (!$rbac->getActiveRole($loginAs, $userId)) {
            return redirect()->route('aftersales.role.select');
        }

        return redirect()->route($rbac->config($loginAs)['home_route']);
    }

    /**
     * Halaman pilih role (user dengan lebih dari satu role).
     */
    public function showSelectRoleAfterSales(AfselRbacService $rbac)
    {
        $type = afsel_type();
        if (!$type) {
            return redirect()->route('loginAfterSales');
        }

        $roles = $rbac->getAssignedRoles($type, (string) session('user.id'));
        if ($roles->isEmpty()) {
            session()->flush();
            return redirect()->route('loginAfterSales')->withErrors(['login' => 'Akun Anda belum memiliki role. Hubungi administrator.']);
        }

        return view('aftersales.auth.select-role', [
            'roles'        => $roles,
            'activeRoleId' => (int) session(AfselRbacService::SESSION_ROLE_KEY),
        ]);
    }

    /**
     * Simpan role aktif (pilih role / switch role).
     */
    public function selectRoleAfterSales(Request $request, AfselRbacService $rbac)
    {
        $type = afsel_type();
        if (!$type) {
            return redirect()->route('loginAfterSales');
        }

        $request->validate(['role_id' => 'required|integer']);

        try {
            $rbac->setActiveRole($type, (string) session('user.id'), (int) $request->input('role_id'));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['role_id' => $e->getMessage()]);
        }

        return redirect()->route($rbac->config($type)['home_route']);
    }

    /**
     * Login Sales.
     * Driver dipilih lewat config sales.auth_driver (env SALES_AUTH_DRIVER):
     *  - db  (default): langsung ke database WRS Sales (tblatpm_user / tbldealer_user)
     *  - api          : API_SERVICE lama
     */
    public function loginWrsSales(Request $request, WrsSalesUserApiService $api, WrsSalesAuthService $auth, SalesRbacService $rbac)
    {
        if (config('sales.auth_driver') !== 'api') {
            return $this->loginSalesDb($request, $auth, $rbac);
        }

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
                    'user.app'      => 'sales',
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
                    'user.app'       => 'sales',
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

    /**
     * Login Sales langsung ke database WRS Sales (tanpa API) + RBAC Sales lokal.
     * Key session sama dengan login via API, ditambah user.app = 'sales' dan role aktif (user.sales_role_id).
     */
    private function loginSalesDb(Request $request, WrsSalesAuthService $auth, SalesRbacService $rbac)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'login_as' => 'required|in:atpm,dealer',
        ], [
            'username.required' => 'Username tidak boleh kosong.',
            'password.required' => 'Password tidak boleh kosong.',
            'login_as.required' => 'Login As tidak boleh kosong.',
            'login_as.in'       => 'Login As tidak valid.',
        ]);

        $loginAs  = $request->input('login_as');
        $username = trim($request->input('username'));

        // Rate limit per username + IP
        $throttleKey = 'sales-login:' . $loginAs . '|' . mb_strtolower($username) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, config('sales.max_login_attempts', 10))) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withInput($request->only('username', 'login_as'))
                ->withErrors(['login' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik."]);
        }

        try {
            $result = $auth->attempt($loginAs, $username, $request->input('password'));
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput($request->only('username', 'login_as'))
                ->withErrors(['login' => 'Database WRS Sales tidak dapat dihubungi. Silakan coba lagi.']);
        }

        if (!$result['ok']) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withInput($request->only('username', 'login_as'))
                ->withErrors(['login' => $result['message']]);
        }

        RateLimiter::clear($throttleKey);

        $user   = $result['user'];
        $userId = (string) $user['id'];

        // RBAC Sales: user wajib punya role (role default otomatis jika dikonfigurasi, mis. Dealer)
        $rbac->ensureDefaultRole($loginAs, $userId);
        if ($rbac->getAssignedRoles($loginAs, $userId)->isEmpty()) {
            return back()->withInput($request->only('username', 'login_as'))
                ->withErrors(['login' => 'Akun Anda belum memiliki role WRS Connect Sales. Hubungi administrator.']);
        }

        // Cegah session fixation
        $request->session()->flush();
        $request->session()->regenerate();

        if ($loginAs === 'atpm') {
            session([
                'user.app'      => 'sales',
                'user.loginAs'  => 'atpm',
                'user.id'       => $userId,
                'user.name'     => $user['name'],
                'user.username' => $user['username'],
                'user.email'    => $user['email'],
                'user.level'    => $user['level'],
                'user.dept'     => $user['dept'],
            ]);
        } else {
            session([
                'user.app'       => 'sales',
                'user.loginAs'   => 'dealer',
                'user.id'        => $userId,
                'user.name'      => $user['name'],
                'user.username'  => $user['username'],
                'user.email'     => $user['email'],
                'user.kd_dealer' => $user['kd_dealer'],
                'user.nm_dealer' => $user['nm_dealer'],
            ]);
        }

        // Multi role → pilih role dulu; 1 role → otomatis aktif
        if (!$rbac->getActiveRole($loginAs, $userId)) {
            return redirect()->route('sales.role.select');
        }

        return redirect()->route($rbac->config($loginAs)['home_route']);
    }

    /**
     * Halaman pilih / ganti role aktif Sales (user dengan lebih dari satu role).
     */
    public function showSelectRoleSales(SalesRbacService $rbac)
    {
        $type = sales_type();
        if (!$type) {
            return redirect()->route('loginSales');
        }

        $roles = $rbac->getAssignedRoles($type, (string) session('user.id'));
        if ($roles->isEmpty()) {
            session()->flush();
            return redirect()->route('loginSales')->withErrors(['login' => 'Akun Anda belum memiliki role WRS Connect Sales. Hubungi administrator.']);
        }

        return view('sales.auth.select-role', [
            'roles'        => $roles,
            'activeRoleId' => (int) session(SalesRbacService::SESSION_ROLE_KEY),
            'homeRoute'    => $rbac->config($type)['home_route'],
        ]);
    }

    /**
     * Simpan role aktif Sales (pilih role / switch role).
     */
    public function selectRoleSales(Request $request, SalesRbacService $rbac)
    {
        $type = sales_type();
        if (!$type) {
            return redirect()->route('loginSales');
        }

        $request->validate(['role_id' => 'required|integer']);

        try {
            $rbac->setActiveRole($type, (string) session('user.id'), (int) $request->input('role_id'));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['role_id' => $e->getMessage()]);
        }

        return redirect()->route($rbac->config($type)['home_route']);
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
