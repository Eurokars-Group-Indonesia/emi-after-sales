<?php

namespace App\Http\Controllers\Afsel;

use App\Http\Controllers\Controller;
use App\Services\SyncMsi\SyncMsiRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Yajra\DataTables\Facades\DataTables;

/**
 * Monitoring Sync WRS Connect After Sales → MSI (Customer CSI).
 */
class AfselSyncMsiController extends Controller
{
    /** Maksimal baris detail yang dikirim ke modal. */
    private const DETAIL_LIMIT = 500;

    public function __construct(private SyncMsiRunner $runner)
    {
    }

    public function index()
    {
        [$from, $to, $lastUpdate] = $this->runner->defaultRange();
        $setting = $this->runner->setting();

        return view('aftersales.atpm.page_sync_msi.index', [
            'cfg'         => config('sync_msi'),
            'connection'  => DB::table('ms_sync_db_connection')->where('name', config('sync_msi.connection'))
                ->first(['name', 'driver', 'hostname', 'database', 'is_active']),
            'nextRun'     => $this->runner->nextRunAt(),
            'summary'     => $this->summary(),
            'defaultFrom' => $from->toDateString(),
            'defaultTo'   => $to->toDateString(),
            'lastUpdate'  => $lastUpdate,
            'filterCsi'   => $setting?->filter_csi,
            'hasSetting'  => (bool) $setting,
            'canRun'      => afsel_can('sync-msi.run'),
            'canConn'     => afsel_can('sync-connection.view'),
        ]);
    }

    public function datatable(Request $request)
    {
        $query = DB::table(SyncMsiRunner::LOG)
            ->select(['id', 'trigger_type', 'triggered_by', 'status', 'date_from', 'date_to', 'is_default_range', 'queued_at',
                'started_at', 'finished_at', 'duration_sec', 'total_read', 'total_inserted', 'total_updated', 'total_skipped',
                'total_failed', 'kpi_flagged', 'last_update_after', 'message', 'date_created']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

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
            ->addColumn('range', fn ($r) => $r->date_from
                ? $this->fmtDate($r->date_from) . ' s/d ' . $this->fmtDate($r->date_to)
                : '-')
            ->addColumn('duration', fn ($r) => $this->duration($r->duration_sec))
            ->orderColumn('started_at', 'id $1')
            ->make(true);
    }

    public function show(int $id): JsonResponse
    {
        $log = DB::table(SyncMsiRunner::LOG)->where('id', $id)->first();
        if (!$log) {
            return response()->json(['status' => false, 'message' => 'Log tidak ditemukan.'], 404);
        }

        $detailQuery = DB::table(SyncMsiRunner::DETAIL)->where('log_id', $id);
        $detailTotal = (clone $detailQuery)->count();
        $details     = $detailQuery->orderBy('row_no')->limit(self::DETAIL_LIMIT)->get()
            ->map(function ($d) {
                $d->log_action_date = $this->fmt($d->log_action_date);
                $d->handover_date   = $this->fmtDate($d->handover_date);

                return $d;
            });

        $log->duration           = $this->duration($log->duration_sec);
        $log->started_at         = $this->fmt($log->started_at);
        $log->finished_at        = $this->fmt($log->finished_at);
        $log->queued_at          = $this->fmt($log->queued_at);
        $log->date_from          = $this->fmtDate($log->date_from);
        $log->date_to            = $this->fmtDate($log->date_to);
        $log->last_update_before = $this->fmt($log->last_update_before);
        $log->last_update_after  = $this->fmt($log->last_update_after);

        return response()->json([
            'status'       => true,
            'log'          => $log,
            'details'      => $details,
            'detail_total' => $detailTotal,
            'detail_limit' => self::DETAIL_LIMIT,
        ]);
    }

    public function status(): JsonResponse
    {
        $summary = $this->summary();
        $active  = $summary['active'];
        [$from, $to] = $this->runner->defaultRange();

        return response()->json([
            'status'  => true,
            'summary' => [
                'active_status' => $active->status ?? null,
                'active_id'     => $active->id ?? null,
                'active_read'   => (int) ($active->total_read ?? 0),
                'last_status'   => $summary['last']->status ?? null,
                'last_success'  => $this->fmt($summary['lastSuccess']->finished_at ?? null),
                'default_from'  => $from->toDateString(),
                'default_to'    => $to->toDateString(),
            ],
        ]);
    }

    public function run(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to'   => ['nullable', 'date_format:Y-m-d'],
        ], [
            'date_from.date_format' => 'Tanggal mulai tidak valid.',
            'date_to.date_format'   => 'Tanggal akhir tidak valid.',
        ]);

        try {
            $id = $this->runner->requestManual(
                (string) (session('user.id') ?: 'unknown'),
                $request->input('date_from') ?: null,
                $request->input('date_to') ?: null,
            );
        } catch (RuntimeException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage(), 'errors' => ''], 422);
        }

        $log = DB::table(SyncMsiRunner::LOG)->where('id', $id)->first(['date_from', 'date_to', 'is_default_range']);

        return response()->json([
            'status'  => true,
            'message' => "Permintaan Sync MSI masuk antrean (#{$id}) untuk {$this->fmtDate($log->date_from)} s/d {$this->fmtDate($log->date_to)}"
                . ($log->is_default_range ? '' : ' (rentang manual: last_update_csi tidak akan diubah)')
                . '. Scheduler akan menjalankannya dalam ±1 menit.',
            'errors'  => '',
        ]);
    }

    private function summary(): array
    {
        $base = fn () => DB::table(SyncMsiRunner::LOG);

        return [
            'active'      => $base()->whereIn('status', [SyncMsiRunner::RUNNING, SyncMsiRunner::QUEUED])->orderByDesc('id')->first(),
            'last'        => $base()->whereNotIn('status', [SyncMsiRunner::RUNNING, SyncMsiRunner::QUEUED])->orderByDesc('id')->first(),
            'lastSuccess' => $base()->where('status', SyncMsiRunner::SUCCESS)->where('trigger_type', '!=', 'dry-run')->orderByDesc('id')->first(),
            'last7'       => $base()->where('date_created', '>=', now()->subDays(7))
                ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
        ];
    }

    private function fmt($value): ?string
    {
        return $value ? Carbon::parse($value)->format('d-m-Y H:i:s') : null;
    }

    private function fmtDate($value): ?string
    {
        return $value ? Carbon::parse($value)->format('d-m-Y') : null;
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
