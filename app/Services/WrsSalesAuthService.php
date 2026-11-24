<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Login WRS Connect - Sales langsung ke database WRS Sales (pengganti WrsSalesUserApiService).
 *
 *  - atpm   : tblatpm_user (+ tblatpm_level, tblatpm_department)
 *  - dealer : tbldealer_user (+ tbldealer)
 *
 * Password di WRS Sales disimpan sebagai MD5-crypt ("$1$..."), diverifikasi dengan password_verify().
 */
class WrsSalesAuthService
{
    /**
     * @return array{ok: bool, message?: string, user?: array}
     */
    public function attempt(string $loginAs, string $username, string $password): array
    {
        return match ($loginAs) {
            'atpm'   => $this->attemptAtpm($username, $password),
            'dealer' => $this->attemptDealer($username, $password),
            default  => ['ok' => false, 'message' => 'Login As tidak valid.'],
        };
    }

    private function attemptAtpm(string $username, string $password): array
    {
        $users = $this->db()->table('tblatpm_user as u')
            ->leftJoin('tblatpm_level as l', 'l.kd_atpm_level', '=', 'u.fk_atpm_level')
            ->leftJoin('tblatpm_department as d', 'd.kd_atpm_department', '=', 'u.fk_atpm_department')
            ->where('u.username', $username)
            ->select('u.kd_atpm_user', 'u.nm_atpm_user', 'u.username', 'u.password', 'u.email', 'u.is_active',
                'l.nm_atpm_level', 'd.nm_atpm_department')
            ->limit(2)
            ->get();

        $check = $this->check($users, $password);
        if (!$check['ok']) {
            return $check;
        }

        $user = $check['user'];

        return ['ok' => true, 'user' => [
            'id'       => $user->kd_atpm_user,
            'name'     => $user->nm_atpm_user,
            'username' => $user->username,
            'email'    => $user->email,
            'level'    => $user->nm_atpm_level,
            'dept'     => $user->nm_atpm_department,
        ]];
    }

    private function attemptDealer(string $username, string $password): array
    {
        $users = $this->db()->table('tbldealer_user as u')
            ->leftJoin('tbldealer as d', 'd.kd_dealer', '=', 'u.fk_dealer')
            ->where('u.username', $username)
            ->select('u.kd_dealer_user', 'u.nm_dealer_user', 'u.username', 'u.password', 'u.email', 'u.is_active',
                'u.fk_dealer', 'd.nm_dealer')
            ->limit(2)
            ->get();

        $check = $this->check($users, $password);
        if (!$check['ok']) {
            return $check;
        }

        $user = $check['user'];

        return ['ok' => true, 'user' => [
            'id'        => $user->kd_dealer_user,
            'name'      => $user->nm_dealer_user,
            'username'  => $user->username,
            'email'     => $user->email,
            'kd_dealer' => $user->fk_dealer,
            'nm_dealer' => $user->nm_dealer,
        ]];
    }

    private function check($users, string $password): array
    {
        if ($users->isEmpty()) {
            return ['ok' => false, 'message' => 'Username Not Found'];
        }

        if ($users->count() > 1) {
            return ['ok' => false, 'message' => 'Username terdaftar lebih dari satu. Hubungi administrator.'];
        }

        $user = $users->first();
        $hash = (string) $user->password;

        if ($hash === '' || !password_verify($password, $hash)) {
            return ['ok' => false, 'message' => 'Username or Password is wrong'];
        }

        if (!filter_var($user->is_active, FILTER_VALIDATE_BOOLEAN)) {
            return ['ok' => false, 'message' => 'Akun Anda tidak aktif. Hubungi administrator.'];
        }

        return ['ok' => true, 'user' => $user];
    }

    private function db()
    {
        return DB::connection(config('sales.auth_connection', 'db_wrs_sales'));
    }
}
