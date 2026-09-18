@extends('sales.atpm.layouts.app')

@section('title', 'User Permission')

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
        ['title' => 'User Permission', 'url' => 'javascript:void(0)'],
    ];

    // Collect IDs that the user already has
    // $dataPermissionUser can be a collection or a single object — normalise to array of IDs
    $activePermissionIds = collect($dataPermissionUser)->pluck('fk_sales_atpm_master_permission')->toArray();

    // Group master permissions by their group
    $groupedPermissions = collect($dataMasterPermission)->groupBy('group');
@endphp


@section('content')

    <div class="content">
        <div class="page-header">
            <div class="page-title">User Permission</div>
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

                        <form id="formUserPermission" method="POST">

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

                            @csrf
                            <input type="hidden" name="kd_atpm_user" value="{{ $dataUser->kd_atpm_user }}" />

                            {{-- Toolbar --}}
                            <div class="perm-toolbar d-flex align-items-center gap-2 mb-4">
                                <button type="button" class="btn-fi btn-fi-secondary btn-fi-sm" id="btnSelectAll">
                                    <i class="bi bi-check-all"></i> Select All
                                </button>
                                <button type="button" class="btn-fi btn-fi-secondary btn-fi-sm" id="btnUnselectAll">
                                    <i class="bi bi-x-lg"></i> Unselect All
                                </button>
                                <div class="ms-auto perm-count-badge" id="permCountBadge">
                                    <i class="bi bi-shield-check me-1"></i>
                                    <span id="permCountText">0</span> permission(s) selected
                                </div>
                            </div>

                            {{-- Permission Groups --}}
                            <div class="perm-groups">
                                @foreach($groupedPermissions as $group => $permissions)
                                    <div class="perm-group-card mb-3">
                                        <div class="perm-group-header">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-folder2-open perm-group-icon"></i>
                                                <span class="perm-group-title">{{ $group }}</span>
                                                <span class="perm-group-count">{{ $permissions->count() }} item(s)</span>
                                            </div>
                                            <button type="button" class="btn-fi btn-fi-ghost btn-fi-sm btn-select-group"
                                                    data-group="{{ $group }}">
                                                <i class="bi bi-check2-square"></i> Select Group
                                            </button>
                                        </div>
                                        <div class="perm-group-body">
                                            <div class="row g-2">
                                                @foreach($permissions as $perm)
                                                    <div class="col-md-6 col-lg-4">
                                                        <label class="perm-item {{ in_array($perm->id, $activePermissionIds) ? 'perm-item--checked' : '' }}"
                                                               data-group="{{ $group }}">
                                                            <input type="checkbox"
                                                                   name="permissions[]"
                                                                   value="{{ $perm->id }}"
                                                                   class="perm-checkbox"
                                                                   {{ in_array($perm->id, $activePermissionIds) ? 'checked' : '' }}>
                                                            <div class="perm-item-body">
                                                                <div class="perm-item-name">
                                                                    <i class="bi bi-shield-lock perm-item-icon"></i>
                                                                    {{ $perm->permission }}
                                                                </div>
                                                                @if(!empty($perm->description))
                                                                    <div class="perm-item-desc">{{ $perm->description }}</div>
                                                                @endif
                                                            </div>
                                                            <div class="perm-item-check">
                                                                <i class="bi bi-check2"></i>
                                                            </div>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                @if($groupedPermissions->isEmpty())
                                    <div class="perm-empty">
                                        <i class="bi bi-inbox perm-empty-icon"></i>
                                        <p>No permissions available.</p>
                                    </div>
                                @endif
                            </div>

                            

                            {{-- Footer Actions --}}
                            <div class="perm-footer mt-4 pt-3">
                                <button type="button" class="btn-fi btn-fi-primary btn-fi-sm" id="btn-model-add">
                                    <i class="bi bi-floppy"></i> Save Changes
                                </button>
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


   


    <script>
        $(function () {

            /* ── Helpers ── */
            function updateItemState($label) {
                const checked = $label.find('.perm-checkbox').is(':checked');
                $label.toggleClass('perm-item--checked', checked);
                $label.find('.perm-item-check').css('opacity', checked ? 1 : 0);
            }

            function updateCount() {
                const count = $('.perm-checkbox:checked').length;
                $('#permCountText').text(count);
            }

            /* ── Init state on load ── */
            $('.perm-item').each(function () {
                updateItemState($(this));
            });
            updateCount();

            /* ── Click on label ── */
            $('.perm-item').on('click', function () {
                const $cb = $(this).find('.perm-checkbox');
                $cb.prop('checked', !$cb.prop('checked'));
                updateItemState($(this));
                updateCount();
            });

            /* ── Select All ── */
            $('#btnSelectAll').on('click', function () {
                $('.perm-checkbox').prop('checked', true);
                $('.perm-item').each(function () { updateItemState($(this)); });
                updateCount();
            });

            /* ── Unselect All ── */
            $('#btnUnselectAll').on('click', function () {
                $('.perm-checkbox').prop('checked', false);
                $('.perm-item').each(function () { updateItemState($(this)); });
                updateCount();
            });

            /* ── Select Group ── */
            $('.btn-select-group').on('click', function (e) {
                e.stopPropagation();
                const group = $(this).data('group');
                const $items = $('[data-group="' + group + '"].perm-item');
                const allChecked = $items.find('.perm-checkbox').toArray().every(cb => cb.checked);

                $items.each(function () {
                    $(this).find('.perm-checkbox').prop('checked', !allChecked);
                    updateItemState($(this));
                });
                updateCount();
            });

            /* ── Save ── */
            $('body').on('click', '#btn-model-add', function () {

                const formElement = document.querySelector('#formUserPermission');
                const formData = new FormData(formElement);

                Swal.fire({
                    title: 'Save Changes?',
                    text: 'Update user permissions for ' + '{{ $dataUser->nm_atpm_user }}',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Save',
                    cancelButtonText: 'Cancel',
                    showLoaderOnConfirm: true,
                    allowOutsideClick: () => !Swal.isLoading(),

                    preConfirm: () => {
                        return axios.post('{{ route('sales.atpm.update_user_permission') }}', formData, {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                            }
                        })
                        .then(function (response) {
                            return response.data;
                        })
                        .catch(function (error) {
                            Swal.showValidationMessage(
                                error.response?.data?.message || 'An error occurred'
                            );
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (result.value && result.value.status === true) {
                            Swal.fire({
                                title: 'Saved!',
                                text: 'Permissions updated successfully.',
                                icon: 'success'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                title: 'Failed!',
                                text: result.value?.message || 'Save data failed.',
                                icon: 'error'
                            });
                        }
                    }
                });
            });

        });
    </script>

@endsection
