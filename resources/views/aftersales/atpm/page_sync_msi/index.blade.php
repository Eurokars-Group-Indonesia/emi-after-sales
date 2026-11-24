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
                <li class="breadcrumb-item active" aria-current="page">Monitoring Sync MSI (CSI)</li>
            </ol>
        </nav>
    </div>

    @unless ($hasSetting)
        <div class="alert alert-warning small">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>tblsetting</strong> masih kosong. Jalankan <code>php artisan db:seed --class=TblSettingSeeder</code> sebelum sync dijalankan.
        </div>
    @endunless

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
            <span class="afsel-stat-num sw-stat-sm">{{ $lastUpdate ? $lastUpdate->format('d-m-Y') : '-' }}</span>
            <span class="afsel-stat-label">last_update_csi</span>
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

    <div class="row g-3 mb-3">
        <div class="{{ $canRun ? 'col-xl-7' : 'col-12' }}">
            <div class="card h-100">
                <div class="card-header"><span class="afsel-section-title">Informasi Job</span></div>
                <div class="card-body">
                    <dl class="sw-dl mb-0">
                        <dt>Jadwal</dt>
                        <dd>{{ $cfg['schedule'] }} <code class="ms-1">{{ $cfg['cron'] }}</code></dd>
                        <dt>Sumber</dt>
                        <dd>Database WRS Connect After Sales — tblcustomer_log, tblcustomer, tblkpi, tblwork_planning, tblinventory, tblfaktur</dd>
                        <dt>Tujuan (MSI)</dt>
                        <dd>
                            @if ($connection)
                                <strong>{{ $connection->name }}</strong> — {{ $connection->hostname }} / {{ $connection->database }}
                                <span class="afsel-status {{ $connection->is_active ? 'is-on' : 'is-off' }} ms-1">{{ $connection->is_active ? 'Aktif' : 'Non-aktif' }}</span>
                                @if ($connection->driver !== 'sqlsrv')
                                    <span class="sw-text-fail small ms-1">Driver harus SQL Server</span>
                                @endif
                            @else
                                <span class="sw-text-fail">Koneksi {{ $cfg['connection'] }} belum diatur</span>
                            @endif
                            @if ($canConn)
                                <a href="{{ route('aftersales.atpm.sync_connection') }}" class="ms-1 small">Atur koneksi</a>
                            @endif
                            <div class="afsel-muted small">dbo.members — insert / update saja, <strong>tanpa hapus data</strong>.</div>
                        </dd>
                        <dt>Filter CSI (tblsetting.filter_csi)</dt>
                        <dd class="mb-0">
                            @if ($filterCsi)
                                <code class="sw-filter">{{ $filterCsi }}</code>
                            @else
                                <span class="afsel-muted">- (tanpa filter)</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        @if ($canRun)
            <div class="col-xl-5">
                <div class="card h-100">
                    <div class="card-header"><span class="afsel-section-title">Jalankan Manual</span></div>
                    <div class="card-body">
                        <form id="form-run" novalidate>
                            <div class="row g-2 align-items-end">
                                <div class="col-sm-6">
                                    <label class="form-label small" for="run-from">log_action_date dari</label>
                                    <input type="date" class="form-control form-control-sm" id="run-from" name="date_from" value="{{ $defaultFrom }}" max="{{ $defaultTo }}" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small" for="run-to">sampai</label>
                                    <input type="date" class="form-control form-control-sm" id="run-to" name="date_to" value="{{ $defaultTo }}" max="{{ $defaultTo }}" required>
                                </div>
                            </div>
                            <div class="form-text" id="run-hint"></div>
                            <div class="d-flex gap-2 mt-2">
                                <button type="button" class="btn-fi btn-fi-secondary btn-fi-sm" id="btn-default"><i class="bi bi-arrow-counterclockwise"></i> Pakai default</button>
                                <button type="button" class="btn-fi btn-fi-primary btn-fi-sm ms-auto" id="btn-run" @disabled($active)>
                                    <i class="bi bi-play-fill"></i> Jalankan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="sw-active mb-3 {{ $active ? '' : 'd-none' }}" id="active-box" role="status" aria-live="polite">
        <i class="bi bi-arrow-repeat sw-spin"></i>
        <span id="active-text">
            @if ($active)
                {{ $active->status === 'QUEUED' ? 'Menunggu scheduler menjalankan antrean #' . $active->id . ' ...' : 'Sync #' . $active->id . ' sedang berjalan ...' }}
            @endif
        </span>
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
                        <th>Durasi</th>
                        <th>Pemicu</th>
                        <th>Rentang log</th>
                        <th>Status</th>
                        <th class="text-end">Dibaca</th>
                        <th class="text-end">Insert</th>
                        <th class="text-end">Update</th>
                        <th class="text-end">Dilewati</th>
                        <th class="text-end">Gagal</th>
                        <th>Pesan</th>
                    </tr>
                </thead>
            </table>
            <div class="form-text mt-2">
                <i class="bi bi-info-circle"></i>
                Klik baris untuk melihat detail. <strong>Dilewati</strong> = dealer / user belum terdaftar di MSI.
                Jika ada baris dilewati / gagal, <code>uploaded_to_msi</code> &amp; <code>last_update_csi</code> tidak diubah (rentang yang sama dikirim ulang di jadwal berikutnya).
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
    .afsel-ui .sw-badge.sw-partial, .afsel-ui .sw-badge.sw-skipped-row { background: #fff4e0; color: #9a5b00; }
    .afsel-ui .sw-badge.sw-running, .afsel-ui .sw-badge.sw-queued { background: #e6f0ff; color: #1f5fbf; }
    .afsel-ui .sw-stat-sm { font-size: 16px; }
    .afsel-ui .sw-text-fail { color: var(--mz-red); }
    .afsel-ui .sw-text-warn { color: #9a5b00; }
    .afsel-ui .sw-dl dt { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: var(--mz-muted); font-weight: 600; }
    .afsel-ui .sw-dl dd { margin-bottom: 10px; }
    .afsel-ui .sw-filter { display: block; white-space: pre-wrap; word-break: break-word; font-size: 11.5px; background: var(--mz-bg-soft); border-radius: 6px; padding: 6px 8px; max-height: 110px; overflow: auto; }
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
    .sw-detail td, .sw-detail th { font-size: 12px; vertical-align: top; }
</style>

@include('aftersales.atpm.page_access._scripts')
<script>
    $(function () {
        const esc = s => $('<div>').text(s ?? '').html();
        const num = n => Number(n || 0).toLocaleString('id-ID');
        const badge = s => s ? `<span class="sw-badge sw-${esc(String(s).toLowerCase())}">${esc(s)}</span>` : '-';
        const urls = {
            datatable: '{{ route('aftersales.atpm.sync_msi.datatable') }}',
            status: '{{ route('aftersales.atpm.sync_msi.status') }}',
            run: '{{ route('aftersales.atpm.sync_msi.run') }}',
            show: '{{ url('aftersales/atpm/utility/sync-msi/log') }}',
        };
        let defaults = { from: '{{ $defaultFrom }}', to: '{{ $defaultTo }}' };

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
                { data: 'started_at', name: 'started_at', className: 'sw-col-dt', render: v => esc(v || '-') },
                { data: 'duration', name: 'duration_sec', orderable: false, searchable: false },
                { data: 'trigger_type', name: 'trigger_type', render: (v, t, r) => esc(v) + (r.trigger_type === 'manual' ? `<div class="afsel-muted small">${esc(r.triggered_by)}</div>` : '') },
                { data: 'range', name: 'date_from', searchable: false, render: (v, t, r) => esc(v) + (r.date_from && !Number(r.is_default_range) ? ' <span class="afsel-muted small">(manual)</span>' : '') },
                { data: 'status', name: 'status', render: v => badge(v) },
                { data: 'total_read', name: 'total_read', className: 'text-end', searchable: false, render: v => num(v) },
                { data: 'total_inserted', name: 'total_inserted', className: 'text-end', searchable: false, render: v => num(v) },
                { data: 'total_updated', name: 'total_updated', className: 'text-end', searchable: false, render: v => num(v) },
                { data: 'total_skipped', name: 'total_skipped', className: 'text-end', searchable: false, render: v => v > 0 ? `<span class="sw-text-warn fw-bold">${num(v)}</span>` : num(v) },
                { data: 'total_failed', name: 'total_failed', className: 'text-end', searchable: false, render: v => v > 0 ? `<span class="sw-text-fail fw-bold">${num(v)}</span>` : num(v) },
                { data: 'message', name: 'message', orderable: false, render: v => `<div class="sw-msg" title="${esc(v)}">${esc(v || '-')}</div>` },
            ],
            language: { search: 'Cari:', zeroRecords: 'Data tidak ditemukan', emptyTable: 'Belum ada riwayat sync', processing: 'Memuat ...' },
        });

        $('#filter-status').on('change', () => table.ajax.reload());
        $('#filter-date-from, #filter-date-to').on('change', function () {
            const from = $('#filter-date-from').val(), to = $('#filter-date-to').val();
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
            $('#modal-detail-title').text(`Detail Sync MSI #${row.id}`);
            $('#detail-body').html('<div class="afsel-muted">Memuat ...</div>');
            modal.show();

            axios.get(`${urls.show}/${row.id}`).then(res => {
                const log = res.data.log;
                let html = `<div class="d-flex flex-wrap gap-3 mb-2 small">
                    <div>Status: ${badge(log.status)}</div>
                    <div>Mulai: <strong>${esc(log.started_at || log.queued_at || '-')}</strong></div>
                    <div>Selesai: <strong>${esc(log.finished_at || '-')}</strong></div>
                    <div>Durasi: <strong>${esc(log.duration)}</strong></div>
                    <div>Pemicu: <strong>${esc(log.trigger_type)}</strong>${log.triggered_by ? ' (' + esc(log.triggered_by) + ')' : ''}</div>
                    <div>Host: <strong>${esc(log.host || '-')}</strong></div>
                </div>
                <div class="d-flex flex-wrap gap-3 mb-3 small">
                    <div>Rentang log: <strong>${esc(log.date_from || '-')} s/d ${esc(log.date_to || '-')}</strong>${Number(log.is_default_range) ? ' (default)' : ' (manual)'}</div>
                    <div>Dibaca: <strong>${num(log.total_read)}</strong></div>
                    <div>Insert: <strong>${num(log.total_inserted)}</strong></div>
                    <div>Update: <strong>${num(log.total_updated)}</strong></div>
                    <div>Dilewati: <strong>${num(log.total_skipped)}</strong></div>
                    <div>Gagal: <strong>${num(log.total_failed)}</strong></div>
                    <div>uploaded_to_msi: <strong>${num(log.kpi_flagged)}</strong></div>
                    <div>last_update_csi: <strong>${esc(log.last_update_before || '-')}</strong> → <strong>${esc(log.last_update_after || 'tidak berubah')}</strong></div>
                </div>`;
                if (log.message) html += `<div class="alert alert-light border small py-2">${esc(log.message)}</div>`;

                const details = res.data.details || [];
                if (details.length) {
                    html += `<div class="table-responsive"><table class="table table-sm sw-detail"><thead><tr>
                        <th>#</th><th>Status</th><th>VIN</th><th>Dealer</th><th>Customer</th><th>User log</th>
                        <th>Handover</th><th>Service</th><th>Aksi</th><th>Keterangan</th></tr></thead><tbody>`;
                    details.forEach(d => {
                        html += `<tr>
                            <td>${d.row_no}</td>
                            <td>${d.status === 'SKIPPED' ? '<span class="sw-badge sw-skipped-row">DILEWATI</span>' : badge(d.status)}</td>
                            <td><code>${esc(d.no_vin)}</code></td>
                            <td>${esc(d.fk_dealer)}</td>
                            <td>${esc(d.kd_customer)}</td>
                            <td>${esc(d.log_action_userid)}<div class="afsel-muted">${esc(d.log_action_date || '')}</div></td>
                            <td>${esc(d.handover_date || '-')}</td>
                            <td>${esc(d.reason_for_service || '-')}</td>
                            <td>${esc(d.action || '-')}</td>
                            <td class="${d.status === 'FAILED' ? 'sw-text-fail' : ''}">${esc(d.message || '')}</td>
                        </tr>`;
                    });
                    html += `</tbody></table></div>`;
                    if (res.data.detail_total > details.length) {
                        html += `<div class="form-text">Menampilkan ${num(details.length)} dari ${num(res.data.detail_total)} baris detail.</div>`;
                    }
                } else {
                    html += `<div class="afsel-muted small">Tidak ada baris yang dilewati / gagal.</div>`;
                }
                $('#detail-body').html(html);
            }).catch(() => $('#detail-body').html('<div class="sw-text-fail">Gagal memuat detail.</div>'));
        });

        // ---------- Jalankan manual ----------
        const $from = $('#run-from'), $to = $('#run-to');
        function hint() {
            const isDefault = $from.val() === defaults.from && $to.val() === defaults.to;
            $('#run-hint').html(isDefault
                ? 'Rentang default (last_update_csi s/d hari ini). Jika semua baris sukses, <code>last_update_csi</code> dimajukan ke hari ini.'
                : '<span class="sw-text-warn">Rentang manual: <code>last_update_csi</code> tidak diubah.</span> <code>uploaded_to_msi</code> tetap di-set jika semua baris sukses.');
            $to.attr('min', $from.val() || null);
            $from.attr('max', $to.val() || defaults.to);
        }
        $from.add($to).on('change', hint);
        $('#btn-default').on('click', () => { $from.val(defaults.from); $to.val(defaults.to); hint(); });
        hint();

        $('#btn-run').on('click', function () {
            const form = document.getElementById('form-run');
            if (!form.reportValidity()) return;
            if ($from.val() > $to.val()) {
                Swal.fire({ icon: 'warning', title: 'Rentang tidak valid', text: 'Tanggal mulai tidak boleh lebih besar dari tanggal akhir.' });
                return;
            }
            afselRequest({
                url: urls.run,
                data: { date_from: $from.val(), date_to: $to.val() },
                confirm: `Jalankan Sync MSI untuk log_action_date ${$from.val()} s/d ${$to.val()}? Proses berjalan di background lewat scheduler.`,
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
                        : `Sync #${s.active_id} sedang berjalan ... (${num(s.active_read)} baris dibaca)`);
                }
                if (s.last_status) $('#stat-last-status').html(badge(s.last_status));
                $('#stat-last-success').text(s.last_success ? s.last_success.slice(0, 16) : '-');

                // last_update_csi bisa maju setelah sync → perbarui nilai default form
                if (s.default_from && (s.default_from !== defaults.from || s.default_to !== defaults.to)) {
                    const wasDefault = $from.val() === defaults.from && $to.val() === defaults.to;
                    defaults = { from: s.default_from, to: s.default_to };
                    if (wasDefault) { $from.val(defaults.from); $to.val(defaults.to); }
                    hint();
                }

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
