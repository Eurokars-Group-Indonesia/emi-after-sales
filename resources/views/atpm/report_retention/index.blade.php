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

                                <div class="col-md-6">
                                    <div class="input-group mb-3">
                                        <select name="kd_dealer[]" id="kd_dealer" class="form-control" multiple>
                                            @foreach ($dataDealer as $dealer)
                                                <option value="{{ $dealer->kd_dealer }}">{{ $dealer->nm_dealer }}</option>
                                            @endforeach
                                        </select>
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
                                            <option value="without">Customer Paid(Without 1.000km Check)</option>
                                            <option value="with">Customer Paid(With 1.000km Check)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="input-group mb-3">
                                        <select name="kd_model[]" id="kd_model" class="form-control" multiple>
                                            @foreach ($dataModel as $model)
                                                <option value="{{ $model->kd_model }}">{{ $model->nm_model }}</option>
                                            @endforeach
                                        </select>
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

    #modalDetailCustomerVisit .modal-dialog
    {
        max-width: 1400px; /* sesuaikan dengan kebutuhan */
        width: 95%;
    }
    </style>
    
    <div class="modal fade" id="modalDetailCustomerVisit" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable ">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0">Detail Customer Visit — <span id="modal-customer_visit-bulan"></span></h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">

                    <!-- Table -->
                    <div id="modal-customer_visit-content" style="display:none;">
                        <table class="table table-sm table-hover table-bordered mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th class="text-center">VIN</th>
                                    <th class="text-center">Dealer Sold</th>
                                    <th class="text-center">Tanggal Service</th>
                                    <th class="text-center">Dealer Service</th>
                                    <th class="text-center">Category 1</th>
                                    <th class="text-center" style="width:28rem;">Permintaan Pelanggan</th>
                                </tr>
                            </thead>
                            <tbody id="modal-customer_visit-tbody"></tbody>
                        </table>
                    </div>

                    <!-- Empty state -->
                    <div id="modal-customer_visit-empty" class="text-center text-secondary py-4 small" style="display:none;">
                        Tidak ada data untuk periode ini.
                    </div>

                </div>
                <div class="modal-footer py-2">
                    <span class="text-secondary small me-auto" id="modal-customer_visit-count"></span>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal UIO --}}
    <div class="modal fade" id="modalDetailUio" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0">Detail UIO — <span id="modal-uio-bulan"></span></h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">

                    <!-- Loader -->
                    {{-- <div id="modal-uio-loader" class="d-flex align-items-center gap-2 px-3 py-3">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span class="text-secondary small">Loading...</span>
                    </div> --}}
                    <!-- Table -->
                    <div id="modal-uio-content" style="display:none;">
                        <table class="table table-sm table-hover table-bordered mb-0 small">
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

                    <!-- Empty state -->
                    <div id="modal-uio-empty" class="text-center text-secondary py-4 small" style="display:none;">
                        Tidak ada data untuk periode ini.
                    </div>

                </div>
                <div class="modal-footer py-2">
                    <span class="text-secondary small me-auto" id="modal-uio-count"></span>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Gap --}}
    <div class="modal fade" id="modalDetailGap" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0">Detail GAP — <span id="modal-gap-bulan"></span></h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">

                    <!-- Table -->
                    <div id="modal-gap-content" style="display:none;">
                        <table class="table table-sm table-hover table-bordered mb-0 small">
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

                    <!-- Empty state -->
                    <div id="modal-gap-empty" class="text-center text-secondary py-4 small" style="display:none;">
                        Tidak ada data untuk periode ini.
                    </div>

                </div>
                <div class="modal-footer py-2">
                    <span class="text-secondary small me-auto" id="modal-gap-count"></span>
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
                            const fields = ["rangePeriode", "bulan", "customer_visit", "uio", "service_retention", "gap"];
                            const labels = ["rangePeriode", "Bulan", "Customer Visit", "UIO", "Service Retention", "Gap"];

                            fields.forEach((field, idx) => {

                                
                                const tr = document.createElement('tr');

                                // Kolom pertama = label
                                const tdLabel = document.createElement('td');
                                tdLabel.textContent = labels[idx];
                                tdLabel.style.background = '#efefef';
                                tdLabel.style.background = '#efefef';
                                tdLabel.style.whiteSpace = 'nowrap';
                                tr.appendChild(tdLabel);

                                data.forEach(item => {

                                    const td = document.createElement('td');

                                    // console.log(field, item);

                                    if (field === 'rangePeriode') {
                                        td.innerHTML = (item[field] || '').replace(/\r?\n/g,
                                        "<br>");
                                    } else if(field == 'customer_visit') {
                                        td.innerHTML = `<div class="showDetailCustomerVisit" data-bulan-customer-visit="${item.bulan}" >${item[field]}</div>`;
                                    } else if(field == 'uio') {
                                        td.innerHTML = `<div class="showDetailUio" data-bulan-uio="${item.bulan}" >${item[field]}</div>`;
                                    } else if(field == 'service_retention') {
                                        td.textContent = item[field]+' %';
                                    } else if(field == 'gap') {
                                        td.innerHTML = `<div class="showDetailGap" data-bulan-gap="${item.bulan}" >${item[field]}</div>`;
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
            $('body').on('click', '.showDetailCustomerVisit', function () {
                const bulan = $(this).attr('data-bulan-customer-visit');

                // reset state
                $('#modal-customer_visit-bulan').text(bulan);
                $('#modal-customer_visit-content').hide();
                $('#modal-customer_visit-empty').hide();
                $('#modal-customer_visit-count').text('');
                $('#modal-customer_visit-tbody').empty();

                // tampilkan modal
                const modalEl = document.getElementById('modalDetailCustomerVisit');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();

                $(modalEl).off('shown.bs.modal').on('shown.bs.modal', function () {
                    const filtered = getDetailCustomerVisitByBulan(bulan);

                    if (!filtered || filtered.length === 0) {
                        $('#modal-customer_visit-empty').show();
                        return;
                    }

                    filtered.forEach((item, idx) => {
                        $('#modal-customer_visit-tbody').append(`
                            <tr>
                                <td class="text-center">${idx + 1}</td>
                                <td class="text-center"><code>${item.no_vin}</code></td>
                                <td>${item.dealer_sold}</td>
                                <td class="text-center">${item.tanggal_service}</td>
                                <td>${item.dealer_service}</td>
                                <td>${item.category_1}</td>
                                <td>${item.permintaan_pelanggan}</td>
                            </tr>
                        `);
                    });

                    $('#modal-customer_visit-count').text(`${filtered.length} data ditemukan`);
                    $('#modal-customer_visit-content').show();
                });
            });


        // ################## UIO ################## 
        // fungsi helper filter UIO
            function getDetailUioByBulan(bulan) {
                const raw = document.getElementById('data-detail-uio').dataset.json;
                const data = JSON.parse(raw || '[]');
                return data.filter(item => item.periode === bulan);
            }

            // klik showDetailUio
            $('body').on('click', '.showDetailUio', function () {
                const bulan = $(this).attr('data-bulan-uio');

                // reset state
                $('#modal-uio-bulan').text(bulan);
                // $('#modal-uio-loader').show();
                $('#modal-uio-content').hide();
                $('#modal-uio-empty').hide();
                $('#modal-uio-count').text('');
                $('#modal-uio-tbody').empty();

                // tampilkan modal
                const modalEl = document.getElementById('modalDetailUio');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();

                $(modalEl).off('shown.bs.modal').on('shown.bs.modal', function () {
                    const filtered = getDetailUioByBulan(bulan); // bulan dari closure, selalu fresh

                    // $('#modal-uio-loader').hide();

                    if (!filtered || filtered.length === 0) {
                        $('#modal-uio-empty').show();
                        return;
                    }

                    filtered.forEach((item, idx) => {
                        $('#modal-uio-tbody').append(`
                            <tr>
                                <td class="text-center">${idx + 1}</td>
                                <td class="text-center"><code>${item.fk_vin}</code></td>
                                <td class="text-center">${item.tanggal_faktur}</td>
                                <td class="text-center">${item.nm_model}</td>
                                <td>${item.nm_dealer}</td>
                            </tr>
                        `);
                    });

                    $('#modal-uio-count').text(`${filtered.length} data ditemukan`);
                    $('#modal-uio-content').show();
                });
            });


        
        // ################## GAP ################## 
            function getDetailGapByBulan(bulan) {
                const raw = document.getElementById('data-detail-gap').dataset.json;
                const data = JSON.parse(raw || '[]');
                return data.filter(item => item.rangePeriode === bulan);
            }

            // klik showDetailUio
            $('body').on('click', '.showDetailGap', function () {
                const bulan = $(this).attr('data-bulan-gap');

                // reset state
                $('#modal-gap-bulan').text(bulan);
                // $('#modal-uio-loader').show();
                $('#modal-gap-content').hide();
                $('#modal-gap-empty').hide();
                $('#modal-gap-count').text('');
                $('#modal-gap-tbody').empty();

                // tampilkan modal
                const modalEl = document.getElementById('modalDetailGap');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();

                $(modalEl).off('shown.bs.modal').on('shown.bs.modal', function () {

                    const filtered = getDetailGapByBulan(bulan); // bulan dari closure, selalu fresh

                    // $('#modal-uio-loader').hide();

                    if (!filtered || filtered.length === 0) {
                        $('#modal-gap-empty').show();
                        return;
                    }

                    filtered.forEach((item, idx) => {
                        $('#modal-gap-tbody').append(`
                            <tr>
                                <td class="text-center">${idx + 1}</td>
                                <td class="text-center"><code>${item.fk_vin}</code></td>
                                <td class="text-center">${item.tanggal_faktur}</td>
                                <td class="text-center">${item.nm_model}</td>
                                <td>${item.nm_dealer}</td>
                            </tr>
                        `);
                    });

                    $('#modal-gap-count').text(`${filtered.length} data ditemukan`);
                    $('#modal-gap-content').show();
                });
            });

    </script>

    @endif

    
@endsection
