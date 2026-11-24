@extends('aftersales.atpm.layouts.app')

@section('title', 'Service History')

@section('navtop')
    {{ view('aftersales.atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('aftersales.atpm.layouts.sidebar') }}
@endsection

@section('content')
<div class="content afsel-ui">
    <div class="page-header flex-wrap gap-2">
        <div class="page-title">Service History</div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('aftersales.atpm.home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript:void(0)">Vehicle</a></li>
                <li class="breadcrumb-item active" aria-current="page">Service History</li>
            </ol>
        </nav>
    </div>

    <div class="card sh-card">
        <form id="form-search" class="afsel-toolbar" autocomplete="off" role="search" aria-label="Filter service history">
            <div class="sh-length">
                <select id="page-length" class="form-select form-select-sm" aria-label="Jumlah data per halaman">
                    @foreach ([10, 25, 50, 100] as $len)
                        <option value="{{ $len }}" @selected($len === 25)>{{ $len }}</option>
                    @endforeach
                </select>
                <span class="sh-sep">per halaman</span>
            </div>

            <div class="sh-daterange" role="group" aria-label="Tanggal service">
                <span class="sh-field-label"><i class="bi bi-calendar3"></i> Tgl service</span>
                <input type="date" id="search-fromdate" class="form-control form-control-sm" aria-label="Tanggal service dari">
                <span class="sh-sep">s/d</span>
                <input type="date" id="search-todate" class="form-control form-control-sm" aria-label="Tanggal service sampai">
            </div>

            <div class="sh-model">
                <select id="search-model" class="form-select form-select-sm" data-afsel-search="float"
                        data-placeholder="Semua model" aria-label="Filter model">
                    <option value="">- Semua model -</option>
                    @foreach ($dataModel as $modelRow)
                        <option value="{{ $modelRow->kd_model }}" data-label="{{ $modelRow->nm_model }}" data-code="{{ $modelRow->kd_model }}">{{ $modelRow->nm_model }} ({{ $modelRow->kd_model }})</option>
                    @endforeach
                </select>
            </div>

            <div class="sh-search">
                <i class="bi bi-upc-scan" aria-hidden="true"></i>
                <input type="search" id="search-vin" class="form-control form-control-sm" placeholder="Cari VIN" aria-label="Cari VIN">
            </div>

            <button type="submit" class="btn-fi btn-fi-primary btn-fi-sm" title="Terapkan filter">
                <i class="bi bi-search"></i> Cari
            </button>
            <button type="button" id="btn-reset" class="btn-fi btn-fi-secondary btn-fi-sm" title="Reset filter" aria-label="Reset filter">
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>
        </form>

        <div class="card-body">
            <table id="table-datatable" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th class="text-center">Tgl Service</th>
                        <th>No. Polisi</th>
                        <th>VIN</th>
                        <th>Model</th>
                        <th>Customer</th>
                        <th>Telepon</th>
                        <th>Dealer Service</th>
                        <th>Request Pelanggan</th>
                    </tr>
                </thead>
            </table>
            <div class="form-text mt-2">
                <i class="bi bi-info-circle"></i>
                Menampilkan maksimal 1.000 data service terbaru sesuai filter, sampai tanggal hari ini.
            </div>
        </div>
    </div>
</div>

<style>
    /* Daftar model (melayang) tidak terpotong oleh kartu */
    .afsel-ui .sh-card { overflow: visible; }

    /* Toolbar filter */
    .afsel-ui .sh-length { display: flex; align-items: center; gap: 6px; }
    .afsel-ui .afsel-toolbar .sh-length .form-select { width: auto; min-width: 0; padding-right: 1.9rem; }
    .afsel-ui .sh-daterange { display: flex; align-items: center; gap: 6px; }
    .afsel-ui .sh-daterange .form-control { width: 128px; }
    .afsel-ui .sh-field-label { font-size: 12px; font-weight: 600; color: var(--mz-ink-2); white-space: nowrap; }
    .afsel-ui .sh-field-label i { color: var(--mz-red); }
    .afsel-ui .sh-sep { font-size: 12px; color: var(--mz-muted); white-space: nowrap; }
    .afsel-ui .sh-model { flex: 0 1 210px; min-width: 180px; }
    .afsel-ui .sh-search { position: relative; flex: 0 1 170px; min-width: 140px; }
    .afsel-ui .sh-search i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--mz-muted); font-size: 12px; pointer-events: none; }
    .afsel-ui .sh-search .form-control { padding-left: 30px; }

    /* Tabel: header 1 baris, isi padat */
    .afsel-ui table.dataTable thead th,
    .afsel-ui .dataTables_scrollHead th,
    .afsel-ui .dt-scroll-head th { white-space: nowrap !important; }
    .afsel-ui #table-datatable tbody td { white-space: nowrap; font-size: 12.5px; line-height: 1.35; }
    .afsel-ui #table-datatable tbody td.sh-wrap { white-space: normal; min-width: 260px; }
    .afsel-ui #table-datatable tbody td.sh-request { white-space: normal; min-width: 320px; line-height: 1.45; }
    .afsel-ui .sh-plate {
        display: inline-block; padding: 2px 8px; border-radius: 4px; background: var(--mz-ink); color: #fff;
        font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 12px; font-weight: 700; letter-spacing: .06em;
        box-shadow: inset 0 -2px 0 var(--mz-red);
    }
    .afsel-ui .sh-mono { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 12px; color: var(--mz-ink-2); }
    .afsel-ui .sh-name { font-weight: 600; }
    .afsel-ui #table-datatable td.sh-wrap .afsel-sub { white-space: normal; }

    /* Info + paging sebaris di bawah tabel */
    .afsel-ui .sh-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; margin-top: 10px; }
    .afsel-ui .sh-foot .dataTables_info { padding-top: 0; }
    .afsel-ui .sh-foot .pagination { margin: 0; }

    @media (max-width: 768px) {
        .afsel-ui .sh-daterange { flex-wrap: wrap; width: 100%; }
        .afsel-ui .sh-daterange .form-control { flex: 1 1 120px; width: auto; }
        .afsel-ui .sh-model, .afsel-ui .sh-search { flex: 1 1 100%; max-width: none; }
    }
</style>

@include('aftersales.atpm.components.searchable-select')
<script>
    $(function () {
        const esc = $.fn.dataTable.render.text().display;
        const empty = '<span class="afsel-muted">-</span>';
        const has = v => v !== null && v !== undefined && String(v).trim() !== '';
        const text = v => has(v) ? esc(v) : empty;
        const sub = v => has(v) ? `<span class="afsel-sub">${esc(v)}</span>` : '';

        const modelSelect = afselSearchSelect(document.getElementById('search-model'));

        const filters = () => ({
            srcFromDate: $('#search-fromdate').val(),
            srcToDate: $('#search-todate').val(),
            srcModel: $('#search-model').val(),
            srcVin: $('#search-vin').val().trim(),
        });

        const dt = $('#table-datatable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: false,
            scrollX: true,
            scrollY: 'calc(100vh - 330px)',
            scrollCollapse: true,
            pageLength: 25,
            // Default: tanggal service terbaru di atas
            order: [[0, 'desc']],
            dom: "rt<'sh-foot'ip>",
            ajax: {
                url: '{{ route('aftersales.atpm.vehicle_service_history_datatable') }}',
                data: d => Object.assign(d, filters()),
            },
            columns: [
                { data: 'date_service', name: 'date_service', className: 'text-center', orderSequence: ['desc', 'asc'], render: (v, t, r) => text(r.date_service_display) },
                { data: 'nomor_polisi', name: 'nomor_polisi', render: v => has(v) ? `<span class="sh-plate">${esc(v)}</span>` : empty },
                { data: 'no_vin', name: 'no_vin', render: v => has(v) ? `<span class="sh-mono">${esc(v)}</span>` : empty },
                {
                    data: 'nm_model', name: 'nm_model',
                    render: (v, t, r) => `<span class="sh-name">${text(v)}</span>`
                        + sub([r.fk_model, has(r.fk_type) ? 'Type ' + r.fk_type : null].filter(has).join(' · ')),
                },
                {
                    data: 'nama_customer', name: 'nama_customer', className: 'sh-wrap',
                    render: (v, t, r) => `<span class="sh-name">${text(v)}</span>${sub(r.alamat)}`,
                },
                {
                    data: 'telephone_1', name: 'telephone_1',
                    render: (v, t, r) => `${text(v)}${sub(r.telephone_2)}`,
                },
                {
                    data: 'dealer_service', name: 'dealer_service',
                    render: (v, t, r) => `${text(v)}${sub(r.fk_dealer_service)}`,
                },
                { data: 'customer_request', name: 'customer_request', className: 'sh-request', orderable: false, render: text },
            ],
            language: {
                processing: 'Memuat data...',
                info: '_START_ - _END_ dari _TOTAL_ data service',
                infoEmpty: 'Tidak ada data',
                emptyTable: 'Belum ada data service',
                zeroRecords: 'Data service tidak ditemukan dengan filter ini',
                paginate: { first: '«', previous: '‹', next: '›', last: '»' },
            },
        });

        const validDateRange = () => {
            const f = filters();
            if (f.srcFromDate && f.srcToDate && f.srcFromDate > f.srcToDate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tanggal tidak valid',
                    text: 'Tanggal service "dari" tidak boleh lebih besar dari tanggal "sampai".',
                    confirmButtonColor: '#1b2027',
                });
                return false;
            }
            if (!!f.srcFromDate !== !!f.srcToDate) {
                Swal.fire({
                    icon: 'info',
                    title: 'Lengkapi tanggal',
                    text: 'Isi tanggal service "dari" dan "sampai" agar filter tanggal dipakai.',
                    confirmButtonColor: '#1b2027',
                });
                return false;
            }
            return true;
        };

        $('#form-search').on('submit', function (e) {
            e.preventDefault();
            if (validDateRange()) dt.ajax.reload();
        });

        $('#page-length').on('change', function () {
            dt.page.len(Number(this.value)).draw();
        });

        $('#btn-reset').on('click', function () {
            $('#form-search')[0].reset();
            setTimeout(() => {
                modelSelect.sync();
                dt.page.len(Number($('#page-length').val())).draw();
            });
        });
    });
</script>
@endsection
