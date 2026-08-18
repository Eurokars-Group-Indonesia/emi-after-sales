@extends('sales.atpm.layouts.app')

@section('title', 'User Menu')

@section('navtop')
    {{ view('sales.atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('sales.atpm.layouts.sidebar') }}
@endsection

@php

    $breadcrumbs = [
        ['title' => 'Home', 'url' => route("sales.atpm.home")],
        ['title' => 'User', 'url' => route("sales.atpm.user_index")],
        ['title' => 'User Menu', 'url' => 'javascript:void(0)'],
    ];
@endphp


@section('content')
    
    @csrf

    <div class="content">
        <div class="page-header">
            <div class="page-title">User Menu</div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    @foreach ($breadcrumbs as $item)
                        <li class="breadcrumb-item"><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></li>
                    @endforeach
                </ol>
            </nav>
        </div>

        @php
            // Siapkan array id menu yang sudah dimiliki user untuk pengecekan
            $userMenuIds = [];
            if (!empty($dataMenuUser)) {
                $userMenuIds = $dataMenuUser->pluck('fk_sales_atpm_master_menu')->toArray();
            }

            // Semua menu dikelompokkan by parent_id, root = parent_id null
            $menusByParent = $dataMasterMenu
                                ->sortBy('order')
                                ->groupBy('parent_id');
            $rootMenus     = $menusByParent->get('')
                          ?? $menusByParent->get(null) 
                          ?? collect();
        @endphp

        <div class="row g-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        
                        <form id="formUserMenu" method="POST">
                            @csrf

                            <!-- header hidden field -->
                            <input type="hidden" name="kd_atpm_user" value="{{ $dataUser->kd_atpm_user }}" />

                            <table class="table table-sm table-bordered">
                                <tr>
                                    <td style="width:20%">Kd ATPM User</td>
                                    <td style="width:5%" class="text-center">:</td>
                                    <td>{{ $dataUser->kd_atpm_user }}</td>
                                </tr>
                                <tr>
                                    <td>Name</td>
                                    <td class="text-center">:</td>
                                    <td>{{ $dataUser->nm_atpm_user }}</td>
                                </tr>
                            </table>
                            <hr>

                            <div class="mb-2">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-fi btn-fi-primary btn-fi-sm" id="btnSelectAll">
                                    <i class="bi bi-check-all"></i> Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-fi btn-fi-primary btn-fi-sm" id="btnUnselectAll">
                                    <i class="bi bi-x-lg"></i> Unselect All
                                </button>
                            </div>

                            <div class="menu-list">
                                @include('sales.atpm.page_user._menu_tree', [
                                    'nodes'         => $rootMenus,
                                    'menusByParent' => $menusByParent,
                                    'userMenuIds'   => $userMenuIds,
                                    'depth'         => 0,
                                ])
                            </div>

                            <div class="mt-4">
                                <button type="button" class="btn-fi btn-fi-primary btn-fi-sm" id="btn-model-add">Save</button>
                                <a href="{{ route('sales.atpm.user_index') }}" class="btn-fi btn-fi-secondary btn-fi-sm">
                                    <i class="bi bi-arrow-left"></i> Back
                                </a>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .form-check-label { cursor: pointer; }
        .form-check-input  { cursor: pointer; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            /**
             * Saat checkbox berubah, propagasikan ke semua ancestor secara rekursif.
             * Checked  -> semua ancestor ikut checked.
             * Unchecked -> ancestor uncheck hanya jika tidak ada sibling lain yang masih checked.
             */
            function propagateUp(checkbox) {
                const parentId = checkbox.dataset.parent;
                if (!parentId) return;

                const parentCheckbox = document.querySelector('.menu-checkbox[data-id="' + parentId + '"]');
                if (!parentCheckbox) return;

                if (checkbox.checked) {
                    parentCheckbox.checked = true;
                } else {
                    const siblings  = document.querySelectorAll('.menu-checkbox[data-parent="' + parentId + '"]');
                    const anyChecked = Array.from(siblings).some(cb => cb.checked);
                    parentCheckbox.checked = anyChecked;
                }

                propagateUp(parentCheckbox);
            }

            document.querySelectorAll('.menu-checkbox').forEach(function (cb) {
                cb.addEventListener('change', function () {
                    propagateUp(this);
                });
            });

            // Select All
            document.getElementById('btnSelectAll').addEventListener('click', function () {
                document.querySelectorAll('.menu-checkbox').forEach(cb => cb.checked = true);
            });

            // Unselect All
            document.getElementById('btnUnselectAll').addEventListener('click', function () {
                document.querySelectorAll('.menu-checkbox').forEach(cb => cb.checked = false);
            });

        });


        
        $(function () {

            $('body').on('click', '#btn-model-add', function(){

                const formElement = document.querySelector("#formUserMenu");
                const formData = new FormData(formElement);

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Update Menu',
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