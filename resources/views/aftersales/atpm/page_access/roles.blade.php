@extends($acx['layout'])

@section('title', 'Role ' . $typeLabel)

@section('navtop')
    {{ view($acx['navtop']) }}
@endsection

@section('sidebar')
    {{ view($acx['sidebar']) }}
@endsection

@section('content')
<div class="content afsel-ui">
    @include('aftersales.atpm.page_access._header', [
        'title' => 'Role',
        'tabRoute' => $acx['route'] . 'roles',
        'crumbs' => [['title' => 'Role ' . $typeLabel]],
    ])

    <div class="card">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">
            <span class="access-section-title">Daftar Role {{ $typeLabel }}</span>
            <button class="btn-fi btn-fi-primary btn-fi-sm ms-auto" id="btn-add-role">
                <i class="bi bi-plus-lg"></i> Tambah Role
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover access-table mb-0">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Slug</th>
                            <th class="text-center">Level</th>
                            <th>Deskripsi</th>
                            <th class="text-center">User</th>
                            <th class="text-center">Permission</th>
                            <th class="text-center">Menu</th>
                            <th class="text-center" style="width:230px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td>
                                    <strong>{{ $role->name }}</strong>
                                    @if ($role->is_system)
                                        <span class="badge bg-dark ms-1" title="Bypass semua permission & menu">Super Admin</span>
                                    @endif
                                    @if (!$role->is_active)
                                        <span class="badge bg-secondary ms-1">Nonaktif</span>
                                    @endif
                                </td>
                                <td><code>{{ $role->slug }}</code></td>
                                <td class="text-center">{{ $role->level }}</td>
                                <td>{{ $role->description }}</td>
                                <td class="text-center">
                                    <a href="{{ route($acx['route'] . 'users', ['type' => $type, 'role_id' => $role->id]) }}">{{ $role->users_count }}</a>
                                </td>
                                <td class="text-center">{{ $role->is_system ? 'Semua' : $role->permissions_count }}</td>
                                <td class="text-center">{{ $role->is_system ? 'Semua' : $role->menus_count }}</td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ route($acx['route'] . 'roles.access', ['id' => $role->id, 'type' => $type]) }}" class="btn-fi btn-fi-primary btn-fi-sm">
                                        <i class="bi bi-ui-checks"></i> Akses
                                    </a>
                                    @if ($role->can_manage)
                                        <button class="btn-fi btn-fi-secondary btn-fi-sm btn-edit-role" data-role="{{ json_encode($role) }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn-fi btn-fi-danger btn-fi-sm btn-delete-role" data-id="{{ $role->id }}" data-name="{{ $role->name }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada role.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="form-text mt-2">
                <i class="bi bi-info-circle"></i>
                Level semakin kecil = privilege semakin tinggi. Role <strong>Super Admin</strong> melewati semua pengecekan permission & menu.
            </div>
        </div>
    </div>
</div>

{{-- Modal Role --}}
<div class="modal fade afsel-ui" id="modal-role" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" id="form-role" onsubmit="return false">
            <div class="modal-header">
                <div>
                    <div class="afsel-eyebrow">Access Control &middot; Role {{ $typeLabel }}</div>
                    <h6 class="modal-title" id="modal-role-title">Tambah Role</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="role-id">
                <div class="mb-2">
                    <label class="form-label">Nama Role</label>
                    <input type="text" class="form-control form-control-sm" name="name" maxlength="100" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-control form-control-sm" name="slug" maxlength="100" placeholder="contoh: service-advisor">
                    <div class="form-text">Huruf kecil, angka, titik, strip. Tidak bisa diubah setelah dibuat.</div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Deskripsi</label>
                    <textarea class="form-control form-control-sm" name="description" rows="2" maxlength="500"></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label">Level</label>
                    <input type="number" class="form-control form-control-sm" name="level" min="1" max="999" value="50" required>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="role-is-active" checked>
                    <label class="form-check-label" for="role-is-active">Aktif</label>
                </div>
                @if ($isSuper)
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_system" id="role-is-system">
                        <label class="form-check-label" for="role-is-system">Super Admin (bypass semua akses)</label>
                    </div>
                @endif
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn-fi btn-fi-secondary btn-fi-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn-fi btn-fi-primary btn-fi-sm" id="btn-save-role"><i class="bi bi-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

@include('aftersales.atpm.page_access._scripts')
<script>
    $(function () {
        const modal = new bootstrap.Modal(document.getElementById('modal-role'));
        const form = document.getElementById('form-role');
        const baseUrl = '{{ route($acx['route'] . 'roles') }}';
        const type = @json($type);

        $('#btn-add-role').on('click', function () {
            form.reset();
            $('#role-id').val('');
            form.slug.disabled = false;
            $('#modal-role-title').text('Tambah Role');
            modal.show();
        });

        $('.btn-edit-role').on('click', function () {
            const role = $(this).data('role');
            form.reset();
            $('#role-id').val(role.id);
            form.name.value = role.name;
            form.slug.value = role.slug;
            form.slug.disabled = true;
            form.description.value = role.description || '';
            form.level.value = role.level;
            form.is_active.checked = !!Number(role.is_active);
            if (form.is_system) form.is_system.checked = !!Number(role.is_system);
            $('#modal-role-title').text('Ubah Role');
            modal.show();
        });

        $('#btn-save-role').on('click', function () {
            const id = $('#role-id').val();
            const data = Object.assign(afselFormData(form), { type });
            afselRequest({ method: id ? 'put' : 'post', url: id ? `${baseUrl}/${id}` : baseUrl, data, confirm: 'Simpan role?' });
        });

        $('.btn-delete-role').on('click', function () {
            afselRequest({
                method: 'delete',
                url: `${baseUrl}/${$(this).data('id')}`,
                data: { type },
                confirm: `Hapus role "${$(this).data('name')}"? Pengaturan permission & menu role ini ikut terhapus.`,
                confirmIcon: 'warning',
            });
        });
    });
</script>
@endsection
