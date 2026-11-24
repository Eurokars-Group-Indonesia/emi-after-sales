@extends('aftersales.atpm.layouts.app')

@section('title', 'Dealer Personel After Sales')

@section('navtop')
    {{ view('aftersales.atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('aftersales.atpm.layouts.sidebar') }}
@endsection

@section('content')
<div class="content afsel-ui">
    <div class="page-header flex-wrap gap-2">
        <div class="page-title">Dealer Personel After Sales</div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('aftersales.atpm.home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript:void(0)">Personel After Sales</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dealer Personel</li>
            </ol>
        </nav>
    </div>

    <div class="afsel-stats">
        <div class="afsel-stat"><span class="afsel-stat-num">{{ number_format($stats->total) }}</span><span class="afsel-stat-label">Total personel</span></div>
        <div class="afsel-stat"><span class="afsel-stat-num" id="stat-active">{{ number_format($stats->active) }}</span><span class="afsel-stat-label">Aktif</span></div>
        <div class="afsel-stat"><span class="afsel-stat-num" id="stat-blacklist">{{ number_format($stats->blacklist) }}</span><span class="afsel-stat-label">Blacklist</span></div>
        <div class="afsel-stat"><span class="afsel-stat-num">{{ number_format($stats->dealers) }}</span><span class="afsel-stat-label">Dealer</span></div>
    </div>

    {{-- Sync dari WRS --}}
    <div class="card mb-3 dp-sync">
        <div class="card-body d-flex align-items-center flex-wrap gap-3">
            <div class="dp-sync-icon" aria-hidden="true"><i class="bi bi-arrow-repeat"></i></div>
            <div class="flex-grow-1">
                <div class="afsel-section-title">Sync dari WRS After Sales</div>
                <div class="small text-muted">
                    @if ($lastSync)
                        Terakhir sync {{ \Carbon\Carbon::parse($lastSync['synced_at'])->format('d M Y H:i') }}:
                        {{ number_format($lastSync['inserted']) }} ditambahkan, {{ number_format($lastSync['updated']) }} diperbarui{{ count($lastSync['skipped_level']) ? ', ' . count($lastSync['skipped_level']) . ' dilewati' : '' }}.
                    @else
                        Belum pernah sync. Data diambil dari <code>master_aftersales.tbldealer_personil_aftersales</code>.
                    @endif
                </div>
            </div>
            @if ($canSync)
                <button class="btn-fi btn-fi-primary btn-fi-sm" id="btn-sync">
                    <i class="bi bi-arrow-repeat"></i> Sync dari WRS
                </button>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="afsel-toolbar">
            <span class="afsel-section-title me-auto">Daftar Personel</span>
            <select class="form-select form-select-sm dp-filter" id="filter-dealer" aria-label="Filter dealer (hanya dealer aktif)">
                <option value="">Semua dealer</option>
                @foreach ($dealers as $d)
                    <option value="{{ $d->kd_dealer }}">{{ $d->nm_dealer }}</option>
                @endforeach
            </select>
            <select class="form-select form-select-sm dp-filter" id="filter-level" aria-label="Filter level">
                <option value="">Semua level</option>
                @foreach ($levels as $l)
                    <option value="{{ $l->dealer_level_personel_id }}">{{ $l->dealer_level_personel }}</option>
                @endforeach
            </select>
            <select class="form-select form-select-sm dp-filter" id="filter-status" aria-label="Filter status">
                <option value="">Semua status</option>
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
                <option value="blacklist">Blacklist</option>
            </select>
        </div>
        <div class="card-body">
            <table id="table-personel" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>NIK / No KTP</th>
                        <th>Nama</th>
                        <th>Level</th>
                        <th>Dealer</th>
                        <th style="width:100px">Tgl masuk</th>
                        <th style="width:170px">Status</th>
                        @if ($canEdit)
                            <th class="text-end" style="width:60px">Aksi</th>
                        @endif
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Modal detail / ubah personel --}}
<div class="modal fade afsel-ui" id="modal-personel" tabindex="-1" aria-labelledby="dp-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header dp-hero">
                <div class="dp-hero-inner" id="dp-head"></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body" id="dp-body"></div>
            <div class="modal-footer" id="dp-footer"></div>
        </div>
    </div>
</div>

<style>
    .afsel-ui.modal .dp-hero { padding: 26px 26px 22px; }
    .dp-hero-inner { display: flex; gap: 20px; align-items: flex-end; width: 100%; min-width: 0; }
    .dp-photo {
        flex: 0 0 116px; width: 116px; height: 144px; border-radius: 14px; overflow: hidden;
        background: linear-gradient(160deg, var(--mz-red), #6d0f1f);
        box-shadow: 0 0 0 3px rgba(255, 255, 255, .9), 0 10px 24px rgba(0, 0, 0, .45);
        display: flex; align-items: center; justify-content: center;
        font-size: 38px; font-weight: 700; letter-spacing: .04em; color: rgba(255, 255, 255, .95);
    }
    .dp-photo.afsel-skel { box-shadow: none; }
    .dp-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .dp-name { font-size: 21px; font-weight: 700; line-height: 1.2; margin: 4px 0 2px; color: #fff; word-break: break-word; }
    .dp-nick { font-size: 13px; color: var(--mz-silver); }
    .dp-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
    .dp-chip {
        display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600;
        padding: 3px 10px; border-radius: 999px; background: rgba(255, 255, 255, .1); color: #fff;
        border: 1px solid rgba(255, 255, 255, .18);
    }
    .dp-chip.is-red { background: var(--mz-red); border-color: var(--mz-red); }
    .dp-chip.is-ok::before, .dp-chip.is-off::before { content: ''; width: 7px; height: 7px; border-radius: 50%; }
    .dp-chip.is-ok::before { background: #3ddc84; }
    .dp-chip.is-off::before { background: #9aa1a9; }
    .afsel-ui .card.dp-sync { border-top: 0; border-left: 4px solid var(--mz-red); }
    .dp-sync-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(166, 25, 46, .1); color: var(--mz-red); display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .dp-result-list { text-align: left; font-size: 13px; max-height: 180px; overflow-y: auto; margin: 6px 0 0; padding-left: 18px; }
    @media (max-width: 576px) { .dp-hero-inner { flex-direction: column; align-items: flex-start; } }
</style>

<script>
    $(function () {
        const esc = s => $('<div>').text(s ?? '').html();

        const table = $('#table-personel').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            order: [[1, 'asc']],
            ajax: {
                url: '{{ route('aftersales.atpm.dealer_personel.datatable') }}',
                data: d => Object.assign(d, {
                    fk_dealer: $('#filter-dealer').val(),
                    level: $('#filter-level').val(),
                    status: $('#filter-status').val(),
                }),
            },
            columns: [
                {
                    data: 'nik', name: 'nik',
                    render: (v, t, r) => esc(v) + (r.no_ktp ? `<span class="afsel-sub">KTP ${esc(r.no_ktp)}</span>` : ''),
                },
                {
                    data: 'name', name: 'name',
                    render: (v, t, r) => `<button type="button" class="afsel-link dp-name-link" data-id="${esc(r.dealer_personel_id)}" title="Lihat detail">${esc(v)}</button>`
                        + (r.nm_panggilan ? `<span class="afsel-sub">${esc(r.nm_panggilan)}</span>` : ''),
                },
                {
                    data: 'dealer_level_personel', name: 'dealer_level_personel',
                    render: (v, t, r) => esc(v || '-') + (r.jenis ? ` <span class="afsel-tag ${r.jenis === 'Direct' ? 'is-red' : ''}">${esc(r.jenis)}</span>` : ''),
                },
                {
                    data: 'nm_dealer', name: 'nm_dealer',
                    render: (v, t, r) => v ? esc(v) : `<span class="text-danger" title="Dealer belum ada di master dealer">${esc(r.fk_dealer || '-')}</span>`,
                },
                { data: 'tgl_masuk', name: 'tgl_masuk', searchable: false, render: v => esc(v) },
                {
                    data: 'is_active', name: 'is_active', searchable: false,
                    render: (v, t, r) => {
                        let html = Number(v)
                            ? '<span class="afsel-status is-on">Aktif</span>'
                            : `<span class="afsel-status is-off" title="${esc(r.alasan_keluar || '')}">Nonaktif</span>`;
                        if (Number(r.is_blacklist)) html += ' <span class="afsel-tag is-red">Blacklist</span>';
                        if (Number(r.is_blacklist_atpm)) html += ' <span class="afsel-tag is-dark">Blacklist EMI</span>';
                        return html;
                    },
                },
                @if ($canEdit)
                {
                    data: null, orderable: false, searchable: false, className: 'text-end',
                    render: (v, t, r) => `<button type="button" class="btn-fi btn-fi-secondary btn-fi-sm dp-edit-btn" data-id="${esc(r.dealer_personel_id)}"
                        title="Ubah data keluar" aria-label="Ubah data keluar ${esc(r.name)}"><i class="bi bi-pencil"></i></button>`,
                },
                @endif
            ],
            language: {
                search: 'Cari:',
                processing: 'Memuat...',
                lengthMenu: '_MENU_ per halaman',
                info: '_START_–_END_ dari _TOTAL_ personel',
                infoEmpty: 'Tidak ada data',
                infoFiltered: '(dari _MAX_)',
                zeroRecords: 'Data tidak ditemukan',
                emptyTable: 'Belum ada data personel. Klik "Sync dari WRS" untuk mengambil data.',
                paginate: { previous: '‹', next: '›' },
            },
        });

        $('.dp-filter').on('change', () => table.ajax.reload());

        // ── Modal detail / ubah ──
        const detailModal = new bootstrap.Modal(document.getElementById('modal-personel'));
        const $body = $('#dp-body');
        const $footer = $('#dp-footer');
        const detailUrl = '{{ url('aftersales/atpm/personel/dealer-personel') }}';

        const initials = name => (name || '?').replace(/[^A-Za-z\s]/g, ' ').trim().split(/\s+/)
            .filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('') || '?';
        const value = (v, fallback = '-') => v ? esc(v) : `<span class="afsel-muted">${fallback}</span>`;
        const yesNo = (on, danger = false) => on
            ? `<span class="afsel-tag ${danger ? 'is-red' : ''}">Ya</span>`
            : '<span class="afsel-muted">Tidak</span>';
        const closeBtn = '<button type="button" class="btn-fi btn-fi-secondary btn-fi-sm" data-bs-dismiss="modal">Tutup</button>';

        const exitBlock = (d, editable) => `
            <div class="afsel-block ${editable ? 'is-accent' : ''}">
                <h6 class="afsel-block-title"><i class="bi bi-box-arrow-right"></i>Data Keluar Dealer Personel After Sales</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <dl class="afsel-dl">
                            <dt>Tanggal keluar</dt><dd>${value(d.tgl_keluar, d.is_active ? 'Masih bekerja' : '-')}</dd>
                            <dt>Black List</dt><dd>${yesNo(d.is_blacklist, true)}</dd>
                            <dt>Alasan keluar</dt><dd>${value(d.alasan_keluar)}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        ${editable ? `
                        <label class="afsel-setting" for="dp-is-active">
                            <span>
                                <span class="afsel-setting-title">Active</span>
                                <span class="afsel-setting-desc">Personel masih aktif bekerja di dealer</span>
                            </span>
                            <span class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="dp-is-active" ${d.is_active ? 'checked' : ''}>
                            </span>
                        </label>
                        <label class="afsel-setting" for="dp-is-blacklist-atpm">
                            <span>
                                <span class="afsel-setting-title">Black List EMI</span>
                                <span class="afsel-setting-desc">Diblacklist oleh EMI (ATPM)</span>
                            </span>
                            <span class="form-check form-switch m-0">
                                <input class="form-check-input is-danger" type="checkbox" role="switch" id="dp-is-blacklist-atpm" ${d.is_blacklist_atpm ? 'checked' : ''}>
                            </span>
                        </label>` : `
                        <div class="afsel-setting" style="cursor:default">
                            <span><span class="afsel-setting-title">Active</span><span class="afsel-setting-desc">Personel masih aktif bekerja di dealer</span></span>
                            ${d.is_active ? '<span class="badge bg-success">Ya</span>' : '<span class="badge bg-secondary">Tidak</span>'}
                        </div>
                        <div class="afsel-setting" style="cursor:default">
                            <span><span class="afsel-setting-title">Black List EMI</span><span class="afsel-setting-desc">Diblacklist oleh EMI (ATPM)</span></span>
                            ${d.is_blacklist_atpm ? '<span class="badge bg-danger">Ya</span>' : '<span class="badge bg-secondary">Tidak</span>'}
                        </div>`}
                    </div>
                </div>
            </div>`;

        const renderHead = d => {
            const photo = d.photo_url
                ? `<img src="${esc(d.photo_url)}" alt="Foto ${esc(d.name)}" onerror="this.parentNode.textContent='${esc(initials(d.name))}'">`
                : esc(initials(d.name));
            const chips = [
                d.level ? `<span class="dp-chip"><i class="bi bi-person-gear"></i>${esc(d.level)}</span>` : '',
                d.jenis ? `<span class="dp-chip ${d.jenis === 'Direct' ? 'is-red' : ''}">${esc(d.jenis)}</span>` : '',
                `<span class="dp-chip ${d.is_active ? 'is-ok' : 'is-off'}">${d.is_active ? 'Aktif' : 'Nonaktif'}</span>`,
            ].join('');

            return `
                <div class="dp-photo">${photo}</div>
                <div class="min-w-0">
                    <div class="afsel-eyebrow">Dealer Personel After Sales</div>
                    <h5 class="dp-name" id="dp-modal-title">${esc(d.name)}</h5>
                    <div class="dp-nick">${d.nm_panggilan ? 'Panggilan: ' + esc(d.nm_panggilan) : 'NIK ' + esc(d.nik)}</div>
                    <div class="dp-chips">${chips}</div>
                </div>`;
        };

        const render = (d, editable) => {
            const blacklist = [d.is_blacklist ? 'Dealer' : '', d.is_blacklist_atpm ? 'EMI' : ''].filter(Boolean);
            const note = blacklist.length
                ? `<div class="afsel-note is-red"><i class="bi bi-exclamation-octagon-fill"></i>
                       <div><strong>Personel ini masuk daftar blacklist ${blacklist.join(' & ')}.</strong><br>Pastikan sebelum menugaskan atau merekrut ulang.</div></div>`
                : '';

            const dealer = d.nm_dealer
                ? `${esc(d.nm_dealer)}<br><code>${esc(d.fk_dealer)}</code>`
                : (d.fk_dealer ? `<span class="text-danger">${esc(d.fk_dealer)}</span><br><span class="afsel-muted">belum ada di master dealer</span>` : value(null));

            return `
                ${note}
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="afsel-block h-100">
                            <h6 class="afsel-block-title"><i class="bi bi-person-vcard"></i>Identitas</h6>
                            <dl class="afsel-dl">
                                <dt>NIK</dt><dd>${value(d.nik)}</dd>
                                <dt>No KTP</dt><dd>${value(d.no_ktp)}</dd>
                                <dt>Kode personel</dt><dd><code>${esc(d.id)}</code></dd>
                            </dl>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="afsel-block h-100">
                            <h6 class="afsel-block-title"><i class="bi bi-shop"></i>Penempatan</h6>
                            <dl class="afsel-dl">
                                <dt>Dealer</dt><dd>${dealer}</dd>
                                <dt>Level</dt><dd>${value(d.level)}</dd>
                                <dt>Jenis</dt><dd>${value(d.jenis)}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="afsel-block">
                    <h6 class="afsel-block-title"><i class="bi bi-calendar3"></i>Masa Kerja</h6>
                    <dl class="afsel-dl">
                        <dt>Tanggal masuk</dt><dd>${value(d.tgl_masuk)}</dd>
                        <dt>Lama bekerja</dt><dd>${value(d.masa_kerja)}</dd>
                    </dl>
                </div>
                ${exitBlock(d, editable)}
                <div class="afsel-block">
                    <h6 class="afsel-block-title"><i class="bi bi-geo-alt"></i>Alamat</h6>
                    <div>${value(d.alamat)}</div>
                </div>`;
        };

        // Loading berkilau
        const skeletonHead = `
            <div class="dp-photo afsel-skel"></div>
            <div class="min-w-0 flex-grow-1">
                <div class="afsel-skel" style="width:110px;height:10px"></div>
                <div class="afsel-skel mt-2" style="width:240px;max-width:100%;height:22px"></div>
                <div class="afsel-skel mt-2" style="width:150px;height:12px"></div>
            </div>`;
        const skeletonBody = `
            <div class="row g-3 mb-3">
                <div class="col-md-6"><div class="afsel-block"><div class="afsel-skel" style="height:82px"></div></div></div>
                <div class="col-md-6"><div class="afsel-block"><div class="afsel-skel" style="height:82px"></div></div></div>
            </div>
            ${'<div class="afsel-block"><div class="afsel-skel" style="height:56px"></div></div>'.repeat(2)}`;

        const openDetail = (id, editable) => {
            $('#dp-head').html(skeletonHead);
            $body.html(skeletonBody);
            $footer.html(closeBtn);
            detailModal.show();

            axios.get(`${detailUrl}/${encodeURIComponent(id)}`, { headers: { Accept: 'application/json' } })
                .then(res => {
                    const d = res.data.data;
                    $('#dp-head').html(renderHead(d));
                    $body.html(render(d, editable));
                    $footer.html(editable
                        ? `<button type="button" class="btn-fi btn-fi-secondary btn-fi-sm" data-bs-dismiss="modal">Batal</button>
                           <button type="button" class="btn-fi btn-fi-primary btn-fi-sm dp-save-btn" data-id="${esc(d.id)}" data-name="${esc(d.name)}"><i class="bi bi-save"></i> Simpan</button>`
                        : closeBtn);
                })
                .catch(err => {
                    $('#dp-head').html('<div class="min-w-0"><div class="afsel-eyebrow">Dealer Personel After Sales</div><h5 class="dp-name" id="dp-modal-title">Gagal memuat data</h5></div>');
                    $body.html(`<div class="afsel-note is-red mb-0"><i class="bi bi-exclamation-triangle-fill"></i><div>${esc(err.response?.data?.message || 'Terjadi kesalahan. Silakan coba lagi.')}</div></div>`);
                });
        };

        $('#table-personel').on('click', '.dp-name-link', function () { openDetail($(this).data('id'), false); });
        $('#table-personel').on('click', '.dp-edit-btn', function () { openDetail($(this).data('id'), true); });

        // Simpan Active & Black List EMI
        $footer.on('click', '.dp-save-btn', function () {
            const $btn = $(this);
            const payload = {
                is_active: $('#dp-is-active').is(':checked') ? 1 : 0,
                is_blacklist_atpm: $('#dp-is-blacklist-atpm').is(':checked') ? 1 : 0,
            };

            Swal.fire({
                title: 'Simpan perubahan?',
                html: `${esc($btn.data('name'))}<br>Active: <b>${payload.is_active ? 'Ya' : 'Tidak'}</b>, Black List EMI: <b>${payload.is_blacklist_atpm ? 'Ya' : 'Tidak'}</b>`,
                icon: payload.is_blacklist_atpm ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                confirmButtonColor: payload.is_blacklist_atpm ? '#a6192e' : '#1b2027',
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: () => axios.put(`${detailUrl}/${encodeURIComponent($btn.data('id'))}`, payload, {
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                })
                .then(res => res.data)
                .catch(err => {
                    Swal.showValidationMessage(err.response?.data?.message || 'Gagal menyimpan. Silakan coba lagi.');
                    return false;
                }),
            }).then(result => {
                if (!result.isConfirmed || !result.value) return;
                detailModal.hide();
                if (result.value.stats) {
                    $('#stat-active').text(Number(result.value.stats.active).toLocaleString('id-ID'));
                    $('#stat-blacklist').text(Number(result.value.stats.blacklist).toLocaleString('id-ID'));
                }
                table.ajax.reload(null, false);
                Swal.fire({ title: 'Tersimpan', text: result.value.message, icon: 'success', timer: 1500, showConfirmButton: false });
            });
        });

        $('#btn-sync').on('click', function () {
            Swal.fire({
                title: 'Sync Dealer Personel?',
                html: 'Personel baru dari WRS akan ditambahkan dan personel yang sudah ada diperbarui.<br><span class="small text-muted">Status Active dan Black List EMI yang sudah diubah ATPM tidak ditimpa.</span>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sync sekarang',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#1b2027',
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: () => axios.post('{{ route('aftersales.atpm.dealer_personel.sync') }}', {}, {
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    timeout: 600000,
                })
                .then(res => res.data)
                .catch(err => {
                    Swal.showValidationMessage(err.response?.data?.message || 'Sync gagal. Silakan coba lagi.');
                    return false;
                }),
            }).then(result => {
                if (!result.isConfirmed || !result.value) return;
                const r = result.value.result;
                let html = `Data WRS: <b>${r.source_total}</b>, ditambahkan: <b>${r.inserted}</b>, diperbarui: <b>${r.updated}</b>`;

                const unknown = Object.entries(r.unknown_dealer || {});
                if (unknown.length) {
                    html += `<div class="mt-2 text-start small"><b>Dealer belum ada di master dealer</b> (personel tetap disimpan):
                        <ul class="dp-result-list">${unknown.map(([kd, n]) => `<li>${esc(kd)}: ${n} personel</li>`).join('')}</ul></div>`;
                }
                if ((r.skipped_level || []).length) {
                    html += `<div class="mt-2 text-start small"><b>Dilewati karena level belum ada di Level Personel:</b>
                        <ul class="dp-result-list">${r.skipped_level.map(s => `<li>${esc(s.name)} (${esc(s.id)}), level ${esc(s.level)}</li>`).join('')}</ul></div>`;
                }

                Swal.fire({
                    title: 'Sync selesai',
                    html,
                    icon: (r.skipped_level || []).length ? 'warning' : 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#1b2027',
                }).then(() => window.location.reload());
            });
        });
    });
</script>
@endsection
