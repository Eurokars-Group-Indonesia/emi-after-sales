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
        ['title' => 'ATPM User', 'url' => 'javascript:void(0)'],
    ];
@endphp


@section('content')
    
    @csrf

    <div class="content">
        <div class="page-header">
            <div class="page-title">ATPM User</div>
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
                            <button id="btn-search" class="btn-fi btn-fi-primary btn-fi-sm">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <button id="btn-reset" class="btn-fi btn-fi-secondary btn-fi-sm ms-1">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </div>
                        <div class="col-auto ms-auto">
                            <button class="btn-fi btn-fi-warning btn-fi-sm" id="btn-sync-atpm-user">
                                <i class="bi bi-arrow-repeat"></i> Sync ATPM User
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table id="customer-table" class="table table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th class="text-center">No.</th>
                                <th class="text-center">Username</th>
                                <th class="text-center">Name</th>
                                <th class="text-center">Email</th>
                                <th class="text-center">Action</th>
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

            const dt = $('#customer-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route("atpm.aftersales.atpm_user_datatable") }}',
                dom: '<"row align-items-center mb-2"<"col-auto"l><"col-auto ms-auto"i>>rt<"row mt-2"<"col-auto"p>>',
                language: {
                    lengthMenu: '_MENU_ entries per page',
                    info: '<small class="text-muted">_START_–_END_ of _TOTAL_</small>',
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'username', name: 'username' },
                    { data: 'nm_atpm_user', name: 'nm_atpm_user' },
                    { data: 'email', name: 'email' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
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

            $('body').on('click', '#btn-sync-atpm-user', function(){
                $('#disabler').show();

                axios.get('{{ route('atpm.aftersales.atpm_user_sync') }}', {
                    headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value }
                })
                .then(function(response) {
                    $('#disabler').hide();
                    dt.ajax.reload();
                })
                .catch(function(error) {
                    $('#disabler').hide();
                });
            });
        });
    </script>
@endsection