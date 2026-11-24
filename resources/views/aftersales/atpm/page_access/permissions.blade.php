@extends($acx['layout'])

@section('title', 'Permission ' . $typeLabel)

@section('navtop')
    {{ view($acx['navtop']) }}
@endsection

@section('sidebar')
    {{ view($acx['sidebar']) }}
@endsection

@section('content')
<div class="content afsel-ui">
    @include('aftersales.atpm.page_access._header', [
        'title' => 'Permission',
        'tabRoute' => $acx['route'] . 'permissions',
        'crumbs' => [['title' => 'Permission ' . $typeLabel]],
    ])

    @if (count($missingSlugs))
        <div class="alert alert-warning small">
            <i class="bi bi-exclamation-triangle"></i>
            Permission berikut dipakai di route aplikasi tetapi belum terdaftar, sehingga hanya Super Admin yang bisa membuka halamannya:
            @foreach ($missingSlugs as $slug)
                <button class="btn btn-link btn-sm p-0 ms-1 btn-add-missing" data-slug="{{ $slug }}"><code>{{ $slug }}</code></button>
            @endforeach
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">
            <span class="access-section-title">Daftar Permission {{ $typeLabel }}</span>
            <input type="text" id="perm-search" class="form-control form-control-sm ms-auto" style="max-width:220px" placeholder="Cari...">
            <button class="btn-fi btn-fi-primary btn-fi-sm" id="btn-add-perm">
                <i class="bi bi-plus-lg"></i> Tambah Permission
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover access-table mb-0" id="perm-table">
                    <thead>
                        <tr>
                            <th>Group</th>
                            <th>Nama</th>
                            <th>Slug</th>
                            <th>Deskripsi</th>
                            <th>Dipakai di</th>
                            <th class="text-center">Role</th>
                            <th class="text-center" style="width:90px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($permissions as $perm)
                            <tr>
                                <td>{{ $perm->group_name ?: '-' }}</td>
                                <td>{{ $perm->name }}</td>
                                <td><code>{{ $perm->slug }}</code></td>
                                <td>{{ $perm->description }}</td>
                                <td>
                                    @if (count($perm->used_in_routes))
                                        <span class="badge bg-info-subtle text-info-emphasis" title="{{ implode("\n", $perm->used_in_routes) }}">
                                            <i class="bi bi-signpost"></i> {{ count($perm->used_in_routes) }} route
                                        </span>
                                    @endif
                                    @if ($perm->used_in_menu)
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis"><i class="bi bi-list"></i> menu</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $perm->roles_count }}</td>
                                <td class="text-center text-nowrap">
                                    <button class="btn-fi btn-fi-secondary btn-fi-sm btn-edit-perm" data-perm="{{ json_encode($perm) }}"><i class="bi bi-pencil"></i></button>
                                    @if (!count($perm->used_in_routes) && !$perm->used_in_menu)
                                        <button class="btn-fi btn-fi-danger btn-fi-sm btn-delete-perm" data-id="{{ $perm->id }}" data-slug="{{ $perm->slug }}"><i class="bi bi-trash"></i></button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada permission.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="form-text mt-2">
                <i class="bi bi-info-circle"></i>
                Slug dipakai di kode (<code>{{ $acx['perm_middleware'] }}:slug</code> / <code>{{ '@' . $acx['perm_directive'] }}('slug')</code>), sehingga tidak bisa diubah.
                Permission yang masih dipakai route atau menu tidak bisa dihapus.
            </div>
        </div>
    </div>
</div>

{{-- Modal Permission --}}
<div class="modal fade afsel-ui" id="modal-perm" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" id="form-perm" onsubmit="return false">
            <div class="modal-header">
                <div>
                    <div class="afsel-eyebrow">Access Control &middot; Permission {{ $typeLabel }}</div>
                    <h6 class="modal-title" id="modal-perm-title">Tambah Permission</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="perm-id">
                <div class="mb-2">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-control form-control-sm" name="slug" maxlength="100" placeholder="contoh: report-retention.view">
                </div>
                <div class="mb-2">
                    <label class="form-label">Nama</label>
                    <input type="text" class="form-control form-control-sm" name="name" maxlength="100" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Group</label>
                    <input type="text" class="form-control form-control-sm" name="group_name" maxlength="50" list="perm-groups">
                    <datalist id="perm-groups">
                        @foreach ($groups as $group)
                            <option value="{{ $group }}">
                        @endforeach
                    </datalist>
                </div>
                <div class="mb-2">
                    <label class="form-label">Deskripsi</label>
                    <textarea class="form-control form-control-sm" name="description" rows="2" maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn-fi btn-fi-secondary btn-fi-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn-fi btn-fi-primary btn-fi-sm" id="btn-save-perm"><i class="bi bi-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

@include('aftersales.atpm.page_access._scripts')
<script>
    $(function () {
        const modal = new bootstrap.Modal(document.getElementById('modal-perm'));
        const form = document.getElementById('form-perm');
        const baseUrl = '{{ route($acx['route'] . 'permissions') }}';
        const type = @json($type);

        const openCreate = (slug = '') => {
            form.reset();
            $('#perm-id').val('');
            form.slug.disabled = false;
            form.slug.value = slug;
            $('#modal-perm-title').text('Tambah Permission');
            modal.show();
        };

        $('#btn-add-perm').on('click', () => openCreate());
        $('.btn-add-missing').on('click', function () { openCreate($(this).data('slug')); });

        $('.btn-edit-perm').on('click', function () {
            const p = $(this).data('perm');
            form.reset();
            $('#perm-id').val(p.id);
            form.slug.value = p.slug;
            form.slug.disabled = true;
            form.name.value = p.name;
            form.group_name.value = p.group_name || '';
            form.description.value = p.description || '';
            $('#modal-perm-title').text('Ubah Permission');
            modal.show();
        });

        $('#btn-save-perm').on('click', function () {
            const id = $('#perm-id').val();
            afselRequest({
                method: id ? 'put' : 'post',
                url: id ? `${baseUrl}/${id}` : baseUrl,
                data: Object.assign(afselFormData(form), { type }),
                confirm: 'Simpan permission?',
            });
        });

        $('.btn-delete-perm').on('click', function () {
            afselRequest({
                method: 'delete',
                url: `${baseUrl}/${$(this).data('id')}`,
                data: { type },
                confirm: `Hapus permission "${$(this).data('slug')}"? Permission ini akan dilepas dari semua role.`,
                confirmIcon: 'warning',
            });
        });

        $('#perm-search').on('input', function () {
            const q = this.value.toLowerCase();
            $('#perm-table tbody tr').each(function () {
                $(this).toggle($(this).text().toLowerCase().includes(q));
            });
        });
    });
</script>
@endsection
