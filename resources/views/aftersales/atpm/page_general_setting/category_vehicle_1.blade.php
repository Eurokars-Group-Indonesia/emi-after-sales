@extends('aftersales.atpm.layouts.app')

@section('title', 'Cat Service Vehicle 1')

@section('navtop')
    {{ view('aftersales.atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('aftersales.atpm.layouts.sidebar') }}
@endsection

@section('content')
@php
    $countActive = $categories->where('is_active', true)->count();
    $hasAction   = $canEdit || $canDelete;
@endphp
<div class="content afsel-ui">
    <div class="page-header flex-wrap gap-2">
        <div class="page-title">Cat Service Vehicle 1</div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('aftersales.atpm.home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript:void(0)">General Setting</a></li>
                <li class="breadcrumb-item"><a href="javascript:void(0)">Category Service</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cat Service Vehicle 1</li>
            </ol>
        </nav>
    </div>

    <div class="afsel-stats">
        <div class="afsel-stat"><span class="afsel-stat-num">{{ $categories->count() }}</span><span class="afsel-stat-label">Total category</span></div>
        <div class="afsel-stat"><span class="afsel-stat-num">{{ $countActive }}</span><span class="afsel-stat-label">Aktif</span></div>
        @foreach ($jenisList as $jenis)
            <div class="afsel-stat"><span class="afsel-stat-num">{{ $categories->where('jenis', $jenis)->count() }}</span><span class="afsel-stat-label">{{ $jenis }}</span></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">
            <span class="afsel-section-title">Daftar Cat Service Vehicle 1</span>
            <div class="d-flex align-items-center gap-2 ms-auto flex-wrap">
                <select class="form-select form-select-sm" id="filter-jenis" aria-label="Filter jenis">
                    <option value="">Semua jenis</option>
                    @foreach ($jenisList as $jenis)
                        <option value="{{ $jenis }}">{{ $jenis }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm" id="filter-status" aria-label="Filter status">
                    <option value="">Semua status</option>
                    <option value="Aktif">Aktif</option>
                    <option value="Nonaktif">Nonaktif</option>
                </select>
                @if ($canCreate)
                    <button class="btn-fi btn-fi-primary btn-fi-sm" id="btn-add">
                        <i class="bi bi-plus-lg"></i> Tambah Category
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <table id="table-category" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:50px" class="text-center">No</th>
                        <th>Nama Category</th>
                        <th style="width:190px" class="text-center">Jenis</th>
                        <th style="width:110px" class="text-center">Status</th>
                        <th style="width:140px" class="text-center" title="Jumlah Cat Service Vehicle 2 di bawah category ini">Jml Sub Category</th>
                        <th style="width:230px">Kode</th>
                        @if ($hasAction)
                            <th style="width:100px" class="text-center">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr class="{{ $category->is_active ? '' : 'afsel-muted' }}">
                            <td class="text-center"></td>
                            <td><strong>{{ $category->nm_category_vehicles }}</strong></td>
                            <td class="text-center">
                                @if ($category->jenis)
                                    <span class="afsel-tag {{ $category->jenis === 'Customer Service Sales' ? 'is-red' : '' }}">{{ $category->jenis }}</span>
                                @else
                                    <span class="afsel-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="afsel-status {{ $category->is_active ? 'is-on' : 'is-off' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="text-center">{{ $category->children_count }}</td>
                            <td><code>{{ $category->kd_category_vehicles }}</code></td>
                            @if ($hasAction)
                                <td class="text-center text-nowrap">
                                    @if ($canEdit)
                                        <button class="btn-fi btn-fi-secondary btn-fi-sm btn-edit" title="Ubah"
                                                aria-label="Ubah {{ $category->nm_category_vehicles }}"
                                                data-category="{{ json_encode($category->only(['kd_category_vehicles', 'nm_category_vehicles', 'jenis', 'is_active'])) }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    @endif
                                    @if ($canDelete)
                                        <button class="btn-fi btn-fi-secondary btn-fi-sm btn-delete" title="Hapus"
                                                aria-label="Hapus {{ $category->nm_category_vehicles }}"
                                                data-id="{{ $category->kd_category_vehicles }}"
                                                data-name="{{ $category->nm_category_vehicles }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="form-text mt-2">
                <i class="bi bi-info-circle"></i>
                Kode dibuat otomatis dari Document Numbering. Category yang sudah dipakai oleh Cat Service Vehicle 2 atau data service tidak bisa dihapus, cukup dinonaktifkan.
            </div>
        </div>
    </div>
</div>

@if ($canCreate || $canEdit)
{{-- Modal Cat Service Vehicle 1 --}}
<div class="modal fade afsel-ui" id="modal-category" tabindex="-1" aria-labelledby="modal-category-title">
    <div class="modal-dialog">
        <form class="modal-content" id="form-category" onsubmit="return false">
            <div class="modal-header">
                <div>
                    <div class="afsel-eyebrow">Category Service</div>
                    <h6 class="modal-title" id="modal-category-title">Tambah Cat Service Vehicle 1</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="category-id">
                <div class="mb-2" id="wrap-kode" style="display:none">
                    <label class="form-label">Kode</label>
                    <input type="text" class="form-control form-control-sm" id="category-kode" disabled>
                </div>
                <div class="mb-2">
                    <label class="form-label" for="category-name">Nama Category</label>
                    <input type="text" class="form-control form-control-sm" id="category-name" name="nm_category_vehicles" maxlength="255" required placeholder="contoh: Periodical Inspection">
                </div>
                <div class="mb-2">
                    <label class="form-label" for="category-jenis">Jenis</label>
                    <select class="form-select form-select-sm" id="category-jenis" name="jenis" required>
                        <option value="">- Pilih Jenis -</option>
                        @foreach ($jenisList as $jenis)
                            <option value="{{ $jenis }}">{{ $jenis }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="category-is-active" checked>
                    <label class="form-check-label" for="category-is-active">Aktif</label>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn-fi btn-fi-secondary btn-fi-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn-fi btn-fi-primary btn-fi-sm" id="btn-save"><i class="bi bi-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endif

<style>
    .afsel-ui .card-header .form-select { width: auto; min-width: 140px; }
    .afsel-ui tr.afsel-muted td { color: var(--mz-off); }
</style>

@include('aftersales.atpm.page_access._scripts')
<script>
    $(function () {
        const table = $('#table-category').DataTable({
            order: [[3, 'asc'], [2, 'asc'], [1, 'asc']],
            pageLength: 25,
            columnDefs: [
                { targets: 0, orderable: false, searchable: false },
                @if ($hasAction) { targets: -1, orderable: false, searchable: false }, @endif
            ],
            language: {
                search: 'Cari:',
                lengthMenu: 'Tampilkan _MENU_ data',
                info: '_START_ - _END_ dari _TOTAL_ data',
                infoEmpty: 'Tidak ada data',
                infoFiltered: '(difilter dari _MAX_ data)',
                zeroRecords: 'Data tidak ditemukan',
                paginate: { previous: '‹', next: '›' },
            },
        });

        // Nomor urut mengikuti urutan & filter yang tampil
        table.on('draw.dt', function () {
            table.column(0, { search: 'applied', order: 'applied', page: 'applied' }).nodes()
                .each((cell, i) => { cell.innerHTML = table.page.info().start + i + 1; });
        }).draw();

        const exact = v => v ? '^' + $.fn.dataTable.util.escapeRegex(v) + '$' : '';
        $('#filter-jenis').on('change', function () { table.column(2).search(exact(this.value), true, false).draw(); });
        $('#filter-status').on('change', function () { table.column(3).search(exact(this.value), true, false).draw(); });

        const baseUrl = '{{ url('aftersales/atpm/general-setting/category-service-vehicle-1') }}';

        $('#table-category').on('click', '.btn-delete', function () {
            afselRequest({
                method: 'delete',
                url: `${baseUrl}/${$(this).data('id')}`,
                confirm: `Hapus category "${$(this).data('name')}"? Data yang dihapus tidak bisa dikembalikan.`,
                confirmIcon: 'warning',
            });
        });

        const modalEl = document.getElementById('modal-category');
        if (!modalEl) return;

        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('form-category');

        modalEl.addEventListener('shown.bs.modal', () => form.nm_category_vehicles.focus());

        $('#btn-add').on('click', function () {
            form.reset();
            $('#category-id').val('');
            $('#wrap-kode').hide();
            $('#modal-category-title').text('Tambah Cat Service Vehicle 1');
            modal.show();
        });

        $('#table-category').on('click', '.btn-edit', function () {
            const category = $(this).data('category');
            form.reset();
            $('#category-id').val(category.kd_category_vehicles);
            $('#category-kode').val(category.kd_category_vehicles);
            $('#wrap-kode').show();
            form.nm_category_vehicles.value = category.nm_category_vehicles;
            form.jenis.value = category.jenis || '';
            form.is_active.checked = !!category.is_active;
            $('#modal-category-title').text('Ubah Cat Service Vehicle 1');
            modal.show();
        });

        $('#btn-save').on('click', function () {
            if (!form.reportValidity()) return;
            const id = $('#category-id').val();
            afselRequest({
                method: id ? 'put' : 'post',
                url: id ? `${baseUrl}/${id}` : baseUrl,
                data: afselFormData(form),
                confirm: id ? 'Simpan perubahan Cat Service Vehicle 1?' : 'Tambah Cat Service Vehicle 1?',
            });
        });
    });
</script>
@endsection
