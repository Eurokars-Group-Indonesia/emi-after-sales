<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleDealer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session('user.loginAs') !== 'dealer') {
            abort(403, 'Akses ditolak. Halaman ini hanya untuk user Dealer.');
        }

        return $next($request);
    }
}
