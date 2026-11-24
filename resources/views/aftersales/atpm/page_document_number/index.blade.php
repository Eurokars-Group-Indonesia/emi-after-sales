@extends('aftersales.atpm.layouts.app')

@section('title', 'Document Numbering')

@section('navtop')
    {{ view('aftersales.atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('aftersales.atpm.layouts.sidebar') }}
@endsection

@section('content')
<div class="content afsel-ui">
    <div class="page-header flex-wrap gap-2">
        <div class="page-title">Document Numbering</div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('aftersales.atpm.home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript:void(0)">Administration</a></li>
                <li class="breadcrumb-item active" aria-current="page">Document Numbering</li>
            </ol>
        </nav>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">
            <span class="afsel-section-title">&nbsp;</span>
            @if ($canManage)
                <button class="btn-fi btn-fi-primary btn-fi-sm ms-auto" id="btn-add">
                    <i class="bi bi-plus-lg"></i> Tambah Table
                </button>
            @endif
        </div>
        <div class="card-body">
            <table id="table-docnum" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:60px" class="text-center">ID</th>
                        <th>Table Name</th>
                        <th class="text-end" style="width:130px">Last Number</th>
                        <th class="text-center" style="width:130px">Last Date</th>
                        <th style="width:260px">Nomor Berikutnya</th>
                        <th style="width:190px">Diubah</th>
                        @if ($canManage)
                            <th style="width:70px" class="text-center">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td class="text-center">{{ $row->DocNumId }}</td>
                            <td><strong>{{ $row->table_name }}</strong></td>
                            <td class="text-end">{{ number_format($row->last_number, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if ($row->last_date)
                                    {{ \Carbon\Carbon::parse($row->last_date)->format('m/d/Y') }}
                                    @unless ($row->is_today)
                                        <div><span class="afsel-tag">reset ke 1 saat dipakai</span></div>
                                    @endunless
                                @else
                                    <span class="afsel-muted">-</span>
                                @endif
                            </td>
                            <td><code>{{ $row->next_number }}</code></td>
                            <td class="small">
                                {{ $row->date_updated ?? $row->date_created }}
                                <div class="afsel-muted">{{ $row->updated_by ?? $row->created_by }}</div>
                            </td>
                            @if ($canManage)
                                <td class="text-center">
                                    <button class="btn-fi btn-fi-secondary btn-fi-sm btn-edit" title="Ubah nomor urut"
                                            aria-label="Ubah nomor urut {{ $row->table_name }}"
                                            data-row="{{ json_encode(['id' => $row->DocNumId, 'table_name' => $row->table_name, 'last_number' => $row->is_today ? $row->last_number : 0]) }}">
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
                Format nomor: <code>yyyymmdd</code> + 20 digit nomor urut, contoh <code>{{ now()->format('Ymd') }}00000000000000000001</code>.
                Nomor urut kembali ke 1 setiap ganti hari, dan otomatis dilewati jika kode tersebut sudah ada di tabel tujuan.
            </div>
        </div>
    </div>
</div>

@if ($canManage)
<div class="modal fade afsel-ui" id="modal-docnum" tabindex="-1" aria-labelledby="modal-docnum-title">
    <div class="modal-dialog">
        <form class="modal-content" id="form-docnum" onsubmit="return false">
            <div class="modal-header">
                <div>
                    <div class="afsel-eyebrow">Administration</div>
                    <h6 class="modal-title" id="modal-docnum-title">Tambah Document Number</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="docnum-id">
                <div class="mb-2">
                    <label class="form-label" for="docnum-table">Table Name</label>
                    <input type="text" class="form-control form-control-sm" id="docnum-table" name="table_name" maxlength="100" placeholder="contoh: tblcustomer">
                </div>
                <div class="mb-2">
                    <label class="form-label" for="docnum-last">Last Number (hari ini)</label>
                    <input type="number" min="0" step="1" class="form-control form-control-sm" id="docnum-last" name="last_number" value="0" required>
                    <div class="form-text">Nomor berikutnya = last number + 1. Isi 0 agar mulai dari 1.</div>
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

@include('aftersales.atpm.page_access._scripts')
<script>
    $(function () {
        $('#table-docnum').DataTable({
            order: [[1, 'asc']],
            paging: false,
            info: false,
            columnDefs: [ @if ($canManage) { targets: -1, orderable: false, searchable: false }, @endif ],
            language: { search: 'Cari:', zeroRecords: 'Data tidak ditemukan', emptyTable: 'Belum ada data' },
        });

        const modalEl = document.getElementById('modal-docnum');
        if (!modalEl) return;

        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('form-docnum');
        const baseUrl = '{{ url('aftersales/atpm/administration/document-number') }}';

        $('#btn-add').on('click', function () {
            form.reset();
            $('#docnum-id').val('');
            form.table_name.disabled = false;
            $('#modal-docnum-title').text('Tambah Document Number');
            modal.show();
        });

        $('#table-docnum').on('click', '.btn-edit', function () {
            const row = $(this).data('row');
            form.reset();
            $('#docnum-id').val(row.id);
            form.table_name.value = row.table_name;
            form.table_name.disabled = true;
            form.last_number.value = row.last_number;
            $('#modal-docnum-title').text('Ubah Nomor Urut');
            modal.show();
        });

        $('#btn-save').on('click', function () {
            if (!form.reportValidity()) return;
            const id = $('#docnum-id').val();
            afselRequest({
                method: id ? 'put' : 'post',
                url: id ? `${baseUrl}/${id}` : baseUrl,
                data: afselFormData(form),
                confirm: id
                    ? 'Ubah nomor urut? Nomor yang lebih kecil dari kode yang sudah ada akan otomatis dilewati.'
                    : 'Tambah document number?',
                confirmIcon: id ? 'warning' : 'question',
            });
        });
    });
</script>
@endsection
