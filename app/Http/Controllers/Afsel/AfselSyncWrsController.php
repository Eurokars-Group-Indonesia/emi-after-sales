<?php

namespace App\Http\Controllers\Afsel;

use App\Http\Controllers\Controller;
use App\Services\SyncWrs\SyncWrsRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Yajra\DataTables\Facades\DataTables;

/**
 * Monitoring Sync WRS Sales → WRS Connect After Sales (per job: inventory / faktur).
 */
class AfselSyncWrsController extends Controller
{
    public function __construct(private SyncWrsRunner $runner)
    {
    }

    public function index(string $job)
    {
        $cfg   = $this->runner->job($job);
        $steps = collect($cfg['steps'])->map(fn ($code) => ['code' => $code] + config("sync_wrs.steps.{$code}", []));

        $connection = DB::table('ms_sync_db_connection')->where('name', $cfg['connection'])->first(['name', 'hostname', 'database', 'is_active']);

        return view('aftersales.atpm.page_sync_wrs.index', [
            'job'        => $job,
            'cfg'        => $cfg,
            'steps'      => $steps,
            'connection' => $connection,
            'nextRun'    => $this->runner->nextRunAt($job),
            'summary'    => $this->summary($job),
            'canRun'     => afsel_can('sync-wrs.run'),
            'canConn'    => afsel_can('sync-connection.view'),
        ]);
    }

    public function datatable(Request $request, string $job)
    {
        $query = DB::table(SyncWrsRunner::LOG)->where('job_code', $job)
            ->select(['id', 'trigger_type', 'triggered_by', 'status', 'queued_at', 'started_at', 'finished_at', 'duration_sec',
                'total_read', 'total_inserted', 'total_updated', 'total_unchanged', 'total_skipped', 'total_failed', 'message', 'date_created']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter tanggal mulai (sama dengan kolom "Mulai": started_at → queued_at → date_created).
        $startExpr = 'COALESCE(started_at, queued_at, date_created)';
        if ($from = $this->parseDate($request->input('date_from'))) {
            $query->whereRaw("{$startExpr} >= ?", [$from->startOfDay()->toDateTimeString()]);
        }
        if ($to = $this->parseDate($request->input('date_to'))) {
            $query->whereRaw("{$startExpr} < ?", [$to->addDay()->startOfDay()->toDateTimeString()]);
        }

        return DataTables::of($query)
            ->editColumn('started_at', fn ($r) => $this->fmt($r->started_at ?? $r->queued_at ?? $r->date_created))
            ->editColumn('finished_at', fn ($r) => $this->fmt($r->finished_at))
            ->editColumn('triggered_by', fn ($r) => $r->triggered_by ?? '-')
            ->addColumn('duration', fn ($r) => $this->duration($r->duration_sec))
            ->orderColumn('started_at', 'id $1')
            ->make(true);
    }

    public function show(int $id): JsonResponse
    {
        $log = DB::table(SyncWrsRunner::LOG)->where('id', $id)->first();
        if (!$log) {
            return response()->json(['status' => false, 'message' => 'Log tidak ditemukan.'], 404);
        }

        $details = DB::table(SyncWrsRunner::DETAIL)->where('log_id', $id)->orderBy('step_order')->get()
            ->map(function ($d) {
                $d->label         = config("sync_wrs.steps.{$d->step_code}.label", $d->step_code);
                $d->duration      = $this->duration($d->duration_sec);
                $d->error_samples = $d->error_samples ? json_decode($d->error_samples, true) : [];
                $d->started_at    = $this->fmt($d->started_at);
                $d->finished_at   = $this->fmt($d->finished_at);

                return $d;
            });

        $log->duration    = $this->duration($log->duration_sec);
        $log->started_at  = $this->fmt($log->started_at);
        $log->finished_at = $this->fmt($log->finished_at);
        $log->queued_at   = $this->fmt($log->queued_at);

        return response()->json(['status' => true, 'log' => $log, 'details' => $details]);
    }

    /** Ringkasan untuk polling halaman (kartu status + progress step yang sedang jalan). */
    public function status(string $job): JsonResponse
    {
        $summary = $this->summary($job);
        $active  = $summary['active'];

        return response()->json([
            'status'  => true,
            'summary' => [
                'active_status' => $active->status ?? null,
                'active_id'     => $active->id ?? null,
                'last_status'   => $summary['last']->status ?? null,
                'last_finished' => $this->fmt($summary['last']->finished_at ?? null),
                'last_success'  => $this->fmt($summary['lastSuccess']->finished_at ?? null),
            ],
            'steps' => $active
                ? DB::table(SyncWrsRunner::DETAIL)->where('log_id', $active->id)->orderBy('step_order')
                    ->get(['step_code', 'status', 'rows_read', 'rows_inserted', 'rows_updated', 'rows_failed'])
                : [],
        ]);
    }

    public function run(string $job): JsonResponse
    {
        try {
            $id = $this->runner->requestManual($job, (string) (session('user.id') ?: 'unknown'));
        } catch (RuntimeException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage(), 'errors' => ''], 422);
        }

        return response()->json([
            'status'  => true,
            'message' => "Permintaan sync masuk antrean (#{$id}). Scheduler akan menjalankannya dalam ±1 menit.",
            'errors'  => '',
        ]);
    }

    private function summary(string $job): array
    {
        $base = fn () => DB::table(SyncWrsRunner::LOG)->where('job_code', $job);

        return [
            'active'      => $base()->whereIn('status', [SyncWrsRunner::RUNNING, SyncWrsRunner::QUEUED])->orderByDesc('id')->first(),
            'last'        => $base()->whereNotIn('status', [SyncWrsRunner::RUNNING, SyncWrsRunner::QUEUED])->orderByDesc('id')->first(),
            'lastSuccess' => $base()->where('status', SyncWrsRunner::SUCCESS)->where('trigger_type', '!=', 'dry-run')->orderByDesc('id')->first(),
            'last7'       => $base()->where('date_created', '>=', now()->subDays(7))
                ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
        ];
    }

    private function fmt($value): ?string
    {
        return $value ? Carbon::parse($value)->format('d-m-Y H:i:s') : null;
    }

    private function parseDate($value): ?Carbon
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function duration(?int $sec): string
    {
        if ($sec === null) {
            return '-';
        }

        return $sec < 60 ? "{$sec}s" : ($sec < 3600
            ? intdiv($sec, 60) . 'm ' . ($sec % 60) . 's'
            : intdiv($sec, 3600) . 'h ' . intdiv($sec % 3600, 60) . 'm');
    }
}
