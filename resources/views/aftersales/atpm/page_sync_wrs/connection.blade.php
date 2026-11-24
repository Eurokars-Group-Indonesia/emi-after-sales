@extends('aftersales.atpm.layouts.app')

@section('title', 'Koneksi Database Sync')

@section('navtop')
    {{ view('aftersales.atpm.layouts.navtop') }}
@endsection

@section('sidebar')
    {{ view('aftersales.atpm.layouts.sidebar') }}
@endsection

@section('content')
<div class="content afsel-ui">
    <div class="page-header flex-wrap gap-2">
        <div class="page-title">Koneksi Database Sync</div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('aftersales.atpm.home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript:void(0)">Utility</a></li>
                <li class="breadcrumb-item active" aria-current="page">Koneksi Database Sync</li>
            </ol>
        </nav>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">
            <span class="afsel-section-title">Database sumber sync</span>
            @if ($canManage)
                <button class="btn-fi btn-fi-primary btn-fi-sm ms-auto" id="btn-add">
                    <i class="bi bi-plus-lg"></i> Tambah Koneksi
                </button>
            @endif
        </div>
        <div class="card-body">
            <table id="table-conn" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Hostname</th>
                        <th>Database</th>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Remarks</th>
                        <th>Status</th>
                        <th>Diubah</th>
                        @if ($canManage)
                            <th style="width:70px" class="text-center">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>
                                <strong>{{ $row->name }}</strong>
                                <div class="afsel-muted small">{{ $drivers[$row->driver] ?? $row->driver }}</div>
                                @foreach ($row->used_by as $job)
                                    <span class="afsel-tag">{{ $job }}</span>
                                @endforeach
                            </td>
                            <td>{{ $row->hostname }}<span class="afsel-muted">:{{ $row->port }}</span></td>
                            <td>{{ $row->database }}</td>
                            <td>{{ $row->username }}</td>
                            <td>{!! $row->has_password ? '<span class="afsel-muted">••••••••</span>' : '<span class="afsel-muted">(kosong)</span>' !!}</td>
                            <td class="small">{{ $row->remarks ?: '-' }}</td>
                            <td><span class="afsel-status {{ $row->is_active ? 'is-on' : 'is-off' }}">{{ $row->is_active ? 'Aktif' : 'Non-aktif' }}</span></td>
                            <td class="small">
                                {{ $row->date_updated ?? $row->date_created }}
                                <div class="afsel-muted">{{ $row->updated_by ?? $row->created_by }}</div>
                            </td>
                            @if ($canManage)
                                <td class="text-center">
                                    <button class="btn-fi btn-fi-secondary btn-fi-sm btn-edit" title="Ubah koneksi" aria-label="Ubah koneksi {{ $row->name }}"
                                            data-row="{{ json_encode(collect($row)->except(['used_by', 'has_password'])) }}">
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
                Password disimpan terenkripsi. Koneksi tidak bisa dihapus — non-aktifkan untuk menghentikan sementara job yang memakainya.
                Disarankan memakai user database <strong>read-only</strong> karena sync hanya membaca dari sumber.
            </div>
        </div>
    </div>
</div>

@if ($canManage)
<div class="modal fade afsel-ui" id="modal-conn" tabindex="-1" aria-labelledby="modal-conn-title">
    <div class="modal-dialog">
        <form class="modal-content" id="form-conn" onsubmit="return false">
            <div class="modal-header">
                <div>
                    <div class="afsel-eyebrow">Utility</div>
                    <h6 class="modal-title" id="modal-conn-title">Tambah Koneksi</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="conn-id" name="id">
                <div class="row g-2">
                    <div class="col-7">
                        <label class="form-label" for="conn-name">Name</label>
                        <input type="text" class="form-control form-control-sm text-uppercase" id="conn-name" name="name" maxlength="100" placeholder="WRS_SALES" required>
                    </div>
                    <div class="col-5">
                        <label class="form-label" for="conn-driver">Driver</label>
                        <select class="form-select form-select-sm" id="conn-driver" name="driver">
                            @foreach ($drivers as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-8">
                        <label class="form-label" for="conn-host">Hostname</label>
                        <input type="text" class="form-control form-control-sm" id="conn-host" name="hostname" maxlength="255" placeholder="192.168.1.238" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label" for="conn-port">Port</label>
                        <input type="number" class="form-control form-control-sm" id="conn-port" name="port" min="1" max="65535" value="5432" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="conn-db">Database</label>
                        <input type="text" class="form-control form-control-sm" id="conn-db" name="database" maxlength="255" placeholder="wrs" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="conn-user">Username</label>
                        <input type="text" class="form-control form-control-sm" id="conn-user" name="username" maxlength="255" autocomplete="off" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="conn-pass">Password</label>
                        <input type="password" class="form-control form-control-sm" id="conn-pass" name="password" maxlength="255" autocomplete="new-password">
                        <div class="form-text d-none" id="conn-pass-hint">Kosongkan jika tidak diganti.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="conn-remarks">Remarks</label>
                        <textarea class="form-control form-control-sm" id="conn-remarks" name="remarks" rows="2" maxlength="2000"></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="conn-active" name="is_active" checked>
                            <label class="form-check-label" for="conn-active">Aktif</label>
                        </div>
                    </div>
                </div>
                <div class="small mt-2 d-none" id="conn-test-result" role="status"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn-fi btn-fi-secondary btn-fi-sm me-auto" id="btn-test"><i class="bi bi-plug"></i> Test Koneksi</button>
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
        $('#table-conn').DataTable({
            order: [[0, 'asc']],
            paging: false,
            info: false,
            columnDefs: [ @if ($canManage) { targets: -1, orderable: false, searchable: false }, @endif ],
            language: { search: 'Cari:', zeroRecords: 'Data tidak ditemukan', emptyTable: 'Belum ada koneksi' },
        });

        const modalEl = document.getElementById('modal-conn');
        if (!modalEl) return;

        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('form-conn');
        const baseUrl = '{{ url('aftersales/atpm/utility/sync-connection') }}';
        const $result = $('#conn-test-result');

        // form.elements dipakai karena form.id / form.name bentrok dengan atribut milik <form>
        const field = n => form.elements.namedItem(n);

        // Ganti driver → port ikut default driver, kecuali port sudah diubah manual
        const defaultPorts = @json(\App\Services\SyncWrs\SyncConnectionManager::DEFAULT_PORTS);
        $(field('driver')).on('change', function () {
            const port = field('port');
            if (!port.value || Object.values(defaultPorts).includes(String(port.value))) {
                port.value = defaultPorts[this.value] || port.value;
            }
        });

        function openForm(row) {
            form.reset();
            $result.addClass('d-none').text('');
            field('id').value = row ? row.id : '';
            field('name').value = row ? row.name : '';
            field('name').disabled = !!row;
            field('driver').value = row ? row.driver : 'pgsql';
            field('hostname').value = row ? row.hostname : '';
            field('port').value = row ? row.port : '5432';
            field('database').value = row ? row.database : '';
            field('username').value = row ? row.username : '';
            field('remarks').value = row ? (row.remarks || '') : '';
            field('is_active').checked = row ? !!Number(row.is_active) : true;
            $('#conn-pass-hint').toggleClass('d-none', !row);
            $('#modal-conn-title').text(row ? `Ubah Koneksi ${row.name}` : 'Tambah Koneksi');
            modal.show();
        }

        $('#btn-add').on('click', () => openForm(null));
        $('#table-conn').on('click', '.btn-edit', function () { openForm($(this).data('row')); });

        $('#btn-test').on('click', function () {
            const btn = this;
            btn.disabled = true;
            $result.removeClass('d-none text-success text-danger').addClass('afsel-muted').text('Menguji koneksi ...');
            axios.post(`${baseUrl}/test`, afselFormData(form), {
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            }).then(res => {
                $result.removeClass('afsel-muted').addClass(res.data.status ? 'text-success' : 'text-danger').text(res.data.message);
            }).catch(err => {
                $result.removeClass('afsel-muted').addClass('text-danger').text(err.response?.data?.message || 'Test koneksi gagal.');
            }).finally(() => { btn.disabled = false; });
        });

        $('#btn-save').on('click', function () {
            if (!form.reportValidity()) return;
            const id = field('id').value;
            afselRequest({
                method: id ? 'put' : 'post',
                url: id ? `${baseUrl}/${id}` : baseUrl,
                data: afselFormData(form),
                confirm: id ? 'Simpan perubahan koneksi? Job sync berikutnya langsung memakai setting baru.' : 'Tambah koneksi?',
            });
        });
    });
</script>
@endsection
