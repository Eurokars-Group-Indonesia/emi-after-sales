@extends('atpm.layouts.app')

@section('title', 'Retention Report')

@section('navtop')
    {{ view('atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('atpm.layouts.sidebar') }}
@endsection

@php
    $breadcrumbs = [
        ['title' => 'Home', 'url' => '#'],
        ['title' => 'Report', 'url' => '#'],
        ['title' => 'Retention Report', 'url' => 'javascript:void(0)'],
    ];
@endphp

@section('content')

    <span id="data-detail-uio" style="display:none;"></span>
    <span id="data-detail-customer-visit" style="display:none;"></span>
    <span id="data-detail-gap" style="display:none;"></span>

    <div class="content">
        <div class="page-header">
            <div class="page-title">Report Retention</div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    @foreach ($breadcrumbs as $item)
                        <li class="breadcrumb-item"><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></li>
                    @endforeach
                </ol>
            </nav>
        </div>




        @if($isSyncRunning)
            <div class="row g-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body py-5">

                            {{-- @if (session('information')) --}}
                            <div class="text-center py-4">

                                <div class="sync-icon-wrapper mx-auto mb-4">
                                    <i class="bi bi-arrow-repeat sync-spin"></i>
                                </div>

                                <h5 class="fw-semibold text-dark mb-2">Synchronization in Progress</h5>
                                <p class="text-muted mb-4" style="font-size:15px;">Report Retention cannot be opened because the synchronization process is ongoing.</p>

                                <div class="d-inline-flex align-items-center gap-2 px-4 py-2 rounded-pill" style="background:#fff8e1; border:1px solid #ffe082;">
                                    <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                                    <span class="text-warning fw-medium" style="font-size:13px;">Please wait, this process may take a while...</span>
                                </div>

                            </div>
                            {{-- @endif --}}

                        </div>
                    </div>
                </div>
            </div>

            <style>
                .sync-icon-wrapper {
                    width: 90px;
                    height: 90px;
                    border-radius: 50%;
                    background: linear-gradient(135deg, #e3f0ff, #cce0ff);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 4px 20px rgba(0, 62, 136, 0.15);
                }

                .sync-icon-wrapper i {
                    font-size: 42px;
                    color: #003f88;
                }

                .sync-spin {
                    display: inline-block;
                    animation: spin 1.8s linear infinite;
                }

                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to   { transform: rotate(360deg); }
                }
            </style>

        @else

        <div class="row">
            <div class="col-12">
                <div class="card">
                    {{-- <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-tag"></i> Report Retention</span>
                    </div> --}}
                    <div class="card-body">
                        <form id="parameterSearch">
                            @csrf
                            <div class="row mb-3">
                                <div class="col-md-12 mb-2" style="text-align:right">
                                    <label for="last_finish_sync mb-2">
                                        <span class="badge bg-success   last_finish_sync" style="font-weight:normal;">Last Sync Date: {{ date('m-d-Y h:i:s', strtotime($dataSuccessSyncLogs->end_time)) }}</span>
                                    </label>
                                </div>
                                <div class="clear:both"></div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <select name="kd_dealer[]" id="kd_dealer" class="form-control" multiple>
                                            @foreach ($dataDealer as $dealer)
                                                <option value="{{ $dealer->kd_dealer }}">{{ $dealer->nm_dealer }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-sm btn-secondary me-1 ms-2" id="btn-select-all-dealer" style="padding:0px 5px 0px 5px">Select All</button>
                                        <button type="button" class="btn btn-sm btn-warning" id="btn-unselect-all-dealer" style="padding:0px 5px 0px 5px">Unselect All</button>
                                    </div>
                                    <div class="input-group mb-3">
                                        <select name="tahun" id="tahun" class="form-control">
                                            <option value=""selected>- Tahun -</option>
                                            <option value="2026">2026</option>
                                            <option value="2025">2025</option>
                                            <option value="2024">2024</option>
                                            <option value="2023">2023</option>
                                        </select>
                                    </div>
                                    <div class="input-group mb-3">
                                        <select name="category_customer" id="category_customer" class="form-control">
                                            <option value=""selected>- Category Customer -</option>
                                            <option value="without">Customer Paid (Without 1.000km Check)</option>
                                            <option value="with">Customer Paid (With 1.000km Check)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="input-group">
                                        <select name="kd_model[]" id="kd_model" class="form-control" multiple>
                                            @foreach ($dataModel as $model)
                                                <option value="{{ $model->kd_model }}">{{ $model->nm_model }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-sm btn-secondary me-1 ms-2" id="btn-select-all-model" style="padding:0px 5px 0px 5px">Select All</button>
                                        <button type="button" class="btn btn-sm btn-warning" id="btn-unselect-all-model" style="padding:0px 5px 0px 5px">Unselect All</button>
                                    </div>

                                    <div class="input-group mb-3">
                                        <select name="uio" id="uio" class="form-control">
                                            <option value=""selected>- UIO -</option>
                                            @foreach ($dataUio as $row_dataUio)
                                                <option value="{{ $row_dataUio->kd_uio }}">{{ $row_dataUio->deskripsi }}
                                                </option>
                                            @endforeach




                                            {{-- <option value="1styears">1st Years</option>
                                        <option value="2ndyears">2nd Years</option>
                                        <option value="3rdyears">3rd Years</option>
                                        <option value="4thyears">4th Years</option>
                                        <option value="5thyears">5th Years</option>
                                        <option value="6thyears">6th Years</option>
                                        <option value="7thyears">7th Years</option>
                                        <option value="overall3Years">Overall 3 Years</option>
                                        <option value="overall7Years">Overall 7 Years</option> --}}
                                        </select>
                                    </div>
                                    <div class="input-group mb-3">
                                        <select name="including_vin" id="including_vin" class="form-control">
                                            <option value=""selected>- Including VIN sold by other dealer -</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                    </div>
                                </div>


                                <style>
                                    .report-container-1 {
                                        background-color: #efefef;
                                        /* bisa diganti sesuai tema */
                                        border-radius: 5px;
                                    }

                                    /* Optional: untuk spinner positioning lebih rapi */
                                    #spinner-search {
                                        margin-left: 10px;
                                        /* jarak dari tombol */
                                    }
                                </style>

                                <div class="col-12">
                                    <div class="report-container-1 p-2 rounded bg-light" style="padding:0px!important;">
                                        <div class="row align-items-center">
                                            <div class="col-md-3 d-flex align-items-center">
                                                <button class="btn btn-primary me-2" id="search-report" type="button">
                                                    Search
                                                </button>
                                                <div class="spinner-border d-none" id="spinner-search" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>




                            </div>
                        </form>
                    </div>



                    <style>
                        #report table thead tr th {
                            text-align: center;
                        }
                    </style>

                    <table class="table table-bordered table-hover">
                        <tbody id="report-body">
                            <!-- Row Target (%) -->
                            <tr>
                                <td style="background:#efefef; width:200px">Retention Report</td>
                                <td colspan="12"></td>
                            </tr>
                        </tbody>
                    </table>


                </div>
            </div>
        </div>
    </div>


    {{-- Modal Csutomer Visit --}}
    <style>

    #modalDetailCustomerVisit .modal-dialog {
        max-width: 98vw;
        width: 98vw;
        margin: 1vh auto;
    }

    /* modal full height */
    #modalDetailCustomerVisit .modal-content {
        display: flex;
        flex-direction: column;
        height: 98vh;
        max-height: 98vh;
    }
    #modalDetailCustomerVisit .modal-body {
        flex: 1 1 auto;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        padding: 0;
        min-height: 0;
    }

    /* wrapper DataTables flex */
    #modalDetailCustomerVisit .dataTables_wrapper {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        height: 100%;
        overflow: hidden;
        padding: 6px 10px 0;
        min-height: 0;
    }
    #modalDetailCustomerVisit .dataTables_wrapper .dt-buttons,
    #modalDetailCustomerVisit .dataTables_wrapper .dataTables_length,
    #modalDetailCustomerVisit .dataTables_wrapper .dataTables_filter {
        flex-shrink: 0;
    }
    #modalDetailCustomerVisit .dataTables_wrapper .dataTables_scroll {
        flex: 1 1 auto;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    #modalDetailCustomerVisit .dataTables_wrapper .dataTables_scrollHead {
        flex-shrink: 0;
    }
    #modalDetailCustomerVisit .dataTables_wrapper .dataTables_scrollBody {
        flex: 1 1 auto;
        overflow-y: auto !important;
        overflow-x: auto !important;
        border-bottom: 1px solid #dee2e6 !important;
    }
    /* row info+pagination selalu di bawah */
    #modalDetailCustomerVisit .dataTables_wrapper .dataTables_info,
    #modalDetailCustomerVisit .dataTables_wrapper .dataTables_paginate {
        flex-shrink: 0;
        padding: 4px 0 6px;
    }
    #modalDetailCustomerVisit .dataTables_wrapper > .row:last-child {
        flex-shrink: 0;
        margin-top: auto !important;
        padding-bottom: 4px;
    }

    /* compact table: kurangi padding cell */
    #modalDetailCustomerVisit table.dataTable thead th,
    #modalDetailCustomerVisit table.dataTable tbody td {
        padding: 4px 6px !important;
        font-size: 0.8rem;
        line-height: 1.3;
    }
    #modalDetailCustomerVisit table.dataTable thead th {
        white-space: nowrap;
    }

    /* compact controls */
    #modalDetailCustomerVisit .dataTables_wrapper .dataTables_length,
    #modalDetailCustomerVisit .dataTables_wrapper .dataTables_filter,
    #modalDetailCustomerVisit .dataTables_wrapper .dataTables_info,
    #modalDetailCustomerVisit .dataTables_wrapper .dataTables_paginate {
        font-size: 0.8rem;
    }
    #modalDetailCustomerVisit .dataTables_wrapper .dt-buttons .btn {
        padding: 2px 8px;
        font-size: 0.78rem;
        color: #ffffff;
    }

    </style>
    
    <div class="modal fade" id="modalDetailCustomerVisit" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0">Detail Customer Visit — <span id="modal-customer_visit-bulan"></span></h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <!-- Table -->
                    <div id="modal-customer_visit-content">
                        <table id="dt-customer-visit" class="table table-sm table-hover table-bordered mb-0 small" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th class="text-center">VIN</th>
                                    <th class="text-center">Dealer Sold</th>
                                    <th class="text-center">Tanggal Service</th>
                                    <th class="text-center">Dealer Service</th>
                                    <th class="text-center">Category 1</th>
                                    <th class="text-center">Permintaan Pelanggan</th>
                                </tr>
                            </thead>
                            <tbody id="modal-customer_visit-tbody"></tbody>
                        </table>
                    </div>

                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal UIO --}}
    <style>
    #modalDetailUio .modal-dialog {
        max-width: 98vw;
        width: 98vw;
        margin: 1vh auto;
    }
    #modalDetailUio .modal-content {
        display: flex;
        flex-direction: column;
        height: 98vh;
        max-height: 98vh;
    }
    #modalDetailUio .modal-body {
        flex: 1 1 auto;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        padding: 0;
        min-height: 0;
    }
    #modalDetailUio .dataTables_wrapper {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        height: 100%;
        overflow: hidden;
        padding: 6px 10px 0;
        min-height: 0;
    }
    #modalDetailUio .dataTables_wrapper .dt-buttons,
    #modalDetailUio .dataTables_wrapper .dataTables_length,
    #modalDetailUio .dataTables_wrapper .dataTables_filter { flex-shrink: 0; }
    #modalDetailUio .dataTables_wrapper .dataTables_scroll {
        flex: 1 1 auto;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    #modalDetailUio .dataTables_wrapper .dataTables_scrollHead { flex-shrink: 0; }
    #modalDetailUio .dataTables_wrapper .dataTables_scrollBody {
        flex: 1 1 auto;
        overflow-y: auto !important;
        overflow-x: auto !important;
        border-bottom: 1px solid #dee2e6 !important;
    }
    #modalDetailUio .dataTables_wrapper .dataTables_info,
    #modalDetailUio .dataTables_wrapper .dataTables_paginate {
        flex-shrink: 0;
        padding: 4px 0 6px;
        font-size: 0.8rem;
    }
    #modalDetailUio .dataTables_wrapper > .row:last-child {
        flex-shrink: 0;
        margin-top: auto !important;
        padding-bottom: 4px;
    }
    #modalDetailUio table.dataTable thead th,
    #modalDetailUio table.dataTable tbody td {
        padding: 4px 6px !important;
        font-size: 0.8rem;
        line-height: 1.3;
    }
    #modalDetailUio table.dataTable thead th { white-space: nowrap; }
    #modalDetailUio .dataTables_wrapper .dataTables_length,
    #modalDetailUio .dataTables_wrapper .dataTables_filter { font-size: 0.8rem; }
    #modalDetailUio .dataTables_wrapper .dt-buttons .btn {
        padding: 2px 8px;
        font-size: 0.78rem;
        color: #ffffff;
    }
    </style>
    <div class="modal fade" id="modalDetailUio" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0">Detail UIO — <span id="modal-uio-bulan"></span></h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="modal-uio-content">
                        <table id="dt-uio" class="table table-sm table-hover table-bordered mb-0 small" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th class="text-center">VIN</th>
                                    <th class="text-center">Tanggal Handover</th>
                                    <th class="text-center">Model</th>
                                    <th class="text-center">Dealer</th>
                                </tr>
                            </thead>
                            <tbody id="modal-uio-tbody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Gap --}}
    <style>
    #modalDetailGap .modal-dialog {
        max-width: 98vw;
        width: 98vw;
        margin: 1vh auto;
    }
    #modalDetailGap .modal-content {
        display: flex;
        flex-direction: column;
        height: 98vh;
        max-height: 98vh;
    }
    #modalDetailGap .modal-body {
        flex: 1 1 auto;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        padding: 0;
        min-height: 0;
    }
    #modalDetailGap .dataTables_wrapper {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        height: 100%;
        overflow: hidden;
        padding: 6px 10px 0;
        min-height: 0;
    }
    #modalDetailGap .dataTables_wrapper .dt-buttons,
    #modalDetailGap .dataTables_wrapper .dataTables_length,
    #modalDetailGap .dataTables_wrapper .dataTables_filter { flex-shrink: 0; }
    #modalDetailGap .dataTables_wrapper .dataTables_scroll {
        flex: 1 1 auto;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    #modalDetailGap .dataTables_wrapper .dataTables_scrollHead { flex-shrink: 0; }
    #modalDetailGap .dataTables_wrapper .dataTables_scrollBody {
        flex: 1 1 auto;
        overflow-y: auto !important;
        overflow-x: auto !important;
        border-bottom: 1px solid #dee2e6 !important;
    }
    #modalDetailGap .dataTables_wrapper .dataTables_info,
    #modalDetailGap .dataTables_wrapper .dataTables_paginate {
        flex-shrink: 0;
        padding: 4px 0 6px;
        font-size: 0.8rem;
    }
    #modalDetailGap .dataTables_wrapper > .row:last-child {
        flex-shrink: 0;
        margin-top: auto !important;
        padding-bottom: 4px;
    }
    #modalDetailGap table.dataTable thead th,
    #modalDetailGap table.dataTable tbody td {
        padding: 4px 6px !important;
        font-size: 0.8rem;
        line-height: 1.3;
    }
    #modalDetailGap table.dataTable thead th { white-space: nowrap; }
    #modalDetailGap .dataTables_wrapper .dataTables_length,
    #modalDetailGap .dataTables_wrapper .dataTables_filter { font-size: 0.8rem; }
    #modalDetailGap .dataTables_wrapper .dt-buttons .btn {
        padding: 2px 8px;
        font-size: 0.78rem;
        color: #ffffff;
    }
    </style>
    <div class="modal fade" id="modalDetailGap" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0">Detail GAP — <span id="modal-gap-bulan"></span></h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="modal-gap-content">
                        <table id="dt-gap" class="table table-sm table-hover table-bordered mb-0 small" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th class="text-center">VIN</th>
                                    <th class="text-center">Tanggal Faktur</th>
                                    <th class="text-center">Model</th>
                                    <th class="text-center">Dealer</th>
                                </tr>
                            </thead>
                            <tbody id="modal-gap-tbody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>


    <style>
        .showDetailCustomerVisit, .showDetailUio, .showDetailGap{color:#0000ff}
        .showDetailCustomerVisit:hover, .showDetailUio:hover, .showDetailGap:hover{cursor:pointer; color:#0000ff}
    </style>

    <script type="text/javascript">

        // ################## Klik generate report ################## 
            
            $('body').on('click', '#btn-select-all-dealer', function() {
                $('#kd_dealer option').prop('selected', true);
            });

            $('body').on('click', '#btn-unselect-all-dealer', function() {
                $('#kd_dealer option').prop('selected', false);
            });

            $('body').on('click', '#btn-select-all-model', function() {
                $('#kd_model option').prop('selected', true);
            });

            $('body').on('click', '#btn-unselect-all-model', function() {
                $('#kd_model option').prop('selected', false);
            });

            $('body').on('click', '#search-report', function() {

                // empty json
                document.getElementById('data-detail-uio').dataset.json = JSON.stringify([]);
                document.getElementById('data-detail-customer-visit').dataset.json = JSON.stringify([]);
                document.getElementById('data-detail-gap').dataset.json = JSON.stringify([]);

                $('#search-report').prop('disabled', true);
                $('#report-body').empty();

                const formElement = document.querySelector("#parameterSearch");
                const formData = new FormData(formElement);

                $('#spinner-search').removeClass('d-none');

                axios.post('{{ route('atpm.report.report-retention-retrieve') }}', formData, {
                        headers: {
                            // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        }
                    })
                    .then(function(response) {

                        // console.log('response:',response);

                        $('#search-report').prop('disabled', false);
                        $('#spinner-search').addClass('d-none');

                        var status = response.data.status;
                        
                        if (status) {

                            const data = response.data.reportRetention.resultSummaryReport;
                            const tbody = document.getElementById('report-body');

                            $('#spinner-search').addClass('d-none');

                            // Buat row untuk setiap field
                            // const fields = ["rangePeriode", "bulan", "customer_visit", "uio", "service_retention", "gap"];
                            // const labels = ["rangePeriode", "Bulan", "Customer Visit", "UIO", "Service Retention", "Gap"];

                            const fields = ["bulan", "customer_visit", "uio", "service_retention", "gap"];
                            const labels = ["Bulan", "Customer Visit", "UIO", "Service Retention", "Gap"];

                            fields.forEach((field, idx) => {

                                
                                const tr = document.createElement('tr');

                                // Kolom pertama = label
                                const tdLabel = document.createElement('td');
                                tdLabel.textContent = labels[idx];
                                // tdLabel.style.background = '#efefef';
                                // tdLabel.style.background = '#efefef';
                                // tdLabel.style.whiteSpace = 'nowrap';
                                tdLabel.style.background = 'rgb(58 123 202)';
                                tdLabel.style.color = '#efefef';
                                tdLabel.style.whiteSpace = 'nowrap';
                                tr.appendChild(tdLabel);

                                data.forEach(item => {

                                    const td = document.createElement('td');

                                    // console.log(field, item);

                                    if (field === 'rangePeriode') {
                                        console.log(item[field]);
                                        td.innerHTML = (item[field] || '').replace(/\r?\n/g,
                                        "<br>");
                                    } else if(field == 'customer_visit') {
                                        td.innerHTML = `<div class="showDetailCustomerVisit" data-bulan-customer-visit="${item.bulan}" >${item[field]}</div>`;
                                    } else if(field == 'uio') {
                                        const rangePeriodeUio = (item['rangePeriode'] || '').replace(/\r?\n/g, ' ');
                                        td.innerHTML = `<div class="showDetailUio" data-bulan-uio="${item.bulan}" title="${rangePeriodeUio}">${item[field]}</div>`;
                                    } else if(field == 'service_retention') {
                                        // td.textContent = item[field]+' %';
                                        td.textContent = (item[field] ?? 0) + ' %';
                                    } else if(field == 'gap') {
                                        td.innerHTML = `<div class="showDetailGap" data-bulan-gap="${item.bulan}" >${item[field]}</div>`;
                                    } else if(field == 'bulan'){
                                        td.textContent = item[field];
                                        td.style.background = '#cfe2ff';
                                        td.style.color = '#084298';
                                        td.style.fontWeight = '600';
                                    } else {
                                        td.textContent = item[field];
                                    }

                                    td.style.textAlign = 'center';
                                    tr.appendChild(td);
                                });

                                tbody.appendChild(tr);
                            });

                            // simpan json
                            console.log(response.data.reportRetention.resultDetailCustomerVisit);
                            console.log(response.data.reportRetention.resultDetailUio);
                            console.log(response.data.reportRetention.resultDetailGap);
                            
                            const dataResultDetailCustomerVisit = response.data.reportRetention.resultDetailCustomerVisit;
                            const dataResultDetailUio = response.data.reportRetention.resultDetailUio;
                            const resultDetailGap = response.data.reportRetention.resultDetailGap;

                            // Simpan ke hidden element
                            document.getElementById('data-detail-uio').dataset.json = JSON.stringify(dataResultDetailUio);
                            document.getElementById('data-detail-customer-visit').dataset.json = JSON.stringify(dataResultDetailCustomerVisit);
                            document.getElementById('data-detail-gap').dataset.json = JSON.stringify(resultDetailGap);

                        } else {

                            // $('#spinner-search').css('display', 'none');

                            // console.log(true);
                            var errors = response.data.errors;
                            var messageError = '';

                            Object.keys(errors).forEach(field => {
                                messageError += errors[field][0] + '\n';
                            });

                            alert(messageError);

                            return;
                        }
                    })
                    .catch(function(error) {
                        console.log(error);
                        alert('catch');
                        $('#search-report').prop('disabled', false);
                        $('#spinner-search').addClass('d-none');
                    });
            });
            








        // ################## Customer Visit ################## 
            // fungsi helper filter Customer Visit
            function getDetailCustomerVisitByBulan(bulan) {
                console.log(bulan);

                const raw = document.getElementById('data-detail-customer-visit').dataset.json;
                const data = JSON.parse(raw || '[]');
                return data.filter(item => item.rangePeriode === bulan);
            }


            // click Customer Visit detail
            let dtCustomerVisit = null;

            $('body').on('click', '.showDetailCustomerVisit', function () {
                const bulan = $(this).attr('data-bulan-customer-visit');

                $('#modal-customer_visit-bulan').text(bulan);

                if (dtCustomerVisit) {
                    dtCustomerVisit.destroy();
                    dtCustomerVisit = null;
                    $('#modal-customer_visit-tbody').empty();
                }

                const modalEl = document.getElementById('modalDetailCustomerVisit');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();

                $(modalEl).off('shown.bs.modal').on('shown.bs.modal', function () {
                    const filtered = getDetailCustomerVisitByBulan(bulan);
                    filtered.sort((a, b) => new Date(a.tanggal_service) - new Date(b.tanggal_service));

                    const rows = filtered.map((item, idx) => {
                        const tgl = item.tanggal_service
                            ? (d => `${String(d.getMonth()+1).padStart(2,'0')}/${String(d.getDate()).padStart(2,'0')}/${d.getFullYear()}`)(new Date(item.tanggal_service))
                            : '';
                        return [
                            idx + 1,
                            `<code>${item.no_vin}</code>`,
                            item.dealer_sold,
                            tgl,
                            item.dealer_service,
                            item.category_1,
                            item.permintaan_pelanggan
                        ];
                    });

                    dtCustomerVisit = $('#dt-customer-visit').DataTable({
                        data: rows,
                        columns: [
                            { title: '#',                    className: 'text-center', width: '40px' },
                            { title: 'VIN',                  className: 'text-center' },
                            { title: 'Dealer Sold' },
                            { title: 'Tanggal Service',      className: 'text-center' },
                            { title: 'Dealer Service' },
                            { title: 'Category 1',           className: 'text-center' },
                            { title: 'Permintaan Pelanggan' },
                        ],
                        pageLength: 25,
                        lengthMenu: [10, 25, 50, 100],
                        order: [[3, 'asc']],
                        scrollX: true,
                        scrollY: '100%',
                        scrollCollapse: false,
                        autoWidth: false,
                        columnDefs: [{ targets: 6, width: '28rem' }],
                        dom: "<'row mb-1'<'col-sm-4 d-flex align-items-center'l><'col-sm-4 d-flex justify-content-center'B><'col-sm-4 d-flex justify-content-end'f>>" +
                             "<'row'<'col-12't>>" +
                             "<'row mt-1'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
                        buttons: [
                            {
                                extend: 'copy',
                                text: '<i class="bi bi-clipboard"></i> Copy',
                                className: 'btn btn-sm btn-outline-secondary',
                                exportOptions: { columns: ':visible', orthogonal: 'display' }
                            },
                            {
                                extend: 'excel',
                                text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                                className: 'btn btn-sm btn-outline-success',
                                title: 'Detail Customer Visit - ' + bulan,
                                exportOptions: { columns: ':visible', orthogonal: 'display' }
                            }
                        ],
                        language: {
                            search:      'Cari:',
                            lengthMenu:  'Tampilkan _MENU_ data',
                            info:        'Menampilkan _START_–_END_ dari _TOTAL_ data',
                            infoEmpty:   'Tidak ada data',
                            zeroRecords: 'Tidak ada data yang cocok',
                            emptyTable:  'Tidak ada data untuk periode ini.',
                            paginate:    { previous: '&laquo;', next: '&raquo;' }
                        }
                    });
                });
            });


        // ################## UIO ################## 
        // fungsi helper filter UIO
            function getDetailUioByBulan(bulan) {
                const raw = document.getElementById('data-detail-uio').dataset.json;
                const data = JSON.parse(raw || '[]');
                return data.filter(item => item.periode === bulan);
            }

            let dtUio = null;

            $('body').on('click', '.showDetailUio', function () {
                const bulan = $(this).attr('data-bulan-uio');

                $('#modal-uio-bulan').text(bulan);

                if (dtUio) {
                    dtUio.destroy();
                    dtUio = null;
                    $('#modal-uio-tbody').empty();
                }

                const modalEl = document.getElementById('modalDetailUio');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();

                $(modalEl).off('shown.bs.modal').on('shown.bs.modal', function () {
                    const filtered = getDetailUioByBulan(bulan);
                    filtered.sort((a, b) => new Date(a.tanggal_faktur) - new Date(b.tanggal_faktur));

                    const rows = filtered.map((item, idx) => {
                        const tgl = item.tanggal_faktur
                            ? (d => `${String(d.getMonth()+1).padStart(2,'0')}/${String(d.getDate()).padStart(2,'0')}/${d.getFullYear()}`)(new Date(item.tanggal_faktur))
                            : '';
                        return [idx + 1, `<code>${item.fk_vin}</code>`, tgl, item.nm_model, item.nm_dealer];
                    });

                    dtUio = $('#dt-uio').DataTable({
                        data: rows,
                        columns: [
                            { title: '#',                className: 'text-center', width: '40px' },
                            { title: 'VIN',              className: 'text-center' },
                            { title: 'Tanggal Handover', className: 'text-center' },
                            { title: 'Model',            className: 'text-center' },
                            { title: 'Dealer' },
                        ],
                        pageLength: 25,
                        lengthMenu: [10, 25, 50, 100],
                        order: [[2, 'asc']],
                        scrollX: true,
                        scrollY: '100%',
                        scrollCollapse: false,
                        autoWidth: false,
                        dom: "<'row mb-1'<'col-sm-4 d-flex align-items-center'l><'col-sm-4 d-flex justify-content-center'B><'col-sm-4 d-flex justify-content-end'f>>" +
                             "<'row'<'col-12't>>" +
                             "<'row mt-1'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
                        buttons: [
                            {
                                extend: 'copy',
                                text: '<i class="bi bi-clipboard"></i> Copy',
                                className: 'btn btn-sm btn-outline-secondary',
                                exportOptions: { columns: ':visible', orthogonal: 'display' }
                            },
                            {
                                extend: 'excel',
                                text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                                className: 'btn btn-sm btn-outline-success',
                                title: 'Detail UIO - ' + bulan,
                                exportOptions: { columns: ':visible', orthogonal: 'display' }
                            }
                        ],
                        language: {
                            search:      'Cari:',
                            lengthMenu:  'Tampilkan _MENU_ data',
                            info:        'Menampilkan _START_–_END_ dari _TOTAL_ data',
                            infoEmpty:   'Tidak ada data',
                            zeroRecords: 'Tidak ada data yang cocok',
                            emptyTable:  'Tidak ada data untuk periode ini.',
                            paginate:    { previous: '&laquo;', next: '&raquo;' }
                        }
                    });
                });
            });


        
        // ################## GAP ################## 
            function getDetailGapByBulan(bulan) {
                const raw = document.getElementById('data-detail-gap').dataset.json;
                const data = JSON.parse(raw || '[]');
                return data.filter(item => item.rangePeriode === bulan);
            }

            let dtGap = null;

            // klik showDetailUio
            $('body').on('click', '.showDetailGap', function () {
                const bulan = $(this).attr('data-bulan-gap');

                $('#modal-gap-bulan').text(bulan);

                if (typeof dtGap !== 'undefined' && dtGap) {
                    dtGap.destroy();
                    dtGap = null;
                    $('#modal-gap-tbody').empty();
                }

                const modalEl = document.getElementById('modalDetailGap');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();

                $(modalEl).off('shown.bs.modal').on('shown.bs.modal', function () {
                    const filtered = getDetailGapByBulan(bulan);
                    filtered.sort((a, b) => new Date(a.tanggal_faktur) - new Date(b.tanggal_faktur));

                    const rows = filtered.map((item, idx) => {
                        const tgl = item.tanggal_faktur
                            ? (d => `${String(d.getMonth()+1).padStart(2,'0')}/${String(d.getDate()).padStart(2,'0')}/${d.getFullYear()}`)(new Date(item.tanggal_faktur))
                            : '';
                        return [idx + 1, `<code>${item.fk_vin}</code>`, tgl, item.nm_model, item.nm_dealer];
                    });

                    dtGap = $('#dt-gap').DataTable({
                        data: rows,
                        columns: [
                            { title: '#',              className: 'text-center', width: '40px' },
                            { title: 'VIN',            className: 'text-center' },
                            { title: 'Tanggal Faktur', className: 'text-center' },
                            { title: 'Model',          className: 'text-center' },
                            { title: 'Dealer' },
                        ],
                        pageLength: 25,
                        lengthMenu: [10, 25, 50, 100],
                        order: [[2, 'asc']],
                        scrollX: true,
                        scrollY: '100%',
                        scrollCollapse: false,
                        autoWidth: false,
                        dom: "<'row mb-1'<'col-sm-4 d-flex align-items-center'l><'col-sm-4 d-flex justify-content-center'B><'col-sm-4 d-flex justify-content-end'f>>" +
                             "<'row'<'col-12't>>" +
                             "<'row mt-1'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
                        buttons: [
                            {
                                extend: 'copy',
                                text: '<i class="bi bi-clipboard"></i> Copy',
                                className: 'btn btn-sm btn-outline-secondary',
                                exportOptions: { columns: ':visible', orthogonal: 'display' }
                            },
                            {
                                extend: 'excel',
                                text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                                className: 'btn btn-sm btn-outline-success',
                                title: 'Detail GAP - ' + bulan,
                                exportOptions: { columns: ':visible', orthogonal: 'display' }
                            }
                        ],
                        language: {
                            search:      'Cari:',
                            lengthMenu:  'Tampilkan _MENU_ data',
                            info:        'Menampilkan _START_–_END_ dari _TOTAL_ data',
                            infoEmpty:   'Tidak ada data',
                            zeroRecords: 'Tidak ada data yang cocok',
                            emptyTable:  'Tidak ada data untuk periode ini.',
                            paginate:    { previous: '&laquo;', next: '&raquo;' }
                        }
                    });
                });
            });

    </script>

    @endif

    
@endsection
