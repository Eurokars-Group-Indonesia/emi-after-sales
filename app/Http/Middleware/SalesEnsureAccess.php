<?php

namespace App\Http\Middleware;

use App\Services\Sales\SalesRbacService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pintu masuk area Sales (RBAC Sales, terpisah dari After Sales).
 * Pemakaian: ->middleware('sales.auth:atpm') / ->middleware('sales.auth:dealer')
 *
 * - Session harus login Sales (user.app = sales) dengan tipe yang sama.
 * - User masih aktif di database WRS Sales (dicek ulang tiap sales.user_check_ttl detik).
 * - User masih punya role; punya > 1 role & belum memilih → halaman pilih role.
 */
class SalesEnsureAccess
{
    public function __construct(private SalesRbacService $rbac) {}

    public function handle(Request $request, Closure $next, string $type): Response
    {
        // Mode login lama (API) tidak memakai RBAC lokal
        if (config('sales.auth_driver') === 'api') {
            return $next($request);
        }

        if (session('user.app') !== 'sales' || session('user.loginAs') !== $type) {
            return $this->kick($request, 'Silakan login ke WRS Connect Sales sebagai ' . strtoupper($type) . '.');
        }

        $userId = (string) session('user.id');

        if (!$this->isUserActive($type, $userId)) {
            return $this->kick($request, 'Akun Anda tidak aktif. Hubungi administrator.');
        }

        if ($this->rbac->getAssignedRoles($type, $userId)->isEmpty()) {
            return $this->kick($request, 'Akun Anda belum memiliki role Sales. Hubungi administrator.');
        }

        if (!$this->rbac->getActiveRole($type, $userId)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Silakan pilih role terlebih dahulu.'], 409);
            }

            return redirect()->route('sales.role.select');
        }

        return $next($request);
    }

    /**
     * Status aktif user di database WRS Sales (pemilik akun). Hasil di-cache singkat agar tidak
     * query lintas database di setiap request. Jika database WRS Sales tidak bisa dihubungi,
     * user tetap dianggap aktif (sudah diverifikasi saat login) supaya aplikasi tidak ikut down.
     */
    private function isUserActive(string $type, string $userId): bool
    {
        $ttl = (int) config('sales.user_check_ttl', 60);

        try {
            return Cache::remember("sales_user_active_{$type}_{$userId}", $ttl, function () use ($type, $userId) {
                $cfg = $this->rbac->config($type);
                $isActive = $this->rbac->userDb()->table($cfg['user_table'])->where($cfg['user_key'], $userId)->value('is_active');

                return filter_var($isActive, FILTER_VALIDATE_BOOLEAN);
            });
        } catch (\Throwable $e) {
            Log::warning('[SalesEnsureAccess] cek status user gagal: ' . $e->getMessage());
            return true;
        }
    }

    private function kick(Request $request, string $message): Response
    {
        $request->session()->flush();
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 401);
        }

        return redirect()->route('loginSales')->withErrors(['login' => $message]);
    }
}
