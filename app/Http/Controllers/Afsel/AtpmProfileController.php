<?php

namespace App\Http\Controllers\Afsel;

use App\Http\Controllers\Controller;
use App\Models\Afsel\AtpmUser;
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
 * Profile user After Sales ATPM: lihat Nama, Level, Department, Role; ganti email & reset password.
 * Ganti email & reset password wajib konfirmasi password saat ini.
 */
class AtpmProfileController extends Controller
{
    public function __construct(private AfselAuthService $auth, private AfselRbacService $rbac)
    {
    }

    public function index()
    {
        $user = $this->currentUser();

        $roleCount = config('afsel.auth_driver') === 'local'
            ? $this->rbac->getAssignedRoles('atpm', $user->kd_atpm_user)->count()
            : 0;

        return view('aftersales.atpm.page_profile.index', [
            'user'           => $user,
            // Di WRS After Sales: tblatpm_level ada di schema master_aftersales, tblatpm_department di schema public
            'levelName'      => $this->masterName('master_aftersales.tblatpm_level', 'kd_atpm_level', 'nm_atpm_level', $user->fk_atpm_level),
            'departmentName' => $this->masterName('public.tblatpm_department', 'kd_atpm_department', 'nm_atpm_department', $user->fk_atpm_department),
            'roleName'       => afsel_role()?->name,
            'canSwitchRole'  => $roleCount > 1,
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
        DB::table('ms_afsel_atpm_user')->where('kd_atpm_user', $user->kd_atpm_user)->update([
            'email'            => $request->input('email'),
            'email_changed_at' => now(),
            'updated_by'       => $user->kd_atpm_user,
            'date_updated'     => now(),
        ]);

        session(['user.email' => $request->input('email')]);
        Log::info('Afsel ATPM user changed email', ['user' => $user->kd_atpm_user, 'old' => $old, 'new' => $request->input('email')]);

        return $this->ok('Email berhasil diubah.');
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $this->currentUser();

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'confirmed', 'max:100', Password::min(8)->letters()->numbers(), 'different:current_password'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required'         => 'Password baru wajib diisi.',
            'password.confirmed'        => 'Konfirmasi password baru tidak sama.',
            'password.different'        => 'Password baru harus berbeda dengan password saat ini.',
            'password.min'              => 'Password baru minimal 8 karakter.',
            'password.letters'          => 'Password baru harus mengandung huruf.',
            'password.numbers'          => 'Password baru harus mengandung angka.',
            'password.max'              => 'Password baru maksimal 100 karakter.',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), $validator->errors()->toArray());
        }
        if (!$this->auth->checkPassword((string) $request->input('current_password'), (string) $user->password)) {
            return $this->fail('Password saat ini salah.', ['current_password' => ['Password saat ini salah.']]);
        }

        DB::table('ms_afsel_atpm_user')->where('kd_atpm_user', $user->kd_atpm_user)->update([
            'password'              => $this->auth->hashPassword((string) $request->input('password')),
            'password_changed_at'   => now(),
            'failed_login_attempts' => 0,
            'locked_until'          => null,
            'updated_by'            => $user->kd_atpm_user,
            'date_updated'          => now(),
        ]);

        Log::info('Afsel ATPM user changed password', ['user' => $user->kd_atpm_user]);

        return $this->ok('Password berhasil diubah. Gunakan password baru saat login berikutnya.');
    }

    private function currentUser(): AtpmUser
    {
        $user = AtpmUser::query()
            ->where('kd_atpm_user', (string) session('user.id'))
            ->where('is_active', true)
            ->first();

        abort_unless($user, 404, 'Data user tidak ditemukan.');

        return $user;
    }

    /** Nama level / department dari master WRS After Sales; jika WRS tidak bisa diakses tampil kodenya saja. */
    private function masterName(string $table, string $keyCol, string $nameCol, ?string $code): ?string
    {
        if (!$code) {
            return null;
        }

        try {
            return Cache::remember('afsel_' . md5($table . '|' . $code) . '_name', 86400, fn () => DB::connection('db_wrs_aftersales')
                ->table($table)->where($keyCol, $code)->value($nameCol));
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
