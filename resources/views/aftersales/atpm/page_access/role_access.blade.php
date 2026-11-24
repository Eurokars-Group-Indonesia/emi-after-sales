@extends($acx['layout'])

@section('title', 'Akses Role ' . $role->name)

@section('navtop')
    {{ view($acx['navtop']) }}
@endsection

@section('sidebar')
    {{ view($acx['sidebar']) }}
@endsection

@section('content')
<div class="content afsel-ui">
    @include('aftersales.atpm.page_access._header', [
        'title' => 'Akses Role: ' . $role->name,
        'tabRoute' => null,
        'crumbs' => [
            ['title' => 'Role ' . $typeLabel, 'url' => route($acx['route'] . 'roles', ['type' => $type])],
            ['title' => $role->name],
        ],
    ])

    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
        <a href="{{ route($acx['route'] . 'roles', ['type' => $type]) }}" class="btn-fi btn-fi-secondary btn-fi-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <span class="badge bg-primary">{{ $typeLabel }}</span>
        <code>{{ $role->slug }}</code>
        <span class="text-muted small">Level {{ $role->level }}</span>
        @if ($canManage && !$role->is_system)
            <button class="btn-fi btn-fi-primary btn-fi-sm ms-auto" id="btn-save-access">
                <i class="bi bi-save"></i> Simpan Akses
            </button>
        @endif
    </div>

    @if ($role->is_system)
        <div class="alert alert-info small">
            <i class="bi bi-info-circle"></i>
            Role ini adalah <strong>Super Admin</strong>: otomatis memiliki semua permission dan semua menu. Pengaturan di bawah tidak berpengaruh.
        </div>
    @elseif (!$canManage)
        <div class="alert alert-warning small">
            <i class="bi bi-lock"></i> Anda hanya dapat melihat akses role ini (privilege role lebih tinggi dari role Anda).
        </div>
    @endif

    @php $readonly = !$canManage || $role->is_system; @endphp

    <div class="row g-3">
        {{-- PERMISSION --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center">
                    <span class="access-section-title">Permission</span>
                    <span class="ms-auto small text-muted"><span id="perm-count">0</span> dipilih</span>
                </div>
                <div class="card-body">
                    @forelse ($permissions as $group => $items)
                        <div class="mb-3 perm-group">
                            <div class="form-check border-bottom pb-1 mb-1">
                                <input class="form-check-input perm-group-toggle" type="checkbox" id="pg-{{ $loop->index }}" {{ $readonly ? 'disabled' : '' }}>
                                <label class="form-check-label fw-semibold small" for="pg-{{ $loop->index }}">{{ $group }}</label>
                            </div>
                            @foreach ($items as $perm)
                                <div class="form-check ms-3">
                                    <input class="form-check-input perm-check" type="checkbox" value="{{ $perm->id }}" id="perm-{{ $perm->id }}"
                                        {{ in_array($perm->id, $rolePermissionIds) || $role->is_system ? 'checked' : '' }} {{ $readonly ? 'disabled' : '' }}>
                                    <label class="form-check-label small" for="perm-{{ $perm->id }}">
                                        {{ $perm->name }} <code>{{ $perm->slug }}</code>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="text-muted small">Belum ada permission.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- MENU --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center">
                    <span class="access-section-title">Menu Sidebar</span>
                    <span class="ms-auto small text-muted"><span id="menu-count">0</span> dipilih</span>
                </div>
                <div class="card-body">
                    @forelse ($menus as $menu)
                        <div class="form-check" style="margin-left: {{ $menu->depth * 22 }}px">
                            <input class="form-check-input menu-check" type="checkbox" value="{{ $menu->id }}" id="menu-{{ $menu->id }}"
                                data-parent="{{ $menu->parent_id }}"
                                {{ in_array($menu->id, $roleMenuIds) || $role->is_system ? 'checked' : '' }} {{ $readonly ? 'disabled' : '' }}>
                            <label class="form-check-label small" for="menu-{{ $menu->id }}">
                                @if ($menu->icon)<i class="{{ $menu->icon }}"></i>@endif
                                {{ $menu->label }}
                                @if (!$menu->is_active)<span class="badge bg-secondary">Nonaktif</span>@endif
                                @if ($menu->permission_slug)
                                    <span class="text-muted" title="Menu hanya tampil jika role punya permission ini"><i class="bi bi-key"></i> {{ $menu->permission_slug }}</span>
                                @endif
                            </label>
                        </div>
                    @empty
                        <div class="text-muted small">Belum ada menu.</div>
                    @endforelse
                    <div class="form-text mt-2">
                        Mencentang sub menu otomatis mencentang parent-nya. Menu bertanda <i class="bi bi-key"></i>
                        hanya tampil jika permission tersebut juga dicentang.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('aftersales.atpm.page_access._scripts')
<script>
    $(function () {
        const updateCounts = () => {
            $('#perm-count').text($('.perm-check:checked').length);
            $('#menu-count').text($('.menu-check:checked').length);
            $('.perm-group').each(function () {
                const all = $(this).find('.perm-check'), on = all.filter(':checked');
                const toggle = $(this).find('.perm-group-toggle')[0];
                toggle.checked = all.length > 0 && on.length === all.length;
                toggle.indeterminate = on.length > 0 && on.length < all.length;
            });
        };

        $('.perm-group-toggle').on('change', function () {
            $(this).closest('.perm-group').find('.perm-check').prop('checked', this.checked);
            updateCounts();
        });
        $('.perm-check').on('change', updateCounts);

        // Child dicentang → parent ikut; parent dilepas → semua child ikut dilepas
        $('.menu-check').on('change', function () {
            if (this.checked) {
                let parent = $(this).data('parent');
                while (parent) {
                    const p = $(`#menu-${parent}`).prop('checked', true);
                    parent = p.data('parent');
                }
            } else {
                const uncheckChildren = (id) => $(`.menu-check[data-parent="${id}"]`).each(function () {
                    this.checked = false;
                    uncheckChildren(this.value);
                });
                uncheckChildren(this.value);
            }
            updateCounts();
        });

        updateCounts();

        $('#btn-save-access').on('click', function () {
            afselRequest({
                url: '{{ route($acx['route'] . 'roles.access.save', ['id' => $role->id]) }}',
                data: {
                    type: @json($type),
                    permission_ids: $('.perm-check:checked').map((i, el) => Number(el.value)).get(),
                    menu_ids: $('.menu-check:checked').map((i, el) => Number(el.value)).get(),
                },
                confirm: 'Simpan permission & menu untuk role ' + @json($role->name) + '?',
            });
        });
    });
</script>
@endsection
