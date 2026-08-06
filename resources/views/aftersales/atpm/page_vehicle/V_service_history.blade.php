@extends('aftersales.atpm.layouts.app')

@section('title', 'Service History')

@section('navtop')
    {{ view('aftersales.atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('aftersales.atpm.layouts.sidebar') }}
@endsection

@php

    $breadcrumbs = [
        ['title' => 'Home', 'url' => route("aftersales.atpm.home")],
        ['title' => 'Service History', 'url' => 'javascript:void(0)'],
    ];
@endphp


@section('content')
    
    @csrf

    <div class="content">
        <div class="page-header">
            <div class="page-title">Vehicle Service History</div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    @foreach ($breadcrumbs as $item)
                        <li class="breadcrumb-item"><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></li>
                    @endforeach
                </ol>
            </nav>
        </div>

        <div class="row g-4">
            <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-2">
                            <input type="date" id="search-fromdate" class="form-control form-control-sm" placeholder="From Date">
                        </div>
                        <div class="col-md-2">
                            <input type="date" id="search-todate" class="form-control form-control-sm" placeholder="To Date">
                        </div>
                        <div class="col-md-2">
                            <select type="text" id="search-model" class="form-control form-control-sm">
                                <option value="">- Select Model -</option>
                                @foreach($dataModel as $modelRow)
                                    <option value="{{ $modelRow->kd_model }}">{{ $modelRow->nm_model }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="text" id="search-vin" class="form-control form-control-sm" placeholder="VIN">
                        </div>
                        <!-- <div class="col-md-2">
                            <input type="text" id="search-text" class="form-control form-control-sm" placeholder="Search">
                        </div> -->
                        <div class="col-auto">
                            <button id="btn-search" class="btn-fi btn-fi-primary btn-fi-sm">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table id="table-datatable" class="table table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th class="text-center">Tgl Service</th>
                                <th class="text-center">VIN</th>
                                <th class="text-center">Kd Model</th>
                                <th class="text-center">Model</th>
                                <th class="text-center">Kd Type</th>
                                <!-- <th class="text-center">Type</th> -->
                                <!-- <th class="text-center">No. Mesin</th> -->
                                <th class="text-center">Police No.</th>
                                <th class="text-center">Customer</th>
                                <th class="text-center">Alamat</th>
                                <th class="text-center">Kd Dealer</th>
                                <th class="text-center">Dealer</th>
                                <!-- <th class="text-center">Last Odo Meter</th> -->
                                <th class="text-center">Telephone 1</th>
                                <th class="text-center">Telephone 2</th>
                                <th class="text-center">Req.Pelanggan</th>
                            </tr>
                        </thead>
                    </table>
                </div>

            </div>
        </div>
    </div>



    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

 <script>
        $(function () {

            const optional_config = {
                // enableTime: true,
                time_24hr: true,
                dateFormat: "m/d/Y"
            }

            $("#search-fromdate").flatpickr(optional_config);  
            $("#search-todate").flatpickr(optional_config);  

            const dt = $('#table-datatable').DataTable({
                // processing: true,
                // serverSide: true,
                // scrollX: true,
                // scrollY: 'calc(100vh - 250px)',
                // scrollCollapse: true,
                searching: false,
                processing: true,
                serverSide: true,
                scrollX: true,
                scrollY: 'calc(100vh - 350px)',
                scrollCollapse: true,
                paging: true,
                fixedHeader: true,
                
                ajax: {
                    url: '{{ route("atpm.aftersales.vehicle_service_history_datatable") }}',
                    data: function (d) {
                        d.srcFromDate = $('#search-fromdate').val();
                        d.srcToDate = $('#search-todate').val();
                        d.srcModel = $('#search-model').val();
                        d.srcVin = $('#search-vin').val(); 
                        d.srcText = $('#search-text').val(); 
                    }
                },
                columns: [
                    // { data: 'DT_RowIndex', name: 'DT_RowIndex' },
                    { data: 'date_service', name: 'date_service' },
                    { data: 'no_vin', name: 'no_vin' },
                    { data: 'fk_model', name: 'fk_model' },
                    { data: 'nm_model', name: 'nm_model' },
                    { data: 'fk_type', name: 'fk_type' },
                    { data: 'nomor_polisi', name: 'nomor_polisi' },
                    { data: 'nama_customer', name: 'nama_customer' },
                    { data: 'alamat', name: 'alamat' },
                    { data: 'fk_dealer_service', name: 'fk_dealer_service' },
                    { data: 'dealer_service', name: 'dealer_service' },
                    { data: 'telephone_1', name: 'telephone_1' }, 
                    { data: 'telephone_2', name: 'telephone_2' }, 
                    { data: 'customer_request', name: 'customer_request' }
                ],
                columnDefs: [
                    {
                        targets: 0,
                        className: 'td-tgl_service'
                    },
                    {
                        targets: 3,
                        className: 'td-model'
                    },
                    {
                        targets: 7,
                        className: 'td-alamat'
                    },

                    {
                        targets: 9,
                        width: '100px',
                        className: 'td-dealer'
                    },
                    
                    {
                        targets: 12,
                        width: '100px',
                        className: 'td-req_pelanggan'
                    }
                ]
            });

            $('#btn-search').on('click', function () {
                dt.ajax.reload();
                console.log('test');
            });

            $('#search-input').on('keypress', function (e) {
                if (e.which === 13) dt.search($(this).val()).draw();
            });
        });
    </script>


@endsection