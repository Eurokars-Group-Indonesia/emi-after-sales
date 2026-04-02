@extends('atpm.layouts.app')

@section('title', 'Model Other')

@section('navtop')
    {{ view('atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('atpm.layouts.sidebar') }}
@endsection

@php

    $breadcrumbs = [
        ['title' => 'Home', 'url' => route("atpm.aftersales.home")],
        ['title' => 'Model Other', 'url' => 'javascript:void(0)'],
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
                    <a href="{{ route('atpm.aftersales.model_other_create') }}" class="btn btn-primary btn-sm" id="btn-model-other"> 
                        <i class="bi bi-plus"></i>
                    </a>
                </div>
                <div class="card-body">
                    <table id="model-other-table" class="display">
                        <thead>
                            <tr>
                                <th>Kode Model</th>
                                <th>Model</th>
                                <th>Is Active</th>
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

            $('#model-other-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route("atpm.aftersales.model_other_datatable") }}',
                columns: [
                    // { data: 'DT_RowIndex', name: 'DT_RowIndex' },
                    { data: 'kd_model', name: 'kd_model' },
                    { data: 'nm_model', name: 'nm_model' },
                    { data: 'is_active', name: 'is_active' },
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