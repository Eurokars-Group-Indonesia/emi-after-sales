<?php

namespace App\Http\Middleware;

use App\Services\Afsel\AfselRbacService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pintu masuk area After Sales (RBAC lokal).
 * Pemakaian: ->middleware('afsel.auth:atpm') / ->middleware('afsel.auth:dealer')
 *
 * - Session harus login After Sales dengan tipe yang sama.
 * - User masih aktif (dinonaktifkan saat login → langsung dikeluarkan).
 * - User masih punya role; punya > 1 role & belum memilih → halaman pilih role.
 */
class AfselEnsureAccess
{
    public function __construct(private AfselRbacService $rbac) {}

    public function handle(Request $request, Closure $next, string $type): Response
    {
        // Mode login lama (API) tidak memakai RBAC lokal
        if (config('afsel.auth_driver') !== 'local') {
            return $next($request);
        }

        if (session('user.app') !== 'aftersales' || session('user.loginAs') !== $type) {
            abort(403, 'Akses ditolak. Silakan login ke After Sales sebagai ' . strtoupper($type) . '.');
        }

        $cfg    = $this->rbac->config($type);
        $userId = (string) session('user.id');

        $isActive = DB::table($cfg['user_table'])->where($cfg['user_key'], $userId)->value('is_active');

        if (!$isActive) {
            return $this->kick($request, 'Akun Anda tidak aktif. Hubungi administrator.');
        }

        if ($this->rbac->getAssignedRoles($type, $userId)->isEmpty()) {
            return $this->kick($request, 'Akun Anda belum memiliki role aktif. Hubungi administrator.');
        }

        if (!$this->rbac->getActiveRole($type, $userId)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Silakan pilih role terlebih dahulu.'], 409);
            }

            return redirect()->route('aftersales.role.select');
        }

        return $next($request);
    }

    private function kick(Request $request, string $message): Response
    {
        $request->session()->flush();
        $request->session()->regenerate();

        return redirect()->route('loginAfterSales')->withErrors(['login' => $message]);
    }
}
