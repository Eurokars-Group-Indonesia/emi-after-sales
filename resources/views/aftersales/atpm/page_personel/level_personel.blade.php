@extends('aftersales.atpm.layouts.app')

@section('title', 'Level Personel After Sales')

@section('navtop')
    {{ view('aftersales.atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('aftersales.atpm.layouts.sidebar') }}
@endsection

@section('content')
@php
    $countActive   = $levels->where('is_active', true)->count();
    $countDirect   = $levels->where('jenis', 'Direct')->count();
    $countIndirect = $levels->where('jenis', 'Indirect')->count();
@endphp
<div class="content afsel-ui">
    <div class="page-header flex-wrap gap-2">
        <div class="page-title">Level Personel After Sales</div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('aftersales.atpm.home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript:void(0)">Personel After Sales</a></li>
                <li class="breadcrumb-item active" aria-current="page">Level Personel</li>
            </ol>
        </nav>
    </div>

    <div class="afsel-stats">
        <div class="afsel-stat"><span class="afsel-stat-num">{{ $levels->count() }}</span><span class="afsel-stat-label">Total level</span></div>
        <div class="afsel-stat"><span class="afsel-stat-num">{{ $countActive }}</span><span class="afsel-stat-label">Aktif</span></div>
        <div class="afsel-stat"><span class="afsel-stat-num">{{ $countDirect }}</span><span class="afsel-stat-label">Direct</span></div>
        <div class="afsel-stat"><span class="afsel-stat-num">{{ $countIndirect }}</span><span class="afsel-stat-label">Indirect</span></div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">
            <span class="afsel-section-title">Daftar Level Personel</span>
            <div class="d-flex align-items-center gap-2 ms-auto flex-wrap">
                <select class="form-select form-select-sm " id="filter-jenis" aria-label="Filter jenis">
                    <option value="">Semua jenis</option>
                    @foreach ($jenisList as $jenis)
                        <option value="{{ $jenis }}">{{ $jenis }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm " id="filter-status" aria-label="Filter status">
                    <option value="">Semua status</option>
                    <option value="Aktif">Aktif</option>
                    <option value="Nonaktif">Nonaktif</option>
                </select>
                @if ($canCreate)
                    <button class="btn-fi btn-fi-primary btn-fi-sm" id="btn-add">
                        <i class="bi bi-plus-lg"></i> Tambah Level
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <table id="table-level" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:50px" class="text-center">No</th>
                        <th>Level Personel</th>
                        <th style="width:110px" class="text-center">Jenis</th>
                        <th style="width:110px" class="text-center">Status</th>
                        <th style="width:230px">Kode</th>
                        @if ($canEdit)
                            <th style="width:70px" class="text-center">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($levels as $level)
                        <tr class="{{ $level->is_active ? '' : 'afsel-muted' }}">
                            <td class="text-center"></td>
                            <td><strong>{{ $level->dealer_level_personel }}</strong></td>
                            <td class="text-center">
                                @if ($level->jenis)
                                    <span class="afsel-tag {{ $level->jenis === 'Direct' ? 'is-red' : '' }}">{{ $level->jenis }}</span>
                                @else
                                    <span class="afsel-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="afsel-status {{ $level->is_active ? 'is-on' : 'is-off' }}">{{ $level->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td><code>{{ $level->dealer_level_personel_id }}</code></td>
                            @if ($canEdit)
                                <td class="text-center">
                                    <button class="btn-fi btn-fi-secondary btn-fi-sm btn-edit" title="Ubah"
                                            aria-label="Ubah {{ $level->dealer_level_personel }}"
                                            data-level="{{ json_encode($level) }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="form-text mt-2">
                <i class="bi bi-info-circle"></i>
                Level yang sudah tidak dipakai cukup dinonaktifkan; data tidak dihapus karena dipakai oleh data personel.
            </div>
        </div>
    </div>
</div>

@if ($canCreate || $canEdit)
{{-- Modal Level Personel --}}
<div class="modal fade afsel-ui" id="modal-level" tabindex="-1" aria-labelledby="modal-level-title">
    <div class="modal-dialog">
        <form class="modal-content" id="form-level" onsubmit="return false">
            <div class="modal-header">
                <div>
                    <div class="afsel-eyebrow">Personel After Sales</div>
                    <h6 class="modal-title" id="modal-level-title">Tambah Level Personel</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="level-id">
                <div class="mb-2" id="wrap-kode" style="display:none">
                    <label class="form-label">Kode</label>
                    <input type="text" class="form-control form-control-sm" id="level-kode" disabled>
                </div>
                <div class="mb-2">
                    <label class="form-label" for="level-name">Level Personel</label>
                    <input type="text" class="form-control form-control-sm" id="level-name" name="dealer_level_personel" maxlength="255" required placeholder="contoh: Service Advisor">
                </div>
                <div class="mb-2">
                    <label class="form-label" for="level-jenis">Jenis</label>
                    <select class="form-select form-select-sm" id="level-jenis" name="jenis">
                        <option value="">-</option>
                        @foreach ($jenisList as $jenis)
                            <option value="{{ $jenis }}">{{ $jenis }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Direct = langsung mengerjakan unit (mis. Technician), Indirect = pendukung.</div>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="level-is-active" checked>
                    <label class="form-check-label" for="level-is-active">Aktif</label>
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
        const table = $('#table-level').DataTable({
            order: [[3, 'asc'], [1, 'asc']],
            pageLength: 25,
            columnDefs: [
                { targets: 0, orderable: false, searchable: false },
                @if ($canEdit) { targets: -1, orderable: false, searchable: false }, @endif
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

        const modalEl = document.getElementById('modal-level');
        if (!modalEl) return;

        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('form-level');
        const baseUrl = '{{ url('aftersales/atpm/personel/level-personel') }}';

        modalEl.addEventListener('shown.bs.modal', () => form.dealer_level_personel.focus());

        $('#btn-add').on('click', function () {
            form.reset();
            $('#level-id').val('');
            $('#wrap-kode').hide();
            $('#modal-level-title').text('Tambah Level Personel');
            modal.show();
        });

        $('#table-level').on('click', '.btn-edit', function () {
            const level = $(this).data('level');
            form.reset();
            $('#level-id').val(level.dealer_level_personel_id);
            $('#level-kode').val(level.dealer_level_personel_id);
            $('#wrap-kode').show();
            form.dealer_level_personel.value = level.dealer_level_personel;
            form.jenis.value = level.jenis || '';
            form.is_active.checked = !!level.is_active;
            $('#modal-level-title').text('Ubah Level Personel');
            modal.show();
        });

        $('#btn-save').on('click', function () {
            if (!form.dealer_level_personel.value.trim()) {
                form.dealer_level_personel.focus();
                form.reportValidity();
                return;
            }
            const id = $('#level-id').val();
            afselRequest({
                method: id ? 'put' : 'post',
                url: id ? `${baseUrl}/${id}` : baseUrl,
                data: afselFormData(form),
                confirm: id ? 'Simpan perubahan level personel?' : 'Tambah level personel?',
            });
        });
    });
</script>
@endsection
