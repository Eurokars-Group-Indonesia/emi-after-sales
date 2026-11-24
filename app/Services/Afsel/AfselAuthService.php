<?php

namespace App\Services\Afsel;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Login WRS Connect - After Sales ke tabel lokal
 * (ms_afsel_atpm_user / ms_afsel_dealer_user).
 *
 * Password:
 *  - Hash lama dari WRS (MD5-crypt "$1$...") tetap bisa dipakai login (password_verify).
 *  - Password baru / reset sebaiknya disimpan dengan Hash::make() (bcrypt).
 */
class AfselAuthService
{
    public function __construct(private AfselRbacService $rbac) {}

    /**
     * @return array{ok: bool, message?: string, user?: object}
     */
    public function attempt(string $type, string $username, string $password, ?string $ip = null): array
    {
        $cfg = $this->rbac->config($type);

        $users = DB::table($cfg['user_table'])->where('username', $username)->limit(2)->get();

        if ($users->isEmpty()) {
            return ['ok' => false, 'message' => 'Username Not Found'];
        }

        if ($users->count() > 1) {
            return ['ok' => false, 'message' => 'Username terdaftar lebih dari satu. Hubungi administrator.'];
        }

        $user = $users->first();
        $key  = $cfg['user_key'];

        if (!$user->is_active) {
            return ['ok' => false, 'message' => 'Akun Anda tidak aktif. Hubungi administrator.'];
        }

        if ($user->locked_until && now()->lt($user->locked_until)) {
            return ['ok' => false, 'message' => 'Akun terkunci sementara karena terlalu banyak percobaan login. Coba lagi nanti.'];
        }

        if (!$this->checkPassword($password, (string) $user->password)) {
            $attempts = (int) $user->failed_login_attempts + 1;
            $lock     = $attempts >= config('afsel.max_login_attempts', 5);

            DB::table($cfg['user_table'])->where($key, $user->$key)->update([
                'failed_login_attempts' => $lock ? 0 : $attempts,
                'locked_until'          => $lock ? now()->addMinutes(config('afsel.lockout_minutes', 15)) : null,
            ]);

            return ['ok' => false, 'message' => 'Username or Password is wrong'];
        }

        if ($this->rbac->getAssignedRoles($type, $user->$key)->isEmpty()) {
            return ['ok' => false, 'message' => 'Akun Anda belum memiliki role. Hubungi administrator.'];
        }

        DB::table($cfg['user_table'])->where($key, $user->$key)->update([
            'failed_login_attempts' => 0,
            'locked_until'          => null,
            'last_login_at'         => now(),
            'last_login_ip'         => $ip,
        ]);

        return ['ok' => true, 'user' => $user];
    }

    public function checkPassword(string $plain, string $hash): bool
    {
        if ($hash === '') {
            return false;
        }

        // password_verify mendukung bcrypt/argon serta format crypt() lama (MD5-crypt $1$)
        return password_verify($plain, $hash);
    }

    public function hashPassword(string $plain): string
    {
        return Hash::make($plain);
    }
}
