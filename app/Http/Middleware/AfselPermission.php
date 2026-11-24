<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cek permission After Sales (role aktif).
 * Pemakaian: ->middleware('afsel.permission:report-retention.view')
 *            ->middleware('afsel.permission:model-other.create,model-other.edit')  // OR
 *
 * Harus dipasang setelah 'afsel.auth:{type}'.
 */
class AfselPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (config('afsel.auth_driver') !== 'local') {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            if (afsel_can($permission)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'code' => 403, 'message' => 'Forbidden'], 403);
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
