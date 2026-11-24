<?php

namespace App\Http\Controllers;

use App\Models\Afsel\DealerUser;
use App\Services\Afsel\AfselAuthService;
use App\Services\Afsel\AfselRbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Throwable;

/**
 * Profile user After Sales Dealer: lihat Group Dealer, Nama Dealer, Nama; ganti email & reset password.
 * Setiap perubahan wajib konfirmasi password saat ini.
 */
class DealerProfileController
{
    public function __construct(private AfselAuthService $auth, private AfselRbacService $rbac)
    {
    }

    public function index()
    {
        $user   = $this->currentUser();
        $dealer = DB::table('tbldealer')->where('kd_dealer', $user->fk_dealer)->first(['kd_dealer', 'nm_dealer', 'fk_dealer_group', 'fk_dealer_region']);

        $roleCount = config('afsel.auth_driver') === 'local'
            ? $this->rbac->getAssignedRoles('dealer', $user->kd_dealer_user)->count()
            : 0;

        return view('aftersales.dealer.profile.index', [
            'user'          => $user,
            'dealer'        => $dealer,
            'groupName'     => $this->dealerGroupName($dealer->fk_dealer_group ?? null),
            'roleName'      => afsel_role()?->name,
            'canSwitchRole' => $roleCount > 1,
        ]);
    }

    public function updateEmail(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        $request->merge(['email' => trim((string) $request->input('email'))]);

        $validator = Validator::make($request->all(), [
            'email'            => ['required', 'string', 'max:150', 'email:rfc'],
            'current_password' => ['required', 'string'],
        ], [
            'email.required'            => 'Email baru wajib diisi.',
            'email.email'               => 'Format email tidak valid.',
            'email.max'                 => 'Email maksimal 150 karakter.',
            'current_password.required' => 'Password saat ini wajib diisi.',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), $validator->errors()->toArray());
        }
        if (!$this->auth->checkPassword((string) $request->input('current_password'), (string) $user->password)) {
            return $this->fail('Password saat ini salah.', ['current_password' => ['Password saat ini salah.']]);
        }
        if (strcasecmp((string) $user->email, $request->input('email')) === 0) {
            return $this->fail('Email baru sama dengan email saat ini.', ['email' => ['Email baru sama dengan email saat ini.']]);
        }

        $old = $user->email;
        DB::table('ms_afsel_dealer_user')->where('kd_dealer_user', $user->kd_dealer_user)->update([
            'email'            => $request->input('email'),
            'email_changed_at' => now(),
            'updated_by'       => $user->kd_dealer_user,
            'date_updated'     => now(),
        ]);

        session(['user.email' => $request->input('email')]);
        Log::info('Afsel dealer user changed email', ['user' => $user->kd_dealer_user, 'old' => $old, 'new' => $request->input('email')]);

        return $this->ok('Email berhasil diubah.');
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $this->currentUser();

        // Dealer: reset password tanpa konfirmasi password saat ini (cukup sesi login yang aktif)
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string', 'confirmed', 'max:100', Password::min(8)->letters()->numbers()],
        ], [
            'password.required'  => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password baru tidak sama.',
            'password.min'       => 'Password baru minimal 8 karakter.',
            'password.letters'   => 'Password baru harus mengandung huruf.',
            'password.numbers'   => 'Password baru harus mengandung angka.',
            'password.max'       => 'Password baru maksimal 100 karakter.',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), $validator->errors()->toArray());
        }
        if ($this->auth->checkPassword((string) $request->input('password'), (string) $user->password)) {
            return $this->fail('Password baru harus berbeda dengan password saat ini.', ['password' => ['Password baru harus berbeda dengan password saat ini.']]);
        }

        DB::table('ms_afsel_dealer_user')->where('kd_dealer_user', $user->kd_dealer_user)->update([
            'password'              => $this->auth->hashPassword((string) $request->input('password')),
            'password_changed_at'   => now(),
            'failed_login_attempts' => 0,
            'locked_until'          => null,
            'updated_by'            => $user->kd_dealer_user,
            'date_updated'          => now(),
        ]);

        Log::info('Afsel dealer user changed password', ['user' => $user->kd_dealer_user]);

        return $this->ok('Password berhasil diubah. Gunakan password baru saat login berikutnya.');
    }

    private function currentUser(): DealerUser
    {
        $user = DealerUser::query()
            ->where('kd_dealer_user', (string) session('user.id'))
            ->where('is_active', true)
            ->first();

        abort_unless($user, 404, 'Data user tidak ditemukan.');

        return $user;
    }

    /** Nama group dealer dari master WRS (tbldealer_group); jika WRS tidak bisa diakses cukup tampil kodenya. */
    private function dealerGroupName(?string $code): ?string
    {
        if (!$code) {
            return null;
        }

        try {
            return Cache::remember('afsel_dealer_group_name_' . md5($code), 86400, fn () => DB::connection('db_wrs_aftersales')
                ->table('tbldealer_group')->where('kd_dealer_group', $code)->value('nm_dealer_group'));
        } catch (Throwable $e) {
            report($e);
            return null;
        }
    }

    private function ok(string $message): JsonResponse
    {
        return response()->json(['status' => true, 'message' => $message, 'errors' => '']);
    }

    private function fail(string $message, array $errors = [], int $code = 422): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $message, 'errors' => $errors], $code);
    }
}
