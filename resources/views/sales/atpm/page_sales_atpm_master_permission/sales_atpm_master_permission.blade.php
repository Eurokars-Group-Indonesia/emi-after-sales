@extends('sales.atpm.layouts.app')

@section('title', 'Sales ATPM Master Permission')

@section('navtop')
    {{ view('sales.atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('sales.atpm.layouts.sidebar') }}
@endsection

@php
    $breadcrumbs = [
        ['title' => 'Home', 'url' => route("sales.atpm.home")],
        ['title' => 'Sales ATPM Master Permission', 'url' => 'javascript:void(0)'],
    ];
@endphp


@section('content')
    
    @csrf

    <div class="content">
        <div class="page-header">
            <div class="page-title">Sales ATPM Master Permission</div>
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
                        <!--                         
                            <div class="col-md-2">
                                <input type="date" id="search-fromdate" class="form-control form-control-sm" placeholder="From Date">
                            </div>
                            <div class="col-md-2">
                                <input type="date" id="search-todate" class="form-control form-control-sm" placeholder="To Date">
                            </div>
                            <div class="col-md-2">
                            
                            </div>
                         -->
                        <div class="col-md-2">
                            <input type="text" id="search-text" class="form-control form-control-sm" placeholder="Search">
                        </div>
                        <div class="col-auto">
                            <button id="btn-search" class="btn-fi btn-fi-primary btn-fi-sm">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <!-- <div class="col-md-1"></div> -->
                        
                        <div class="col-auto">
                            <button id="btn-search" class="btn-fi btn-fi-primary btn-fi-sm">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- <p class="m-0"  style="background:#e8e1e1; padding:5px 15px;">
                    Please be notice that every change on permission have to be adjust on your <code>Code</code>.
                </p> -->
                <div class="card-body">
                    <table id="table-datatable" class="table table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th class="text-center">Description</th>
                                <th class="text-center">Permission</th>
                                <th class="text-center">Group</th>
                                <th class="text-center">Is Active</th>
                                <th class="text-center">#</th>
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
                    url: '{{ route("sales.atpm.system_setup.sales_atpm_master_permission_datatable") }}',
                    data: function (d) {
                        d.srcText = $('#search-text').val(); 
                    }
                },
                columns: [
                    // { data: 'DT_RowIndex', name: 'DT_RowIndex' },
                    { data: 'description', name: 'description' },
                    { data: 'permission', name: 'permission' },
                    { data: 'group', name: 'group' },
                    { data: 'z_is_active', name: 'z_is_active' },
                    { data: 'action', name: 'action' },
                ],
                // columnDefs: [
                //     {
                //         targets: 0,
                //         width: '100px',
                //         className: 'td-permission'
                //     },
                //     {
                //         targets: 1,
                //         className: 'td-descrip'
                //     },
                //     {
                //         targets: 2,
                //         className: 'td-route'
                //     },
                //     {
                //         targets: 3,
                //         className: 'td-icon'
                //     },
                //     {
                //         targets: 4,
                //         className: 'td-parent_id'
                //     },
                //     {
                //         targets: 5,
                //         className: 'td-order'
                //     },
                //     {
                //         targets: 6,
                //         className: 'td-z_is_active'
                //     },
                //     {
                //         targets: 7,
                //         className: 'td-action'
                //     },
                // ]
            });

            $('#btn-search').on('click', function () {
                dt.ajax.reload();
                // console.log('test');
            });

            // $('#search-input').on('keypress', function (e) {
            //     if (e.which === 13) dt.search($(this).val()).draw();
            // });
        });
    </script>


@endsection