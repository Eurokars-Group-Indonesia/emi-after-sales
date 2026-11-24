@extends('aftersales.dealer.layouts.app')

@section('title', 'Dealer Personel After Sales')

@section('navtop')
    {{ view('aftersales.dealer.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('aftersales.dealer.layouts.sidebar') }}
@endsection

@section('content')
@php $canChange = $canEdit || $canStatus; @endphp
<div class="content afsel-ui">
    <div class="page-header flex-wrap gap-2">
        <div class="page-title">Dealer Personel After Sales</div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('aftersales.dealer.home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript:void(0)">Personel After Sales</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dealer Personel</li>
            </ol>
        </nav>
    </div>

    @unless ($hasDealer)
        <div class="afsel-note is-red"><i class="bi bi-exclamation-triangle-fill"></i>
            <div>Akun Anda belum terhubung ke dealer, sehingga data personel tidak bisa ditampilkan.</div></div>
    @endunless

    <div class="card">
        <form id="form-search" class="afsel-toolbar" autocomplete="off" role="search" aria-label="Cari personel">
            <div class="cd-length">
                <select id="page-length" class="form-select form-select-sm" aria-label="Jumlah data per halaman">
                    @foreach ([10, 25, 50, 100] as $len)
                        <option value="{{ $len }}" @selected($len === 25)>{{ $len }}</option>
                    @endforeach
                </select>
                <span class="cd-sep">per halaman</span>
            </div>

            <div class="cd-filter">
                <select class="form-select form-select-sm dp-filter" id="filter-level" aria-label="Filter level">
                    <option value="">Semua level</option>
                    @foreach ($levels as $l)
                        <option value="{{ $l->dealer_level_personel_id }}">{{ $l->dealer_level_personel }}</option>
                    @endforeach
                </select>
            </div>

            <div class="cd-filter">
                <select class="form-select form-select-sm dp-filter" id="filter-status" aria-label="Filter status">
                    <option value="">Semua status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                    <option value="blacklist">Blacklist</option>
                </select>
            </div>

            <div class="cd-search afsel-grow">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="search-text" class="form-control form-control-sm"
                       placeholder="Cari NIK, No KTP, nama, level..." aria-label="Cari personel">
            </div>

            <button type="submit" class="btn-fi btn-fi-primary btn-fi-sm" title="Cari">
                <i class="bi bi-search"></i>
            </button>
            <button type="button" id="btn-reset" class="btn-fi btn-fi-secondary btn-fi-sm" title="Reset filter">
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>
            @if ($canExport && $hasDealer)
                <button type="button" class="btn-fi btn-fi-secondary btn-fi-sm cd-btn-excel" id="btn-export"
                        title="Download Excel sesuai filter" aria-label="Download Excel">
                    <i class="bi bi-file-earmark-excel-fill"></i>
                </button>
            @endif
            @if ($canCreate && $hasDealer)
                <button type="button" class="btn-fi btn-fi-primary btn-fi-sm" id="btn-add">
                    <i class="bi bi-plus-lg"></i> Tambah Personel
                </button>
            @endif
        </form>

        <div class="card-body">
            <table id="table-personel" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>NIK / No KTP</th>
                        <th>Nama</th>
                        <th>Level</th>
                        <th style="width:100px">Tgl masuk</th>
                        <th style="width:170px">Status</th>
                        @if ($canChange)
                            <th class="text-end" style="width:60px">Aksi</th>
                        @endif
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Modal detail personel --}}
<div class="modal fade afsel-ui" id="modal-detail" tabindex="-1" aria-labelledby="dp-modal-title" aria-hidden="true">
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

{{-- Modal tambah / ubah personel --}}
@if ($canCreate || $canChange)
<div class="modal fade afsel-ui" id="modal-form" tabindex="-1" aria-labelledby="form-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <form class="modal-content" id="form-personel" novalidate autocomplete="off">
            <div class="modal-header">
                <div>
                    <div class="afsel-eyebrow">Dealer Personel After Sales</div>
                    <h6 class="modal-title" id="form-title">Tambah Personel</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">

                <div class="afsel-note" id="form-note-new">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>Personel baru disimpan dengan status <strong>Nonaktif</strong> dan baru bisa diaktifkan oleh ATPM. Nama dan No KTP tidak boleh sama dengan personel yang sedang aktif.</div>
                </div>

                <div class="afsel-block" id="block-identity">
                    <h6 class="afsel-block-title"><i class="bi bi-person-vcard"></i>Identitas & Penempatan</h6>
                    <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="f-photo">Foto</label>
                        <div class="dp-photo-edit" id="photo-preview"><i class="bi bi-person"></i></div>
                        <input type="file" class="form-control form-control-sm dp-identity mt-2" id="f-photo" name="photo"
                               accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                        <div class="form-text" id="hint-photo">.jpg, .jpeg, .png &middot; maks {{ $photoMaxKb }} KB</div>
                    </div>
                    <div class="col-md-9">
                    <div class="row g-2">
                        <div class="col-md-8">
                            <label class="form-label" for="f-name">Nama lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm dp-identity" id="f-name" name="name" maxlength="150" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="f-nick">Nama panggilan</label>
                            <input type="text" class="form-control form-control-sm dp-identity" id="f-nick" name="nm_panggilan" maxlength="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="f-nik">NIK <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm dp-identity" id="f-nik" name="nik" maxlength="50" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="f-ktp">No KTP <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm dp-identity" id="f-ktp" name="no_ktp" maxlength="16"
                                   inputmode="numeric" placeholder="16 digit" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="f-masuk">Tanggal masuk <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm dp-identity" id="f-masuk" name="tgl_masuk" max="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="f-level">Level <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm dp-identity" id="f-level" name="dealer_level_personel_id" required>
                                <option value="">- Pilih level -</option>
                                @foreach ($levels as $l)
                                    <option value="{{ $l->dealer_level_personel_id }}" data-active="{{ (int) $l->is_active }}">
                                        {{ $l->dealer_level_personel }}{{ $l->jenis ? ' (' . $l->jenis . ')' : '' }}{{ $l->is_active ? '' : ' - nonaktif' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="f-alamat">Alamat</label>
                            <textarea class="form-control form-control-sm dp-identity" id="f-alamat" name="alamat" rows="2" maxlength="1000"></textarea>
                        </div>
                    </div>
                    </div>
                    </div>
                </div>

                @if ($canStatus)
                <div class="afsel-block is-accent mb-0" id="block-status">
                    <h6 class="afsel-block-title"><i class="bi bi-toggle-on"></i>Status & Data Keluar</h6>
                    <div class="afsel-note is-red d-none" id="note-blacklist-atpm">
                        <i class="bi bi-exclamation-octagon-fill"></i>
                        <div>Personel ini masuk <strong>Black List EMI</strong> dan harus berstatus nonaktif.</div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="afsel-setting" for="f-active">
                                <span>
                                    <span class="afsel-setting-title">Active</span>
                                    <span class="afsel-setting-desc" id="desc-active">Personel masih aktif bekerja di dealer</span>
                                </span>
                                <span class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="f-active" name="is_active">
                                </span>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="afsel-setting" for="f-blacklist">
                                <span>
                                    <span class="afsel-setting-title">Black List</span>
                                    <span class="afsel-setting-desc">Diblacklist oleh dealer</span>
                                </span>
                                <span class="form-check form-switch m-0">
                                    <input class="form-check-input is-danger" type="checkbox" role="switch" id="f-blacklist" name="is_blacklist">
                                </span>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="f-keluar">Tanggal keluar</label>
                            <input type="date" class="form-control form-control-sm dp-exit" id="f-keluar" name="tgl_keluar" max="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="f-alasan">Alasan keluar</label>
                            <input type="text" class="form-control form-control-sm dp-exit" id="f-alasan" name="alasan_keluar" maxlength="1000">
                        </div>
                        <div class="col-12 form-text mt-0" id="hint-exit">Data keluar hanya diisi untuk personel nonaktif.</div>
                    </div>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-fi btn-fi-secondary btn-fi-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn-fi btn-fi-primary btn-fi-sm" id="btn-save"><i class="bi bi-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endif

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
    #modal-form .afsel-block { margin-bottom: 12px; }

    /* Toolbar filter (sama dengan halaman Data Service) */
    .afsel-ui .cd-filter { display: flex; align-items: center; gap: 6px; }
    .afsel-ui .afsel-toolbar .cd-filter .form-select { width: 170px; min-width: 0; }
    .afsel-ui .cd-sep { font-size: 12px; color: var(--mz-muted); }
    .afsel-ui .cd-search { position: relative; }
    .afsel-ui .cd-search i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--mz-muted); font-size: 12px; pointer-events: none; }
    .afsel-ui .cd-search .form-control { padding-left: 30px; }
    .afsel-ui .cd-btn-excel i { color: #1d6f42; }
    .afsel-ui .cd-btn-excel:hover { border-color: #1d6f42; }
    .afsel-ui .cd-length { display: flex; align-items: center; gap: 6px; margin-right: 10px; }
    .afsel-ui .afsel-toolbar .cd-length .form-select { width: auto; min-width: 0; padding-right: 1.9rem; }

    /* Foto di form */
    .dp-photo-edit {
        width: 100%; max-width: 150px; aspect-ratio: 4 / 5; border-radius: 12px; overflow: hidden;
        background: var(--mz-bg-soft, #f3f4f6); border: 1px dashed var(--mz-line); color: var(--mz-off, #9aa1a9);
        display: flex; align-items: center; justify-content: center; font-size: 44px;
    }
    .dp-photo-edit img { width: 100%; height: 100%; object-fit: cover; display: block; }

    /* Tabel: header selalu 1 baris, isi rapi (sama dengan Data Service) */
    .afsel-ui table.dataTable thead th,
    .afsel-ui .dt-scroll-head th { white-space: nowrap !important; }
    .afsel-ui #table-personel tbody td { white-space: nowrap; font-size: 12.5px; }
    .afsel-ui #table-personel tbody tr:hover > td { background: #fafbfc; }

    /* Tabel langsung di bawah toolbar; info + paging sebaris di bawah */
    .afsel-ui .card-body > .dt-container > .dt-layout-row:first-child { margin-top: 0; }

    /* DataTables 2: info & paging bergaya Mazda (sama dengan Data Service) */
    .afsel-ui div.dt-container .dt-info,
    .afsel-ui div.dt-container .dt-length { font-size: 12px; color: var(--mz-muted); }
    .afsel-ui div.dt-container .dt-paging .dt-paging-button {
        font-size: 12px; padding: 4px 10px; border-radius: 6px !important; border: 1px solid transparent !important;
        color: var(--mz-ink) !important; background: transparent !important;
    }
    .afsel-ui div.dt-container .dt-paging .dt-paging-button:hover:not(.disabled) {
        background: var(--mz-bg-soft) !important; border-color: var(--mz-line) !important;
    }
    .afsel-ui div.dt-container .dt-paging .dt-paging-button.current,
    .afsel-ui div.dt-container .dt-paging .dt-paging-button.current:hover {
        background: var(--mz-ink) !important; border-color: var(--mz-ink) !important; color: #fff !important;
        box-shadow: inset 0 -2px 0 var(--mz-red);
    }
    .afsel-ui div.dt-container .dt-paging .dt-paging-button.disabled { color: var(--mz-off) !important; }
    .afsel-ui div.dt-container .dt-processing > div:last-child > div { background: var(--mz-red); }

    @media (max-width: 768px) {
        .afsel-ui .cd-filter { flex: 1 1 160px; }
        .afsel-ui .afsel-toolbar .cd-filter .form-select { flex: 1 1 auto; width: auto; }
        .afsel-ui .cd-search { flex-basis: 100%; max-width: none; }
    }
    @media (max-width: 576px) { .dp-hero-inner { flex-direction: column; align-items: flex-start; } }
</style>

<script>
    $(function () {
        const esc = s => $('<div>').text(s ?? '').html();
        const baseUrl = '{{ route('aftersales.dealer.personel.dealer-personel') }}';
        const headers = { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' };
        const can = { create: @json($canCreate), edit: @json($canEdit), status: @json($canStatus) };

        const table = $('#table-personel').DataTable({
            processing: true,
            serverSide: true,
            scrollX: true,
            scrollY: 'calc(100vh - 340px)',
            scrollCollapse: true,
            pageLength: 25,
            // Per halaman & pencarian ada di toolbar (seperti Data Service); kata kunci dikirim lewat table.search()
            layout: {
                topStart: null,
                topEnd: null,
                bottomStart: 'info',
                bottomEnd: 'paging',
            },
            order: [[1, 'asc']],
            ajax: {
                url: '{{ route('aftersales.dealer.personel.dealer-personel.datatable') }}',
                data: d => Object.assign(d, {
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
                @if ($canChange)
                {
                    data: null, orderable: false, searchable: false, className: 'text-end',
                    render: (v, t, r) => `<button type="button" class="btn-fi btn-fi-secondary btn-fi-sm dp-edit-btn" data-id="${esc(r.dealer_personel_id)}"
                        title="Ubah" aria-label="Ubah ${esc(r.name)}"><i class="bi bi-pencil"></i></button>`,
                },
                @endif
            ],
            language: {
                processing: 'Memuat...',
                lengthMenu: '_MENU_ per halaman',
                info: '_START_–_END_ dari _TOTAL_ personel',
                infoEmpty: 'Tidak ada data',
                infoFiltered: '(dari _MAX_)',
                zeroRecords: 'Data personel tidak ditemukan',
                emptyTable: 'Belum ada data personel.',
                paginate: { first: '«', previous: '‹', next: '›', last: '»' },
            },
        });

        $('.dp-filter').on('change', () => table.ajax.reload());

        $('#form-search').on('submit', function (e) {
            e.preventDefault();
            table.search($('#search-text').val().trim()).draw();
        });

        $('#page-length').on('change', function () {
            table.page.len(Number(this.value)).draw();
        });

        $('#btn-reset').on('click', function () {
            $('#form-search')[0].reset();
            table.search('').page.len(Number($('#page-length').val())).draw();
        });

        // ── Export Excel (filter + kata kunci "Cari" yang sedang dipakai) ──
        $('#btn-export').on('click', function () {
            const total = table.page.info().recordsDisplay;
            if (!total) {
                Swal.fire({ icon: 'info', title: 'Tidak ada data', text: 'Tidak ada personel untuk di-download dengan filter ini.', confirmButtonColor: '#1b2027' });
                return;
            }

            Swal.fire({
                title: 'Menyiapkan file Excel...',
                html: `${total.toLocaleString('id-ID')} personel. Mohon tunggu.`,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            axios.get('{{ route('aftersales.dealer.personel.dealer-personel.export') }}', {
                params: { level: $('#filter-level').val(), status: $('#filter-status').val(), q: table.search() },
                responseType: 'blob',
                timeout: 300000,
            })
            .then(res => {
                const disposition = res.headers['content-disposition'] || '';
                const match = disposition.match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);
                const fileName = match ? decodeURIComponent(match[1]) : 'Dealer-Personel.xlsx';

                const url = URL.createObjectURL(res.data);
                const a = Object.assign(document.createElement('a'), { href: url, download: fileName });
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(() => URL.revokeObjectURL(url), 1000);

                Swal.fire({ icon: 'success', title: 'Download selesai', text: fileName, timer: 1800, showConfirmButton: false });
            })
            .catch(err => Swal.fire({
                icon: 'error',
                title: 'Download gagal',
                text: err.response?.status === 403 ? 'Anda tidak punya akses download Excel personel.' : 'Terjadi kesalahan saat membuat file. Silakan coba lagi.',
                confirmButtonColor: '#1b2027',
            }));
        });

        const fetchPersonel = id => axios.get(`${baseUrl}/${encodeURIComponent(id)}`, { headers }).then(res => res.data.data);
        const errorMessage = err => err.response?.data?.message || 'Terjadi kesalahan. Silakan coba lagi.';

        // ── Modal detail ──
        const detailModal = new bootstrap.Modal(document.getElementById('modal-detail'));
        const $body = $('#dp-body');
        const $footer = $('#dp-footer');

        const initials = name => (name || '?').replace(/[^A-Za-z\s]/g, ' ').trim().split(/\s+/)
            .filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('') || '?';
        const value = (v, fallback = '-') => v ? esc(v) : `<span class="afsel-muted">${fallback}</span>`;
        const yesNo = (on, danger = false) => on
            ? `<span class="afsel-tag ${danger ? 'is-red' : ''}">Ya</span>`
            : '<span class="afsel-muted">Tidak</span>';
        const closeBtn = '<button type="button" class="btn-fi btn-fi-secondary btn-fi-sm" data-bs-dismiss="modal">Tutup</button>';

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

        const renderBody = d => {
            const blacklist = [d.is_blacklist ? 'Dealer' : '', d.is_blacklist_atpm ? 'EMI' : ''].filter(Boolean);
            const note = blacklist.length
                ? `<div class="afsel-note is-red"><i class="bi bi-exclamation-octagon-fill"></i>
                       <div><strong>Personel ini masuk daftar blacklist ${blacklist.join(' & ')}.</strong><br>Pastikan sebelum menugaskan atau merekrut ulang.</div></div>`
                : '';

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
                            <h6 class="afsel-block-title"><i class="bi bi-calendar3"></i>Penempatan & Masa Kerja</h6>
                            <dl class="afsel-dl">
                                <dt>Level</dt><dd>${value(d.level)}${d.jenis ? ` <span class="afsel-muted">(${esc(d.jenis)})</span>` : ''}</dd>
                                <dt>Tanggal masuk</dt><dd>${value(d.tgl_masuk_text)}</dd>
                                <dt>Lama bekerja</dt><dd>${value(d.masa_kerja)}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="afsel-block">
                    <h6 class="afsel-block-title"><i class="bi bi-box-arrow-right"></i>Data Keluar</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <dl class="afsel-dl">
                                <dt>Status</dt><dd>${d.is_active ? '<span class="afsel-status is-on">Aktif</span>' : '<span class="afsel-status is-off">Nonaktif</span>'}</dd>
                                <dt>Tanggal keluar</dt><dd>${value(d.tgl_keluar_text, d.is_active ? 'Masih bekerja' : '-')}</dd>
                                <dt>Alasan keluar</dt><dd>${value(d.alasan_keluar)}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="afsel-dl">
                                <dt>Black List</dt><dd>${yesNo(d.is_blacklist, true)}</dd>
                                <dt>Black List EMI</dt><dd>${yesNo(d.is_blacklist_atpm, true)}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="afsel-block mb-0">
                    <h6 class="afsel-block-title"><i class="bi bi-geo-alt"></i>Alamat</h6>
                    <div>${value(d.alamat)}</div>
                </div>`;
        };

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

        const openDetail = id => {
            $('#dp-head').html(skeletonHead);
            $body.html(skeletonBody);
            $footer.html(closeBtn);
            detailModal.show();

            fetchPersonel(id)
                .then(d => {
                    $('#dp-head').html(renderHead(d));
                    $body.html(renderBody(d));
                    $footer.html((can.edit || can.status)
                        ? `${closeBtn}<button type="button" class="btn-fi btn-fi-primary btn-fi-sm dp-detail-edit" data-id="${esc(d.id)}"><i class="bi bi-pencil"></i> Ubah</button>`
                        : closeBtn);
                })
                .catch(err => {
                    $('#dp-head').html('<div class="min-w-0"><div class="afsel-eyebrow">Dealer Personel After Sales</div><h5 class="dp-name" id="dp-modal-title">Gagal memuat data</h5></div>');
                    $body.html(`<div class="afsel-note is-red mb-0"><i class="bi bi-exclamation-triangle-fill"></i><div>${esc(errorMessage(err))}</div></div>`);
                });
        };

        $('#table-personel').on('click', '.dp-name-link', function () { openDetail($(this).data('id')); });

        // ── Modal tambah / ubah ──
        const formEl = document.getElementById('form-personel');
        if (!formEl) return;

        const f = name => formEl.elements[name];
        const formModal = new bootstrap.Modal(document.getElementById('modal-form'));
        const $form = $(formEl);
        const $save = $('#btn-save');
        let blacklistAtpm = false;
        let wasActive = false;

        // Data keluar hanya untuk personel nonaktif; personel Black List EMI tidak bisa diaktifkan
        const syncStatus = () => {
            if (!f('is_active')) return;
            const active = f('is_active').checked;
            $form.find('.dp-exit').prop('disabled', active);
            $('#hint-exit').text(active ? 'Personel aktif tidak punya data keluar; tanggal & alasan keluar akan dikosongkan.' : 'Data keluar hanya diisi untuk personel nonaktif.');
            // Aktivasi hanya oleh ATPM: switch terkunci untuk personel yang tersimpan nonaktif
            f('is_active').disabled = !wasActive || (!active && (blacklistAtpm || f('is_blacklist').checked));
            $('#desc-active').text(wasActive ? 'Matikan bila personel sudah tidak bekerja di dealer' : 'Personel nonaktif hanya bisa diaktifkan oleh ATPM');
        };
        $form.on('change', '#f-active', syncStatus);
        // Personel yang diblacklist otomatis nonaktif
        $form.on('change', '#f-blacklist', function () {
            if (this.checked) f('is_active').checked = false;
            syncStatus();
        });

        // No KTP hanya angka
        $('#f-ktp').on('input', function () { this.value = this.value.replace(/\D/g, '').slice(0, 16); });

        // ── Foto: .jpg/.jpeg/.png, maks {{ $photoMaxKb }} KB, pratinjau langsung ──
        const photoMaxKb = @json($photoMaxKb);
        const $preview = $('#photo-preview');
        let currentPhotoUrl = null;
        let previewUrl = null;
        const showPhoto = url => {
            if (previewUrl && previewUrl !== url) { URL.revokeObjectURL(previewUrl); previewUrl = null; }
            $preview.html(url ? `<img src="${esc(url)}" alt="Foto personel">` : '<i class="bi bi-person"></i>');
            $preview.find('img').on('error', () => $preview.html('<i class="bi bi-person"></i>'));
        };
        $('#f-photo').on('change', function () {
            const file = this.files[0];
            if (!file) return showPhoto(currentPhotoUrl);

            const ext = (file.name.split('.').pop() || '').toLowerCase();
            const error = !['jpg', 'jpeg', 'png'].includes(ext) ? 'Foto harus berformat .jpg, .jpeg atau .png.'
                : file.size > photoMaxKb * 1024 ? `Ukuran foto ${Math.ceil(file.size / 1024)} KB, maksimal ${photoMaxKb} KB.`
                : null;
            if (error) {
                this.value = '';
                showPhoto(currentPhotoUrl);
                Swal.fire({ icon: 'warning', title: 'Foto tidak valid', text: error, confirmButtonColor: '#1b2027' });
                return;
            }
            showPhoto(previewUrl = URL.createObjectURL(file));
        });

        const setLevelOptions = currentLevel => {
            // Level nonaktif disembunyikan, kecuali level personel ini sekarang
            $(f('dealer_level_personel_id')).find('option[data-active]').each(function () {
                const show = this.dataset.active === '1' || this.value === currentLevel;
                $(this).prop('hidden', !show).prop('disabled', !show);
            });
        };

        const openForm = (d = null) => {
            formEl.reset();
            f('id').value = d ? d.id : '';
            blacklistAtpm = !!d?.is_blacklist_atpm;
            wasActive = !!d?.is_active;
            setLevelOptions(d?.dealer_level_personel_id ?? null);
            currentPhotoUrl = d?.photo_url ?? null;
            showPhoto(currentPhotoUrl);

            $('#form-title').text(d ? `Ubah Personel · ${d.name}` : 'Tambah Personel');
            $('#form-note-new').toggle(!d);
            $('#block-status').toggle(!!d && can.status);
            $('#note-blacklist-atpm').toggleClass('d-none', !blacklistAtpm);

            // Tanpa hak ubah identitas: field identitas hanya tampil (tidak dikirim)
            $form.find('.dp-identity').prop('disabled', !!d && !can.edit);

            if (d) {
                ['name', 'nm_panggilan', 'nik', 'no_ktp', 'tgl_masuk', 'alamat', 'dealer_level_personel_id']
                    .forEach(k => f(k).value = d[k] ?? '');
                if (f('is_active')) {
                    f('is_active').checked = d.is_active;
                    f('is_blacklist').checked = d.is_blacklist;
                    f('tgl_keluar').value = d.tgl_keluar ?? '';
                    f('alasan_keluar').value = d.alasan_keluar ?? '';
                }
            }
            syncStatus();
            formModal.show();
            setTimeout(() => (d && !can.edit ? f('is_active') : f('name'))?.focus(), 300);
        };

        const loadAndEdit = id => {
            detailModal.hide();
            fetchPersonel(id)
                .then(d => openForm(d))
                .catch(err => Swal.fire({ title: 'Gagal memuat data', text: errorMessage(err), icon: 'error', confirmButtonColor: '#1b2027' }));
        };

        $('#btn-add').on('click', () => openForm());
        $('#table-personel').on('click', '.dp-edit-btn', function () { loadAndEdit($(this).data('id')); });
        $footer.on('click', '.dp-detail-edit', function () { loadAndEdit($(this).data('id')); });

        $form.on('submit', function (e) {
            e.preventDefault();
            const id = f('id').value;

            // multipart (ada file foto); ubah data memakai POST + _method=PUT karena PHP tidak membaca file pada PUT
            const payload = new FormData();
            $form.find('input[name], select[name], textarea[name]').each(function () {
                if (this.disabled || this.name === 'id' || $(this).closest('#block-status').is(':hidden')) return;
                if (this.type === 'file') {
                    if (this.files[0]) payload.append(this.name, this.files[0]);
                } else {
                    payload.append(this.name, this.type === 'checkbox' ? (this.checked ? '1' : '0') : this.value.trim());
                }
            });
            // Switch Active yang terkunci tetap dikirim nilainya
            if (id && can.status && f('is_active').disabled) payload.set('is_active', f('is_active').checked ? '1' : '0');
            if (id) payload.append('_method', 'PUT');

            $save.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Menyimpan...');

            const request = axios.post(id ? `${baseUrl}/${encodeURIComponent(id)}` : baseUrl, payload, { headers });

            request
                .then(res => {
                    formModal.hide();
                    table.ajax.reload(null, !!id ? false : true);
                    Swal.fire({ title: 'Tersimpan', text: res.data.message, icon: 'success', timer: 1800, showConfirmButton: false });
                })
                .catch(err => Swal.fire({ title: 'Gagal menyimpan', text: errorMessage(err), icon: 'warning', confirmButtonColor: '#1b2027' }))
                .finally(() => $save.prop('disabled', false).html('<i class="bi bi-save"></i> Simpan'));
        });
    });
</script>
@endsection
