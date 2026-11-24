<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cek permission Sales (role aktif).
 * Pemakaian: ->middleware('sales.permission:report.sales-person-list.view')
 *            ->middleware('sales.permission:email-scheduler-ckd.view,email-scheduler-ckd.manage')  // OR
 *
 * Harus dipasang setelah 'sales.auth:{type}'.
 */
class SalesPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (config('sales.auth_driver') === 'api') {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            if (sales_can($permission)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'code' => 403, 'message' => 'Forbidden'], 403);
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
