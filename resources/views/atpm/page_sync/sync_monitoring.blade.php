@extends('atpm.layouts.app')

@section('title', 'ATPM User')

@section('navtop')
    {{ view('atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('atpm.layouts.sidebar') }}
@endsection

@php

    $breadcrumbs = [
        ['title' => 'Home', 'url' => route("atpm.aftersales.home")],
        ['title' => 'Sync Monitoring', 'url' => 'javascript:void(0)'],
    ];
@endphp


@section('content')
    
    @csrf

    <div class="content">
        <div class="page-header">
            <div class="page-title">Sync Monitoring</div>
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
                        <div class="col-md-4">
                            <input type="text" id="search-input" class="form-control form-control-sm" placeholder="Search...">
                        </div>
                        <div class="col-auto">
                            <button id="btn-search" class="btn btn-primary btn-sm">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <button id="btn-reset" class="btn btn-secondary btn-sm ms-1">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table id="table-datatable" class="display">
                        <thead>
                            <tr>
                                <th class="text-center">No.</th>
                                <th class="text-center">ID</th>
                                <th class="text-center">Job Name</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Start Time</th>
                                <th class="text-center">End Time</th>
                                <th class="text-center">Duration</th>
                                <th class="text-center">Message</th>
                            </tr>
                        </thead>
                    </table>
                </div>

            </div>
        </div>
    </div>






    <style>
        .dt-length select { 
            display: inline-block; 
            width: auto; 
            padding: 2px 6px; 
            font-size: 13px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
    </style>

    <script>
        $(function () {

            const dt = $('#table-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route("atpm.utility.sync_logs_datatable") }}',
                dom: '<"row align-items-center mb-2"<"col-auto"l><"col-auto ms-auto"i>>rt<"row mt-2"<"col-auto"p>>',
                language: {
                    lengthMenu: '_MENU_ entries per page',
                    info: '<small class="text-muted">_START_–_END_ of _TOTAL_</small>',
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'id', name: 'id' },
                    { data: 'job_name', name: 'job_name' },
                    { data: 'status', name: 'status' },
                    { data: 'start_time', name: 'start_time' },
                    { data: 'end_time', name: 'end_time' },
                    { data: 'duration', name: 'duration', orderable: false, searchable: false },
                    { data: 'message', name: 'message' },
                ]
            });

            $('#btn-search').on('click', function () {
                dt.search($('#search-input').val()).draw();
            });

            $('#search-input').on('keypress', function (e) {
                if (e.which === 13) dt.search($(this).val()).draw();
            });

            $('#btn-reset').on('click', function () {
                $('#search-input').val('');
                dt.search('').draw();
            });

            // $('body').on('click', '#btn-sync-atpm-user', function(){

            //     $('#disabler').show();

            //     axios.get('{{ route('atpm.aftersales.atpm_user_sync') }}',{
            //                 headers: {
            //                     'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            //                 }
            //             })
            //             .then(function(response) {

            //                 $('#disabler').hide();

            //                 datatTable.ajax.reload();
            //             })
            //             .catch(function(error) {

            //                 $('#disabler').hide();
                            
            //             });





            // });
        });


        


    </script>
@endsection