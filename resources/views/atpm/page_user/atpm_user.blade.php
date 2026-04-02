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
                    <div class="btn btn-primary btn-sm" id="btn-sync-atpm-user"> 
                        <i class="bi bi-arrow-repeat"></i> Sync - ATPM User
                    </div>
                </div>
                <div class="card-body">
                    <table id="customer-table" class="display">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>

            </div>
        </div>
    </div>






    <script>
        $(function () {

            $('#customer-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route("atpm.aftersales.atpm_user_datatable") }}',
                columns: [
                    // { data: 'DT_RowIndex', name: 'DT_RowIndex' },
                    { data: 'username', name: 'username' },
                    { data: 'nm_atpm_user', name: 'nm_atpm_user' },
                    { data: 'email', name: 'email' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ]
            });

            $('body').on('click', '#btn-sync-atpm-user', function(){

                $('#disabler').show();

                axios.get('{{ route('atpm.aftersales.atpm_user_sync') }}',{
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                            }
                        })
                        .then(function(response) {

                            $('#disabler').hide();

                            datatTable.ajax.reload();
                        })
                        .catch(function(error) {

                            $('#disabler').hide();
                            
                        });





            });
        });


        


    </script>
@endsection