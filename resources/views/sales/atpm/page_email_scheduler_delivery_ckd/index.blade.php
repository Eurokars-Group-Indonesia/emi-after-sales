@extends('sales.atpm.layouts.app')

@section('title', 'Email Scheduler CKD')

@section('navtop')
    {{ view('sales.atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('sales.atpm.layouts.sidebar') }}
@endsection

@php

    $breadcrumbs = [
        ['title' => 'Home', 'url' => route("sales.atpm.home")],
        ['title' => 'Email Scheduler CKD', 'url' => 'javascript:void(0)'],
    ];
@endphp


@section('content')
    
    @csrf

    <div class="content">
        <div class="page-header">
            <div class="page-title">Email Scheduler CKD</div>
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
                        <b><i class="bi bi-envelope-paper me-1"></i> Pengaturan Email</b>
                    </div>
                    <div class="card-body">
                        <div class="ckd-note mb-4">
                            <div class="ckd-note-title">
                                <i class="bi bi-info-circle-fill me-1"></i> Catatan
                            </div>
                            <ul class="mb-0">
                                <li><i class="bi bi-clock me-1"></i> Email ini akan dijalankan setiap <b>jam 4 sore</b> setiap hari.</li>
                                <li><i class="bi bi-calendar-check me-1"></i> Data yang dikirimkan adalah data delivery berdasarkan <b>tanggal input user</b>.</li>
                                <li><i class="bi bi-exclamation-triangle me-1"></i> VIN bisa jadi terkirim <b>lebih dari sekali</b> jika action log bukan <b>IA</b>.</li>
                            </ul>
                        </div>

                        <label for="email-to" class="form-label">Email To</label>
                        <div class="row g-2 align-items-start">
                            <div class="col-md-8">
                                <input type="text" id="email-to" name="email-to" class="form-control form-control-sm" placeholder="contoh: user1@mazda.co.id, user2@mazda.co.id" value="{{ $setup->email_to ?? '' }}">
                                <div class="form-text">Pisahkan dengan koma jika lebih dari satu email.</div>
                            </div>
                            <div class="col-auto">
                                <button id="btn-update" class="btn-fi btn-fi-primary btn-fi-sm">
                                    <i class="bi bi-save me-1"></i> Update
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <br/>

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
                                    <i class="bi bi-search"></i>
                                </button>
                                <button id="btn-reset" class="btn-fi btn-fi-secondary btn-fi-sm ms-1">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="email-scheduler-ckd-table" class="table table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="text-center">No.</th>
                                    <th class="text-center">Tanggal Kirim</th>
                                    <th class="text-center">Email To</th>
                                    <th class="text-center">Status Sent</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <br/>

        <div class="row g-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <b>Log Retry Send Email</b>
                    </div>
                    <div class="card-body">
                        <table id="email-scheduler-ckd-retry-log-table" class="table table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="text-center">No.</th>
                                    <th class="text-center">Tanggal Klik Retry</th>
                                    <th class="text-center">Retry By</th>
                                    <th class="text-center">Tanggal Data Input</th>
                                    <th class="text-center">Email To</th>
                                    <th class="text-center">Tanggal Kirim</th>
                                    <th class="text-center">Status Sent</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>






    <style>
        .ckd-note {
            background: #eef4fb;
            border-left: 4px solid #002856;
            border-radius: 4px;
            padding: 12px 16px;
            font-size: 13px;
        }
        .ckd-note-title {
            font-weight: 600;
            color: #002856;
            margin-bottom: 6px;
        }
        .ckd-note ul {
            list-style: none;
            padding-left: 0;
        }
        .ckd-note li {
            padding: 2px 0;
        }
        .ckd-note li i {
            color: #002856;
        }

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

            const dt = $('#email-scheduler-ckd-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route("sales.atpm.email_scheduler.delivery_ckd_datatable") }}',
                dom: '<"row align-items-center mb-2"<"col-auto"l><"col-auto ms-auto"i>>rt<"row mt-2"<"col-auto"p>>',
                autoWidth: true,
                order: [],
                language: {
                    lengthMenu: '_MENU_ entries per page',
                    info: '<small class="text-muted">_START_–_END_ of _TOTAL_</small>',
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'date_sent', name: 'date_sent' },
                    { data: 'email_to', name: 'email_to', defaultContent: '-' },
                    { data: 'z_status_sent', name: 'status_sent' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                ]
            });

            const dtRetryLog = $('#email-scheduler-ckd-retry-log-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route("sales.atpm.email_scheduler.delivery_ckd_retry_log_datatable") }}',
                dom: '<"row align-items-center mb-2"<"col-auto"l><"col-auto ms-auto"i>>rt<"row mt-2"<"col-auto"p>>',
                autoWidth: true,
                order: [],
                searching: false,
                language: {
                    lengthMenu: '_MENU_ entries per page',
                    info: '<small class="text-muted">_START_–_END_ of _TOTAL_</small>',
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'retry_date', name: 'retry.retry_date' },
                    { data: 'retry_by', name: 'retry.retry_by' },
                    { data: 'data_date', name: 'log.created_at' },
                    { data: 'email_to', name: 'retry.email_to' },
                    { data: 'date_sent', name: 'retry.date_sent' },
                    { data: 'z_status_sent', name: 'retry.status_sent' }
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

            $('body').on('click', '#btn-update', function(){
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Update Email To',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'Cancel',
                    showLoaderOnConfirm: true,
                    allowOutsideClick: () => !Swal.isLoading(),

                    preConfirm: () => {
                        return axios.post('{{ route('sales.atpm.email_scheduler.delivery_ckd_update_email_to') }}', {
                            email_to: $('#email-to').val()
                        }, {
                            headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value }
                        })
                        .then(function(response) {
                            return response.data;
                        })
                        .catch(function(error) {
                            Swal.showValidationMessage(
                                error.response?.data?.message || 'Terjadi error'
                            );
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: result.value.status ? 'Success!' : 'Failed!',
                            text: result.value.message,
                            icon: result.value.status ? 'success' : 'error'
                        });
                    }
                });
            });

            $('body').on('click', '.btn-retry', function(){
                const id = $(this).data('id');

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Kirim ulang email ini?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'Cancel',
                    showLoaderOnConfirm: true,
                    allowOutsideClick: () => !Swal.isLoading(),

                    preConfirm: () => {
                        return axios.post('{{ route('sales.atpm.email_scheduler.delivery_ckd_retry') }}', { id: id }, {
                            headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value }
                        })
                        .then(function(response) {
                            return response.data;
                        })
                        .catch(function(error) {
                            Swal.showValidationMessage(
                                error.response?.data?.message || 'Terjadi error'
                            );
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: result.value.status ? 'Success!' : 'Failed!',
                            text: result.value.message,
                            icon: result.value.status ? 'success' : 'error'
                        });
                        dt.ajax.reload(null, false);
                        dtRetryLog.ajax.reload(null, false);
                    }
                });
            });
        });
    </script>
@endsection