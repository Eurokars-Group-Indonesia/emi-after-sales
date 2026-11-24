@extends('aftersales.dealer.layouts.app')

@section('title', 'Customer Data')

@section('navtop')
    {{ view('aftersales.dealer.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('aftersales.dealer.layouts.sidebar') }}
@endsection

@section('content')
<div class="content afsel-ui">
    <div class="page-header flex-wrap gap-2">
        <div>
            <div class="page-title">Customer Data</div>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('aftersales.dealer.home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript:void(0)">Customer</a></li>
                <li class="breadcrumb-item active" aria-current="page">Customer Data</li>
            </ol>
        </nav>
    </div>



    <div class="card">
        <form id="form-search" class="afsel-toolbar" autocomplete="off" role="search" aria-label="Cari customer">
            <div class="cd-length">
                <select id="page-length" class="form-select form-select-sm" aria-label="Jumlah data per halaman">
                    @foreach ([10, 25, 50, 100] as $len)
                        <option value="{{ $len }}" @selected($len === 25)>{{ $len }}</option>
                    @endforeach
                </select>
                <span class="cd-sep">per halaman</span>
            </div>

            <div class="cd-daterange" role="group" aria-label="Tanggal service">
                <span class="cd-field-label"><i class="bi bi-calendar3"></i> Tgl service</span>
                <input type="date" id="search-fromdate" class="form-control form-control-sm" aria-label="Tanggal service dari">
                <span class="cd-sep">s/d</span>
                <input type="date" id="search-todate" class="form-control form-control-sm" aria-label="Tanggal service sampai">
            </div>

            <div class="cd-search afsel-grow">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="search-text" class="form-control form-control-sm"
                       placeholder="Cari no. polisi, nama, telepon, VIN, alamat..." aria-label="Cari di semua kolom">
            </div>

            <button type="submit" class="btn-fi btn-fi-primary btn-fi-sm">
                <i class="bi bi-search"></i>
            </button>
            <button type="button" id="btn-reset" class="btn-fi btn-fi-secondary btn-fi-sm" title="Reset filter">
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>
            @afselcan('customer-data.export')
                <button type="button" id="btn-export" class="btn-fi btn-fi-secondary btn-fi-sm cd-btn-excel" title="Download Excel sesuai filter">
                    <i class="bi bi-file-earmark-excel-fill"></i>
                </button>
            @endafselcan
        </form>

        <div class="card-body">
            <table id="table-customer-data" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>No. Polisi</th>
                        <th>Type Customer</th>
                        <th>Nama Customer</th>
                        <th>Telephone 1</th>
                        <th>Telephone 2</th>
                        <th>Email</th>
                        <th>VIN</th>
                        <th>Engine</th>
                        <th class="text-center">Last Service Date</th>
                        <th class="text-end">Odometer</th>
                        <th>Nama Pemakai</th>
                        <th>Alamat</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<style>
    /* Toolbar filter */
    .afsel-ui .cd-daterange { display: flex; align-items: center; gap: 6px; }
    .afsel-ui .cd-daterange .form-control { width: 140px; }
    .afsel-ui .cd-field-label { font-size: 12px; font-weight: 600; color: var(--mz-ink-2); white-space: nowrap; }
    .afsel-ui .cd-field-label i { color: var(--mz-red); }
    .afsel-ui .cd-sep { font-size: 12px; color: var(--mz-muted); }
    .afsel-ui .cd-search { position: relative; }
    .afsel-ui .cd-search i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--mz-muted); font-size: 12px; pointer-events: none; }
    .afsel-ui .cd-search .form-control { padding-left: 30px; }
    .afsel-ui .cd-btn-excel i { color: #1d6f42; }
    .afsel-ui .cd-btn-excel:hover { border-color: #1d6f42; }

    /* Tabel: header selalu 1 baris, isi rapi */
    .afsel-ui table.dataTable thead th,
    .afsel-ui .dt-scroll-head th { white-space: nowrap !important; }
    .afsel-ui #table-customer-data tbody td { white-space: nowrap; font-size: 12.5px; }
    .afsel-ui #table-customer-data tbody td.cd-alamat { white-space: normal; min-width: 420px; line-height: 1.45; }
    .afsel-ui #table-customer-data tbody tr:hover > td { background: #fafbfc; }

    .afsel-ui .cd-plate {
        display: inline-block; padding: 2px 8px; border-radius: 4px; background: var(--mz-ink); color: #fff;
        font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 12px; font-weight: 700; letter-spacing: .06em;
        box-shadow: inset 0 -2px 0 var(--mz-red);
    }
    .afsel-ui .cd-name { font-weight: 600; }
    .afsel-ui .cd-mono { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 12px; color: var(--mz-ink-2); }

    /* Tabel langsung di bawah toolbar; length + info sebaris di bawah */
    .afsel-ui .card-body > .dt-container > .dt-layout-row:first-child { margin-top: 0; }
    .afsel-ui .cd-length { display: flex; align-items: center; gap: 6px; margin-right: 10px; }
    .afsel-ui .afsel-toolbar .cd-length .form-select { width: auto; min-width: 0; padding-right: 1.9rem; }

    /* DataTables 2: length, info & paging bergaya Mazda */
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
        .afsel-ui .cd-daterange { flex-wrap: wrap; width: 100%; }
        .afsel-ui .cd-daterange .form-control { flex: 1 1 120px; width: auto; }
        .afsel-ui .cd-search { flex-basis: 100%; max-width: none; }
    }
</style>

<script>
    $(function () {
        const empty = '<span class="afsel-muted">-</span>';
        const val = v => (v === null || v === undefined || String(v).trim() === '') ? empty : v;

        const table = $('#table-customer-data').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            scrollX: true,
            scrollY: 'calc(100vh - 340px)', // sama dengan Data Service & Dealer Personel
            scrollCollapse: true,
            pageLength: 25,
            // "per halaman" ada di toolbar (#page-length), jadi tidak ada baris kosong di atas tabel
            layout: {
                topStart: null,
                topEnd: null,
                bottomStart: 'info',
                bottomEnd: 'paging',
            },
            order: [[8, 'desc']],
            ajax: {
                url: '{{ route('aftersales.dealer.customer.customer-data-datatable') }}',
                data: d => Object.assign(d, {
                    srcFromDate: $('#search-fromdate').val(),
                    srcToDate: $('#search-todate').val(),
                    srcText: $('#search-text').val(),
                }),
            },
            columns: [
                { data: 'nomor_polisi', name: 'nomor_polisi', render: v => v ? `<span class="cd-plate">${v}</span>` : empty },
                { data: 'type_customer', name: 'type_customer', render: v => v ? `<span class="afsel-tag">${v}</span>` : empty },
                { data: 'nama_customer', name: 'nama_customer', render: v => v ? `<span class="cd-name">${v}</span>` : empty },
                { data: 'telephone_1', name: 'telephone_1', render: val },
                { data: 'telephone_2', name: 'telephone_2', render: val },
                { data: 'email', name: 'email', render: val },
                { data: 'no_vin', name: 'no_vin', render: v => v ? `<span class="cd-mono">${v}</span>` : empty },
                { data: 'no_mesin', name: 'no_mesin', render: v => v ? `<span class="cd-mono">${v}</span>` : empty },
                { data: 'tanggal_last_service', name: 'tanggal_last_service', className: 'text-center', render: val },
                { data: 'last_odo_meter', name: 'last_odo_meter', className: 'text-end', render: v => v ? `${v} <span class="afsel-muted">km</span>` : empty },
                { data: 'nm_pemakai', name: 'nm_pemakai', render: val },
                { data: 'alamat', name: 'alamat', className: 'cd-alamat', width: '420px', render: val },
            ],
            language: {
                processing: 'Memuat...',
                lengthMenu: '_MENU_ per halaman',
                info: '_START_–_END_ dari _TOTAL_ customer',
                infoEmpty: 'Tidak ada data',
                zeroRecords: 'Customer tidak ditemukan',
                emptyTable: 'Belum ada data customer',
                paginate: { first: '«', previous: '‹', next: '›', last: '»' },
            },
        });

        const filters = () => ({
            srcFromDate: $('#search-fromdate').val(),
            srcToDate: $('#search-todate').val(),
            srcText: $('#search-text').val(),
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
            return true;
        };

        $('#form-search').on('submit', function (e) {
            e.preventDefault();
            if (validDateRange()) table.ajax.reload();
        });

        // Download Excel sesuai filter yang sedang dipakai
        $('#btn-export').on('click', function () {
            if (!validDateRange()) return;

            const total = table.page.info().recordsDisplay;
            if (!total) {
                Swal.fire({ icon: 'info', title: 'Tidak ada data', text: 'Tidak ada customer untuk di-download dengan filter ini.', confirmButtonColor: '#1b2027' });
                return;
            }

            Swal.fire({
                title: 'Menyiapkan file Excel...',
                html: `${total.toLocaleString('id-ID')} customer. Mohon tunggu, jangan tutup halaman ini.`,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            axios.get('{{ route('aftersales.dealer.customer.customer-data-export') }}', {
                params: filters(),
                responseType: 'blob',
                timeout: 600000,
            })
            .then(res => {
                const disposition = res.headers['content-disposition'] || '';
                const match = disposition.match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);
                const fileName = match ? decodeURIComponent(match[1]) : 'Customer-Data.xlsx';

                const url = URL.createObjectURL(res.data);
                const a = Object.assign(document.createElement('a'), { href: url, download: fileName });
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(() => URL.revokeObjectURL(url), 1000);

                Swal.fire({ icon: 'success', title: 'Download selesai', text: fileName, timer: 1800, showConfirmButton: false });
            })
            .catch(err => {
                const status = err.response?.status;
                Swal.fire({
                    icon: 'error',
                    title: 'Download gagal',
                    text: status === 403 ? 'Anda tidak punya akses download Excel Customer Data.' : 'Terjadi kesalahan saat membuat file. Silakan coba lagi.',
                    confirmButtonColor: '#1b2027',
                });
            });
        });

        $('#page-length').on('change', function () {
            table.page.len(Number(this.value)).draw();
        });

        $('#btn-reset').on('click', function () {
            $('#form-search')[0].reset();
            table.page.len(Number($('#page-length').val())).draw();
        });
    });
</script>
@endsection
