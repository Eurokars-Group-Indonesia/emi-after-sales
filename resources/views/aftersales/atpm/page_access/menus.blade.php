@extends($acx['layout'])

@section('title', 'Menu ' . $typeLabel)

@section('navtop')
    {{ view($acx['navtop']) }}
@endsection

@section('sidebar')
    {{ view($acx['sidebar']) }}
@endsection

@section('content')
<div class="content afsel-ui">
    @include('aftersales.atpm.page_access._header', [
        'title' => 'Menu',
        'tabRoute' => $acx['route'] . 'menus',
        'crumbs' => [['title' => 'Menu ' . $typeLabel]],
    ])

    <div class="card">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">
            <span class="access-section-title">Menu Sidebar {{ $typeLabel }}</span>
            <button class="btn-fi btn-fi-primary btn-fi-sm ms-auto" id="btn-add-menu">
                <i class="bi bi-plus-lg"></i> Tambah Menu
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover access-table mb-0">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Kode</th>
                            <th>Route / URL</th>
                            <th>Permission</th>
                            <th class="text-center">Urutan</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" style="width:120px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($menus as $menu)
                            <tr>
                                <td>
                                    <span class="menu-indent" style="width: {{ $menu->depth * 20 }}px"></span>
                                    @if ($menu->depth)<i class="bi bi-arrow-return-right text-muted"></i>@endif
                                    @if ($menu->icon)<i class="{{ $menu->icon }}"></i>@endif
                                    {{ $menu->label }}
                                </td>
                                <td><code>{{ $menu->name }}</code></td>
                                <td class="small">
                                    @if ($menu->route_name)
                                        {{ $menu->route_name }}
                                        @if (isset($routes[$menu->route_name]))
                                            <div class="text-muted">{{ $routes[$menu->route_name] }}</div>
                                        @else
                                            <div class="text-danger"><i class="bi bi-exclamation-triangle"></i> route tidak ditemukan</div>
                                        @endif
                                    @else
                                        {{ $menu->url ?: '-' }}
                                    @endif
                                </td>
                                <td class="small">{{ $menu->permission_slug ?: '-' }}</td>
                                <td class="text-center">{{ $menu->sort_order }}</td>
                                <td class="text-center">
                                    {!! $menu->is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>' !!}
                                </td>
                                <td class="text-center text-nowrap">
                                    <button class="btn-fi btn-fi-secondary btn-fi-sm btn-add-child" data-id="{{ $menu->id }}" title="Tambah sub menu"><i class="bi bi-node-plus"></i></button>
                                    <button class="btn-fi btn-fi-secondary btn-fi-sm btn-edit-menu" data-menu="{{ json_encode($menu) }}"><i class="bi bi-pencil"></i></button>
                                    <button class="btn-fi btn-fi-danger btn-fi-sm btn-delete-menu" data-id="{{ $menu->id }}" data-label="{{ $menu->label }}"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada menu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="form-text mt-2">
                <i class="bi bi-info-circle"></i>
                Menu baru belum tampil ke siapa pun (kecuali Super Admin) sampai dicentang di halaman <em>Role &rsaquo; Akses</em>
                atau diberikan lewat override di halaman <em>User Access</em>.
            </div>
        </div>
    </div>
</div>

{{-- Modal Menu --}}
<div class="modal fade afsel-ui" id="modal-menu" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="form-menu" onsubmit="return false">
            <div class="modal-header">
                <div>
                    <div class="afsel-eyebrow">Access Control &middot; Menu {{ $typeLabel }}</div>
                    <h6 class="modal-title" id="modal-menu-title">Tambah Menu</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="menu-id">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Label</label>
                        <input type="text" class="form-control form-control-sm" name="label" maxlength="255" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kode</label>
                        <input type="text" class="form-control form-control-sm" name="name" maxlength="100" placeholder="contoh: reports.retention">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Parent</label>
                        <select class="form-select form-select-sm" name="parent_id">
                            <option value="">- Menu utama -</option>
                            @foreach ($menus as $menu)
                                <option value="{{ $menu->id }}">{{ str_repeat('— ', $menu->depth) . $menu->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Urutan</label>
                        <input type="number" class="form-control form-control-sm" name="sort_order" min="0" max="9999" value="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Icon</label>
                        <input type="text" class="form-control form-control-sm" name="icon" maxlength="100" placeholder="bi bi-house-fill">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Route (opsional)</label>
                        <input type="text" class="form-control form-control-sm font-monospace" name="route_name" id="menu-route"
                               list="menu-route-options" maxlength="255" autocomplete="off" spellcheck="false"
                               placeholder="ketik nama route atau URL, contoh: customer">
                        <datalist id="menu-route-options">
                            @foreach ($routes as $routeName => $routeUrl)
                                <option value="{{ $routeName }}">{{ $routeUrl }}</option>
                            @endforeach
                        </datalist>
                        <div class="form-text" id="menu-route-hint">Kosongkan untuk menu induk (grup).</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">URL (opsional, jika tanpa route)</label>
                        <input type="text" class="form-control form-control-sm" name="url" maxlength="500" placeholder="/path atau https://...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Permission (opsional)</label>
                        <select class="form-select form-select-sm" name="permission_slug">
                            <option value="">- Tanpa syarat permission -</option>
                            @foreach ($permissions as $perm)
                                <option value="{{ $perm->slug }}">{{ $perm->name }} ({{ $perm->slug }})</option>
                            @endforeach
                        </select>
                        <div class="form-text">Menu hanya tampil jika role aktif user punya permission ini.</div>
                    </div>
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="menu-is-active" checked>
                            <label class="form-check-label" for="menu-is-active">Aktif</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn-fi btn-fi-secondary btn-fi-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn-fi btn-fi-primary btn-fi-sm" id="btn-save-menu"><i class="bi bi-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

@include('aftersales.atpm.page_access._scripts')
<script>
    $(function () {
        const modal = new bootstrap.Modal(document.getElementById('modal-menu'));
        const form = document.getElementById('form-menu');
        const baseUrl = '{{ route($acx['route'] . 'menus') }}';
        const type = @json($type);
        const routes = @json((object) $routes);

        // Preview URL dari route yang diketik; URL manual dinonaktifkan selama route terisi.
        const $routeHint = $('#menu-route-hint');
        const refreshRoute = () => {
            const name = form.route_name.value.trim();
            form.url.disabled = name !== '';
            $routeHint.removeClass('text-danger text-success');
            if (!name) {
                $routeHint.text('Kosongkan untuk menu induk (grup).');
            } else if (routes[name]) {
                $routeHint.addClass('text-success').html('<i class="bi bi-check-circle"></i> ').append(document.createTextNode(routes[name]));
            } else {
                $routeHint.addClass('text-danger').text('Route tidak ditemukan. Pilih dari saran yang muncul saat mengetik.');
            }
        };
        $(form.route_name).on('input change', refreshRoute);

        const openCreate = (parentId = '') => {
            form.reset();
            refreshRoute();
            $('#menu-id').val('');
            form.parent_id.value = parentId;
            $(form.parent_id).find('option').prop('disabled', false);
            $('#modal-menu-title').text(parentId ? 'Tambah Sub Menu' : 'Tambah Menu');
            modal.show();
        };

        $('#btn-add-menu').on('click', () => openCreate());
        $('.btn-add-child').on('click', function () { openCreate(String($(this).data('id'))); });

        $('.btn-edit-menu').on('click', function () {
            const m = $(this).data('menu');
            form.reset();
            $('#menu-id').val(m.id);
            ['label', 'name', 'icon', 'url', 'sort_order'].forEach(k => form[k].value = m[k] ?? '');
            form.parent_id.value = m.parent_id ?? '';
            form.route_name.value = m.route_name ?? '';
            refreshRoute();
            form.permission_slug.value = m.permission_slug ?? '';
            form.is_active.checked = !!Number(m.is_active);
            // Menu tidak bisa menjadi parent dirinya sendiri
            $(form.parent_id).find('option').prop('disabled', false).filter(`[value="${m.id}"]`).prop('disabled', true);
            $('#modal-menu-title').text('Ubah Menu');
            modal.show();
        });

        $('#btn-save-menu').on('click', function () {
            const id = $('#menu-id').val();
            afselRequest({
                method: id ? 'put' : 'post',
                url: id ? `${baseUrl}/${id}` : baseUrl,
                data: Object.assign(afselFormData(form), { type }),
                confirm: 'Simpan menu?',
            });
        });

        $('.btn-delete-menu').on('click', function () {
            afselRequest({
                method: 'delete',
                url: `${baseUrl}/${$(this).data('id')}`,
                data: { type },
                confirm: `Hapus menu "${$(this).data('label')}"? Menu juga dilepas dari semua role & override user.`,
                confirmIcon: 'warning',
            });
        });
    });
</script>
@endsection
