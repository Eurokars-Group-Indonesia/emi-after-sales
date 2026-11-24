@extends('aftersales.atpm.layouts.app')

@section('title', 'Model Other')

@section('navtop')
    {{ view('aftersales.atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('aftersales.atpm.layouts.sidebar') }}
@endsection

@section('content')
<div class="content afsel-ui">
    <div class="page-header flex-wrap gap-2">
        <div class="page-title">Model Other</div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('aftersales.atpm.home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript:void(0)">Administration</a></li>
                <li class="breadcrumb-item active" aria-current="page">Model Other</li>
            </ol>
        </nav>
    </div>

    <div class="afsel-stats">
        <div class="afsel-stat"><span class="afsel-stat-num">{{ $countTotal }}</span><span class="afsel-stat-label">Total model other</span></div>
        <div class="afsel-stat"><span class="afsel-stat-num">{{ $countActive }}</span><span class="afsel-stat-label">Aktif</span></div>
        <div class="afsel-stat"><span class="afsel-stat-num">{{ $availableModels->count() }}</span><span class="afsel-stat-label">Model belum masuk</span></div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">
            <div class="min-w-0">
                <span class="afsel-section-title">Daftar Model Other</span>
                <span class="afsel-sub">Model yang ikut dihitung saat filter model <strong>Other</strong> dipilih di Report Retention.</span>
            </div>
            @if ($canCreate)
                <button type="button" class="btn-fi btn-fi-primary btn-fi-sm ms-auto" id="btn-add">
                    <i class="bi bi-plus-lg"></i> Tambah Model
                </button>
            @endif
        </div>
        <div class="card-body">
            <table id="model-other-table" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:50px" class="text-center">No</th>
                        <th style="width:140px">Kode Model</th>
                        <th>Nama Model</th>
                        <th style="width:110px" class="text-center">Status</th>
                        @if ($canEdit)
                            <th style="width:70px" class="text-center">Aksi</th>
                        @endif
                    </tr>
                </thead>
            </table>
            <div class="form-text mt-2">
                <i class="bi bi-info-circle"></i>
                Hanya model berstatus <strong>Aktif</strong> yang digabungkan ke kelompok Other. Model yang tidak dipakai cukup dinonaktifkan; data tidak dihapus.
            </div>
        </div>
    </div>
</div>

@if ($canCreate || $canEdit)
{{-- Modal Tambah / Ubah Model Other --}}
<div class="modal fade afsel-ui" id="modal-model-other" tabindex="-1" aria-labelledby="modal-model-other-title">
    <div class="modal-dialog">
        <form class="modal-content" id="form-model-other" onsubmit="return false">
            <div class="modal-header">
                <div>
                    <div class="afsel-eyebrow">Administration</div>
                    <h6 class="modal-title" id="modal-model-other-title">Tambah Model Other</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="model-other-id">

                <div class="afsel-note mb-0" id="note-all-taken" style="display:none">
                    <i class="bi bi-check2-circle"></i>
                    <span>Semua model After Sales sudah masuk daftar Model Other.</span>
                </div>

                <div id="wrap-model">
                    <label class="form-label" for="kd-model">Model</label>
                    <select class="form-select form-select-sm" id="kd-model" name="kd_model" data-afsel-search>
                        <option value="">- Pilih model -</option>
                        @foreach ($allModels as $model)
                            <option value="{{ $model->kd_model }}" data-label="{{ $model->nm_model }}" data-code="{{ $model->kd_model }}">{{ $model->nm_model }} ({{ $model->kd_model }})</option>
                        @endforeach
                    </select>
                    <div class="form-text">Cari berdasarkan nama atau kode model. Model yang sudah ada di daftar tidak ditampilkan.</div>
                </div>

                <label class="afsel-setting mt-3 mb-0" id="wrap-status" for="model-is-active" style="display:none">
                    <span>
                        <span class="afsel-setting-title">Aktif</span>
                        <span class="afsel-setting-desc">Jika dinonaktifkan, model tidak lagi digabungkan ke kelompok Other.</span>
                    </span>
                    <span class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" name="is_active" id="model-is-active" checked>
                    </span>
                </label>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn-fi btn-fi-secondary btn-fi-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn-fi btn-fi-primary btn-fi-sm" id="btn-save"><i class="bi bi-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endif

@include('aftersales.atpm.page_access._scripts')
@include('aftersales.atpm.components.searchable-select')
<script>
    $(function () {
        const esc = $.fn.dataTable.render.text().display;
        const canEdit = @json($canEdit);

        const columns = [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'kd_model', name: 'kd_model', render: d => `<code>${esc(d)}</code>` },
            { data: 'nm_model', name: 'nm_model', render: d => `<strong>${esc(d)}</strong>` },
            {
                data: 'is_active', name: 'is_active', searchable: false, className: 'text-center',
                render: d => Number(d) === 1
                    ? '<span class="afsel-status is-on">Aktif</span>'
                    : '<span class="afsel-status is-off">Nonaktif</span>',
            },
        ];
        if (canEdit) {
            columns.push({
                data: null, orderable: false, searchable: false, className: 'text-center',
                render: (d, t, row) => `<button type="button" class="btn-fi btn-fi-secondary btn-fi-sm btn-edit" title="Ubah" aria-label="Ubah ${esc(row.nm_model)}"><i class="bi bi-pencil"></i></button>`,
            });
        }

        const table = $('#model-other-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('aftersales.atpm.model_other_datatable') }}',
            order: [[2, 'asc']],
            pageLength: 25,
            columns,
            createdRow: (row, data) => { if (Number(data.is_active) !== 1) row.classList.add('afsel-muted'); },
            language: {
                processing: 'Memuat data...',
                search: 'Cari:',
                lengthMenu: 'Tampilkan _MENU_ data',
                info: '_START_ - _END_ dari _TOTAL_ data',
                infoEmpty: 'Tidak ada data',
                infoFiltered: '(difilter dari _MAX_ data)',
                emptyTable: 'Belum ada model di daftar Model Other.',
                zeroRecords: 'Data tidak ditemukan',
                paginate: { previous: '‹', next: '›' },
            },
        });

        const modalEl = document.getElementById('modal-model-other');
        if (!modalEl) return;

        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('form-model-other');
        const select = form.kd_model;
        const modelSelect = afselSearchSelect(select);
        const takenCodes = new Set(@json($takenCodes));
        const updateUrl = '{{ url('aftersales/atpm/model-other') }}';

        // Model yang sudah ada di daftar disembunyikan, kecuali model milik data yang sedang diubah.
        function prepareOptions(currentCode) {
            Array.from(select.options).forEach(o => {
                if (o.value !== '') o.disabled = takenCodes.has(o.value) && o.value !== currentCode;
            });
            return Array.from(select.options).some(o => o.value !== '' && !o.disabled);
        }

        function openModal({ id = '', title, row = null }) {
            form.reset();
            $('#model-other-id').val(id);
            $('#modal-model-other-title').text(title);

            const code = row ? String(row.kd_model) : null;
            if (code && !Array.from(select.options).some(o => o.value === code)) {
                // Model lama mungkin sudah tidak ditandai After Sales; tetap tampilkan agar bisa dipertahankan.
                select.add(new Option(`${row.nm_model} (${code})`, code));
                Object.assign(select.options[select.options.length - 1].dataset, { label: row.nm_model, code });
            }
            const hasOptions = prepareOptions(code);

            setTimeout(() => {
                select.value = code || '';
                form.is_active.checked = row ? Number(row.is_active) === 1 : true;
                modelSelect.sync();
            });

            $('#wrap-status').toggle(!!row);
            $('#wrap-model').toggle(hasOptions);
            $('#note-all-taken').toggle(!hasOptions);
            $('#btn-save').toggle(hasOptions);
            modal.show();
        }

        modalEl.addEventListener('shown.bs.modal', () => { if ($('#wrap-model').is(':visible')) modelSelect.focus(); });

        $('#btn-add').on('click', () => openModal({ title: 'Tambah Model Other' }));

        $('#model-other-table').on('click', '.btn-edit', function () {
            const row = table.row($(this).closest('tr')).data();
            openModal({ id: row.id, title: 'Ubah Model Other', row });
        });

        $('#btn-save').on('click', function () {
            if (!modelSelect.validate('Pilih model terlebih dahulu.')) {
                return;
            }
            const id = $('#model-other-id').val();
            const label = select.selectedOptions[0].text;
            const data = afselFormData(form);

            if (id) {
                afselRequest({
                    method: 'put',
                    url: `${updateUrl}/${id}`,
                    data,
                    confirm: `Simpan perubahan untuk ${label}?`,
                });
            } else {
                delete data.is_active; // data baru selalu aktif
                afselRequest({
                    method: 'post',
                    url: '{{ route('aftersales.atpm.model_other_store') }}',
                    data,
                    confirm: `Tambahkan ${label} ke daftar Model Other?`,
                });
            }
        });
    });
</script>

<style>
    .afsel-ui tr.afsel-muted td { color: var(--mz-off); }
</style>
@endsection
