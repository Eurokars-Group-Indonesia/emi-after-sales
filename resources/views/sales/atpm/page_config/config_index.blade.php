@extends('sales.atpm.layouts.app')

@section('title', 'Config')

@section('navtop')
    {{ view('sales.atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('sales.atpm.layouts.sidebar') }}
@endsection

@php

    $breadcrumbs = [
        ['title' => 'Home', 'url' => route("sales.atpm.home")],
        ['title' => 'Config', 'url' => 'javascript:void(0)'],
    ];
@endphp


@section('content')
    
    @csrf

    <div class="content">
        <div class="page-header">
            <div class="page-title">Config</div>
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
                    <div class="card-body">
                        
                        <form id="formConfig" method="POST">
                            @csrf

                           
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th class="text-center">No</th>
                                    <th class="text-center">Config</th>
                                    <th class="text-center">Setup</th>
                                </tr>

                                @foreach ($dataConfig as $index => $dataConfigRow)
                                    
                                    <tr>
                                        <td class="text-center" style="width:10%">{{ ++$index }}</td>
                                        <td class="text-center" style="width:20%">{{ $dataConfigRow->config }}</td>
                                        <td>
                                            <input type="text" value="{{ $dataConfigRow->setup }}" style="width:100%;"/>
                                        </td>
                                    </tr>
                                @endforeach
                                
                            </table>
                            <hr>



                            <div class="mt-4">
                                <button type="button" class="btn-fi btn-fi-primary btn-fi-sm" id="btn-model-add">Save</button>
                                <a href="{{ route('sales.atpm.home') }}" class="btn-fi btn-fi-secondary btn-fi-sm">
                                    <i class="bi bi-arrow-left"></i> Home
                                </a>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

   
    <script>
       
        $(function () {

            $('body').on('click', '#btn-model-add', function(){

                const formElement = document.querySelector("#formConfig");
                const formData = new FormData(formElement);

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Update Config',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'Cancel',
                    showLoaderOnConfirm: true,
                    allowOutsideClick: () => !Swal.isLoading(),

                    preConfirm: () => {
                        
                        return axios.post('{{ route('sales.atpm.update_user_menu') }}', formData,{
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
                        // console.log(result);
                        if(result.value.status == true)
                        {
                            Swal.fire({
                                title: 'Succsess!',
                                text: 'Save Data Success',
                                icon: 'success'
                            });

                            location.reload()
                        }
                        else 
                        {
                            Swal.fire({
                                title: 'Failed!',
                                text: 'Save Data Failed',
                                icon: 'error'
                            });
                        }
                    }
                });
                


            });
        });





    </script>
@endsection