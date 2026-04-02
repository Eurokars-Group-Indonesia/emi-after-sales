@extends('atpm.layouts.app')

@section('title', 'Model Other Create')

@section('navtop')
    {{ view('atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('atpm.layouts.sidebar') }}
@endsection

@php

    $breadcrumbs = [
        ['title' => 'Home', 'url' => route("atpm.aftersales.home")],
        ['title' => 'Model Other List', 'url' => route('atpm.aftersales.model_other')],
        ['title' => 'Create', 'url' => 'javascript:void(0)'],
    ];
@endphp


@section('content')

    <div class="content">
        <div class="page-header">
            <div class="page-title">Model Other Create</div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    @foreach ($breadcrumbs as $item)
                        <li class="breadcrumb-item"><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></li>
                    @endforeach
                </ol>
            </nav>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-xs-12">
            <div class="card">
                {{-- <div class="card-header">
                    <div class="btn btn-primary btn-sm" id="btn-model-other"> 
                        <i class="bi bi-plus"></i> Add Model Other
                    </div>
                </div> --}}
                <div class="card-body">
                   <form id="form_model" method="post">

                        @csrf

                        
                        <div class="form-group mb-4">
                            <label for="model" class="mb-1">Model</label>
                            <select class="form-control" name="kd_model">
                                <option value="">- Choose Model -</option>
                                @foreach ($dataModel as $dataModelRow)
                                    <option value="{{ $dataModelRow->kd_model }}">{{ $dataModelRow->nm_model.' ('.$dataModelRow->kd_model.')' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="button" class="btn btn-primary" id="btn-model-add"  style="border-radius: 10px; font-weight: 600; background: #0078d4; border: none;">Add</button>
                    </form>
                </div>

            </div>
        </div>
    </div>






    <script>

        $(function () {

            $('body').on('click', '#btn-model-add', function(){

                const formElement = document.querySelector("#form_model");
                const formData = new FormData(formElement);

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Insert Data',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'Cancel',
                    showLoaderOnConfirm: true,
                    allowOutsideClick: () => !Swal.isLoading(),

                    preConfirm: () => {
                        
                        return axios.post('{{ route('atpm.aftersales.model_other_store') }}', formData,{
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                            }
                        })
                        .then(function(response) {
                            console.log(response);
                            return response.data;
                        })
                        .catch(function(error) {
                            console.log(error);
                            Swal.showValidationMessage(
                                error.response?.data?.message || 'Terjadi error'
                            );
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log(result);
                        if(result.value.status == true)
                        {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: 'Data berhasil disimpan',
                                icon: 'success'
                            });
                        }
                        else 
                        {
                            Swal.fire({
                                title: 'Gagal!',
                                text: 'Data gagal disimpan',
                                icon: 'error'
                            });
                        }
                    }
                });
                


            });
        });

    </script>
@endsection