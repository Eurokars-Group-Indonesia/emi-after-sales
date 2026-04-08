<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class CheckSyncRunning
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isRunning = DB::table('sync_logs')
                        ->where('status', 'RUNNING')
                        ->where('job_name', 'sync_pentaho')
                        ->exists();

        if ($isRunning) {

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sedang sync data'
                ], 503);
            }

            // return redirect()->back()->with('information', 'Sedang ada proses sinkronisasi data');
            // return redirect()->route('atpm.utility.sync_information')->with('sync_running', 'Sedang ada proses sinkronisasi');
            return redirect()->route('atpm.utility.sync_information')->with('information', 'Sedang ada proses sinkronisasi data');
        }

        return $next($request);
    }
}
