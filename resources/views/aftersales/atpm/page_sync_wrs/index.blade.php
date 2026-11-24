@extends('aftersales.atpm.layouts.app')

@section('title', 'Monitoring ' . $cfg['label'])

@section('navtop')
    {{ view('aftersales.atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('aftersales.atpm.layouts.sidebar') }}
@endsection

@php
    $last        = $summary['last'];
    $lastSuccess = $summary['lastSuccess'];
    $active      = $summary['active'];
    $last7       = $summary['last7'];
@endphp

@section('content')
<div class="content afsel-ui">
    <div class="page-header flex-wrap gap-2">
        <div class="page-title">Monitoring {{ $cfg['label'] }}</div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('aftersales.atpm.home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript:void(0)">Utility</a></li>
                <li class="breadcrumb-item"><a href="javascript:void(0)">Monitoring Sync WRS Sales</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $cfg['label'] }}</li>
            </ol>
        </nav>
    </div>

    <div class="afsel-stats">
        <div class="afsel-stat">
            <span class="afsel-stat-num" id="stat-last-status">{!! $last ? '<span class="sw-badge sw-' . strtolower($last->status) . '">' . e($last->status) . '</span>' : '-' !!}</span>
            <span class="afsel-stat-label">Status terakhir</span>
        </div>
        <div class="afsel-stat">
            <span class="afsel-stat-num sw-stat-sm" id="stat-last-success">{{ $lastSuccess?->finished_at ? \Carbon\Carbon::parse($lastSuccess->finished_at)->format('d-m-Y H:i') : '-' }}</span>
            <span class="afsel-stat-label">Sukses terakhir</span>
        </div>
        <div class="afsel-stat">
            <span class="afsel-stat-num sw-stat-sm">{{ $nextRun ? $nextRun->format('d-m-Y H:i') : '-' }}</span>
            <span class="afsel-stat-label">Jadwal berikutnya</span>
        </div>
        <div class="afsel-stat">
            <span class="afsel-stat-num sw-stat-sm">
                <span class="text-success">{{ (int) ($last7['SUCCESS'] ?? 0) }}</span> /
                <span class="sw-text-warn">{{ (int) ($last7['PARTIAL'] ?? 0) }}</span> /
                <span class="sw-text-fail">{{ (int) ($last7['FAILED'] ?? 0) }}</span>
            </span>
            <span class="afsel-stat-label">7 hari: sukses / sebagian / gagal</span>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">
            <span class="afsel-section-title">Informasi Job</span>
            @if ($canRun)
                <button class="btn-fi btn-fi-primary btn-fi-sm ms-auto" id="btn-run" @disabled($active)>
                    <i class="bi bi-play-fill"></i> Jalankan Sekarang
                </button>
            @endif
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-5">
                    <dl class="sw-dl mb-0">
                        <dt>Jadwal</dt>
                        <dd>{{ $cfg['schedule'] }} <code class="ms-1">{{ $cfg['cron'] }}</code></dd>
                        <dt>Koneksi sumber</dt>
                        <dd>
                            @if ($connection)
                                <strong>{{ $connection->name }}</strong> — {{ $connection->hostname }} / {{ $connection->database }}
                                <span class="afsel-status {{ $connection->is_active ? 'is-on' : 'is-off' }} ms-1">{{ $connection->is_active ? 'Aktif' : 'Non-aktif' }}</span>
                            @else
                                <span class="sw-text-fail">{{ $cfg['connection'] }} belum diatur</span>
                            @endif
                            @if ($canConn)
                                <a href="{{ route('aftersales.atpm.sync_connection') }}" class="ms-1 small">Atur koneksi</a>
                            @endif
                        </dd>
                        <dt>Target</dt>
                        <dd>Database WRS Connect After Sales (insert / update saja, tanpa hapus data)</dd>
                    </dl>
                </div>
                <div class="col-lg-7">
                    <table class="table table-sm mb-0 sw-steps">
                        <thead>
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Tabel</th>
                                <th>Script lama</th>
                                <th style="width:120px" class="text-center">Proses aktif</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($steps as $i => $step)
                                <tr data-step="{{ $step['code'] }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td><strong>{{ $step['label'] ?? $step['code'] }}</strong> <span class="afsel-muted small">{{ $step['source'] ?? '' }} → {{ $step['target'] ?? '' }}</span></td>
                                    <td><code>{{ $step['legacy'] ?? '-' }}</code></td>
                                    <td class="text-center sw-step-live"><span class="afsel-muted">-</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="sw-active mt-3 {{ $active ? '' : 'd-none' }}" id="active-box" role="status" aria-live="polite">
                <i class="bi bi-arrow-repeat sw-spin"></i>
                <span id="active-text">
                    @if ($active)
                        {{ $active->status === 'QUEUED' ? 'Menunggu scheduler menjalankan antrean #' . $active->id . ' ...' : 'Sync #' . $active->id . ' sedang berjalan ...' }}
                    @endif
                </span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="afsel-toolbar">
            <span class="afsel-section-title me-auto">Riwayat Eksekusi</span>
            <div class="d-flex align-items-center gap-1">
                <label class="small afsel-muted text-nowrap" for="filter-date-from">Tanggal mulai</label>
                <input type="date" id="filter-date-from" class="form-control form-control-sm" style="width:auto" aria-label="Tanggal mulai dari">
                <span class="small afsel-muted">s/d</span>
                <input type="date" id="filter-date-to" class="form-control form-control-sm" style="width:auto" aria-label="Tanggal mulai sampai">
                <button type="button" class="btn btn-sm btn-link px-1" id="filter-date-reset" title="Reset tanggal" aria-label="Reset tanggal"><i class="bi bi-x-circle"></i></button>
            </div>
            <label class="visually-hidden" for="filter-status">Filter status</label>
            <select id="filter-status" class="form-select form-select-sm" style="width:auto">
                <option value="">Semua status</option>
                @foreach (['SUCCESS', 'PARTIAL', 'FAILED', 'RUNNING', 'QUEUED', 'SKIPPED'] as $s)
                    <option value="{{ $s }}">{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="card-body">
            <table id="table-log" class="table table-hover nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:60px">ID</th>
                        <th class="sw-col-dt">Mulai</th>
                        <th class="sw-col-dt">Selesai</th>
                        <th>Durasi</th>
                        <th>Pemicu</th>
                        <th>Status</th>
                        <th class="text-end">Dibaca</th>
                        <th class="text-end">Insert</th>
                        <th class="text-end">Update</th>
                        <th class="text-end">Gagal</th>
                        <th>Pesan</th>
                    </tr>
                </thead>
            </table>
            <div class="form-text mt-2">
                <i class="bi bi-info-circle"></i>
                Klik baris untuk melihat detail per tabel. <strong>PARTIAL</strong> = sebagian baris/tabel gagal, baris lain tetap tersimpan.
                <strong>SKIPPED</strong> = jadwal dilewati karena proses sebelumnya masih berjalan.
            </div>
        </div>
    </div>
</div>

<div class="modal fade afsel-ui" id="modal-detail" tabindex="-1" aria-labelledby="modal-detail-title">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="afsel-eyebrow">{{ $cfg['label'] }}</div>
                    <h6 class="modal-title" id="modal-detail-title">Detail Sync</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body" id="detail-body">
                <div class="afsel-muted">Memuat ...</div>
            </div>
        </div>
    </div>
</div>

<style>
    .afsel-ui .sw-badge { display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: .04em; padding: 2px 9px; border-radius: 999px; background: #eef0f3; color: #4b535c; }
    .afsel-ui .sw-badge.sw-success { background: var(--mz-ok-tint); color: var(--mz-ok); }
    .afsel-ui .sw-badge.sw-failed { background: var(--mz-red-tint); color: var(--mz-red); }
    .afsel-ui .sw-badge.sw-partial { background: #fff4e0; color: #9a5b00; }
    .afsel-ui .sw-badge.sw-running { background: #e6f0ff; color: #1f5fbf; }
    .afsel-ui .sw-stat-sm { font-size: 16px; }
    .afsel-ui .sw-text-fail { color: var(--mz-red); }
    .afsel-ui .sw-text-warn { color: #9a5b00; }
    .afsel-ui .sw-dl dt { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: var(--mz-muted); font-weight: 600; }
    .afsel-ui .sw-dl dd { margin-bottom: 10px; }
    .afsel-ui .sw-steps td, .afsel-ui .sw-steps th { font-size: 12.5px; vertical-align: middle; }
    .afsel-ui .sw-active { background: #e6f0ff; color: #1f5fbf; border-radius: 8px; padding: 8px 12px; font-size: 13px; }
    .afsel-ui .sw-spin { display: inline-block; animation: sw-spin 1.2s linear infinite; }
    @keyframes sw-spin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) { .afsel-ui .sw-spin { animation: none; } }
    #table-log tbody tr { cursor: pointer; }
    #table-log td { font-size: 12.5px; }
    #table-log th, #table-log td { white-space: nowrap; }
    .sw-col-dt { min-width: 150px; }
    .afsel-ui .afsel-toolbar { flex-wrap: wrap; gap: 8px; }
    .sw-msg { max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sw-errors { font-size: 12px; background: var(--mz-bg-soft); border-radius: 6px; padding: 8px 10px; margin-top: 6px; }
</style>

@include('aftersales.atpm.page_access._scripts')
<script>
    $(function () {
        const esc = s => $('<div>').text(s ?? '').html();
        const num = n => Number(n || 0).toLocaleString('id-ID');
        const badge = s => s ? `<span class="sw-badge sw-${esc(String(s).toLowerCase())}">${esc(s)}</span>` : '-';
        const urls = {
            datatable: '{{ route('aftersales.atpm.sync_wrs.datatable', $job) }}',
            status: '{{ route('aftersales.atpm.sync_wrs.status', $job) }}',
            run: '{{ route('aftersales.atpm.sync_wrs.run', $job) }}',
            show: '{{ url('aftersales/atpm/utility/sync-wrs/log') }}',
        };

        const table = $('#table-log').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            order: [[0, 'desc']],
            scrollX: true,
            autoWidth: false,
            ajax: { url: urls.datatable, data: d => Object.assign(d, {
                status: $('#filter-status').val(),
                date_from: $('#filter-date-from').val(),
                date_to: $('#filter-date-to').val(),
            }) },
            columns: [
                { data: 'id', name: 'id', width: '60px' },
                { data: 'started_at', name: 'started_at', width: '150px', className: 'sw-col-dt', render: v => esc(v || '-') },
                { data: 'finished_at', name: 'finished_at', width: '150px', className: 'sw-col-dt', render: v => esc(v || '-') },
                { data: 'duration', name: 'duration_sec', orderable: false, searchable: false },
                { data: 'trigger_type', name: 'trigger_type', render: (v, t, r) => esc(v) + (r.trigger_type === 'manual' ? `<div class="afsel-muted small">${esc(r.triggered_by)}</div>` : '') },
                { data: 'status', name: 'status', render: v => badge(v) },
                { data: 'total_read', name: 'total_read', className: 'text-end', searchable: false, render: v => num(v) },
                { data: 'total_inserted', name: 'total_inserted', className: 'text-end', searchable: false, render: v => num(v) },
                { data: 'total_updated', name: 'total_updated', className: 'text-end', searchable: false, render: v => num(v) },
                { data: 'total_failed', name: 'total_failed', className: 'text-end', searchable: false, render: v => v > 0 ? `<span class="sw-text-fail fw-bold">${num(v)}</span>` : num(v) },
                { data: 'message', name: 'message', orderable: false, render: v => `<div class="sw-msg" title="${esc(v)}">${esc(v || '-')}</div>` },
            ],
            language: { search: 'Cari:', zeroRecords: 'Data tidak ditemukan', emptyTable: 'Belum ada riwayat sync', processing: 'Memuat ...' },
        });

        $('#filter-status').on('change', () => table.ajax.reload());
        $('#filter-date-from, #filter-date-to').on('change', function () {
            const from = $('#filter-date-from').val(), to = $('#filter-date-to').val();
            // Jaga rentang tetap valid: "sampai" tidak boleh lebih awal dari "dari".
            $('#filter-date-to').attr('min', from || null);
            $('#filter-date-from').attr('max', to || null);
            if (from && to && to < from) $(this).is('#filter-date-from') ? $('#filter-date-to').val(from) : $('#filter-date-from').val(to);
            table.ajax.reload();
        });
        $('#filter-date-reset').on('click', () => {
            $('#filter-date-from, #filter-date-to').val('').removeAttr('min max');
            table.ajax.reload();
        });

        // ---------- Detail ----------
        const modal = new bootstrap.Modal(document.getElementById('modal-detail'));
        $('#table-log tbody').on('click', 'tr', function () {
            const row = table.row(this).data();
            if (!row) return;
            $('#modal-detail-title').text(`Detail Sync #${row.id}`);
            $('#detail-body').html('<div class="afsel-muted">Memuat ...</div>');
            modal.show();

            axios.get(`${urls.show}/${row.id}`).then(res => {
                const log = res.data.log;
                let html = `<div class="d-flex flex-wrap gap-3 mb-3 small">
                    <div>Status: ${badge(log.status)}</div>
                    <div>Mulai: <strong>${esc(log.started_at || log.queued_at || '-')}</strong></div>
                    <div>Selesai: <strong>${esc(log.finished_at || '-')}</strong></div>
                    <div>Durasi: <strong>${esc(log.duration)}</strong></div>
                    <div>Pemicu: <strong>${esc(log.trigger_type)}</strong>${log.triggered_by ? ' (' + esc(log.triggered_by) + ')' : ''}</div>
                    <div>Host: <strong>${esc(log.host || '-')}</strong></div>
                </div>`;
                if (log.message) html += `<div class="alert alert-light border small py-2">${esc(log.message)}</div>`;

                html += `<div class="table-responsive"><table class="table table-sm align-middle"><thead><tr>
                    <th>#</th><th>Tabel</th><th>Status</th><th class="text-end">Dibaca</th><th class="text-end">Insert</th>
                    <th class="text-end">Update</th><th class="text-end">Sama</th><th class="text-end">Skip</th><th class="text-end">Gagal</th><th>Durasi</th>
                    </tr></thead><tbody>`;
                if (!res.data.details.length) {
                    html += `<tr><td colspan="10" class="afsel-muted">Tidak ada detail (job berhenti sebelum memproses tabel).</td></tr>`;
                }
                res.data.details.forEach(d => {
                    html += `<tr>
                        <td>${d.step_order}</td>
                        <td><strong>${esc(d.label)}</strong><div class="afsel-muted small">${esc(d.source_table)} → ${esc(d.target_table)} · ${esc(d.legacy_script || '')}</div></td>
                        <td>${badge(d.status)}</td>
                        <td class="text-end">${num(d.rows_read)}</td><td class="text-end">${num(d.rows_inserted)}</td>
                        <td class="text-end">${num(d.rows_updated)}</td><td class="text-end">${num(d.rows_unchanged)}</td>
                        <td class="text-end">${num(d.rows_skipped)}</td>
                        <td class="text-end">${d.rows_failed > 0 ? `<span class="sw-text-fail fw-bold">${num(d.rows_failed)}</span>` : num(d.rows_failed)}</td>
                        <td>${esc(d.duration)}</td></tr>`;
                    if (d.message || d.error_samples.length) {
                        html += `<tr><td></td><td colspan="9">`;
                        if (d.message) html += `<div class="small ${d.status === 'FAILED' ? 'sw-text-fail' : 'sw-text-warn'}">${esc(d.message)}</div>`;
                        if (d.error_samples.length) {
                            html += `<div class="sw-errors">` + d.error_samples.map(e =>
                                `<div><code>${esc(e.key)}</code> (${esc(e.action)}): ${esc(e.error)}</div>`).join('') + `</div>`;
                        }
                        html += `</td></tr>`;
                    }
                });
                html += `</tbody></table></div>
                    <div class="form-text">Skip = baris berstatus Canceled yang belum ada di target (tidak di-insert). Sama = tidak ada perubahan.</div>`;
                $('#detail-body').html(html);
            }).catch(() => $('#detail-body').html('<div class="sw-text-fail">Gagal memuat detail.</div>'));
        });

        // ---------- Jalankan sekarang ----------
        $('#btn-run').on('click', function () {
            afselRequest({
                url: urls.run,
                confirm: 'Jalankan {{ $cfg['label'] }} sekarang? Proses berjalan di background lewat scheduler.',
                reload: false,
            }).then(r => { if (r) { poll(); table.ajax.reload(null, false); } });
        });

        // ---------- Polling status saat ada proses aktif ----------
        let timer = null;
        function poll() {
            clearTimeout(timer);
            axios.get(urls.status).then(res => {
                const s = res.data.summary;
                const active = !!s.active_status;

                $('#active-box').toggleClass('d-none', !active);
                $('#btn-run').prop('disabled', active);
                if (active) {
                    $('#active-text').text(s.active_status === 'QUEUED'
                        ? `Menunggu scheduler menjalankan antrean #${s.active_id} ...`
                        : `Sync #${s.active_id} sedang berjalan ...`);
                }

                $('.sw-step-live').html('<span class="afsel-muted">-</span>');
                (res.data.steps || []).forEach(st => {
                    const cell = $(`tr[data-step="${st.step_code}"] .sw-step-live`);
                    cell.html(st.status === 'RUNNING'
                        ? `<span class="sw-badge sw-running">${num(st.rows_read)} baris</span>`
                        : badge(st.status));
                });

                if (s.last_status) $('#stat-last-status').html(badge(s.last_status));
                $('#stat-last-success').text(s.last_success ? s.last_success.slice(0, 16) : '-');

                if (active) {
                    timer = setTimeout(poll, 5000);
                } else if (poll.wasActive) {
                    table.ajax.reload(null, false);
                }
                poll.wasActive = active;
            }).catch(() => { timer = setTimeout(poll, 15000); });
        }
        poll();
    });
</script>
@endsection
