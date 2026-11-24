@extends($acx['layout'])

@section('title', 'Akses ' . $userName)

@section('navtop')
    {{ view($acx['navtop']) }}
@endsection

@section('sidebar')
    {{ view($acx['sidebar']) }}
@endsection

@section('content')
<div class="content afsel-ui">
    @include('aftersales.atpm.page_access._header', [
        'title' => 'Akses User',
        'tabRoute' => null,
        'crumbs' => [
            ['title' => 'User ' . $typeLabel, 'url' => route($acx['route'] . 'users', ['type' => $type])],
            ['title' => $userName],
        ],
    ])

    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
        <a href="{{ route($acx['route'] . 'users', ['type' => $type]) }}" class="btn-fi btn-fi-secondary btn-fi-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <button class="btn-fi btn-fi-primary btn-fi-sm ms-auto" id="btn-save-user-access">
            <i class="bi bi-save"></i> Simpan Akses
        </button>
    </div>

    <div class="row g-3">
        {{-- INFO USER --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><span class="access-section-title">User {{ $typeLabel }}</span></div>
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-5">Nama</dt><dd class="col-7">{{ $userName }}</dd>
                        <dt class="col-5">Username</dt><dd class="col-7">{{ $user->username }}</dd>
                        <dt class="col-5">Email</dt><dd class="col-7">{{ $user->email ?: '-' }}</dd>
                        @if ($type === 'dealer')
                            <dt class="col-5">Kode Dealer</dt><dd class="col-7">{{ $user->fk_dealer ?: '-' }}</dd>
                        @endif
                        <dt class="col-5">Status</dt>
                        <dd class="col-7">{!! $user->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' !!}</dd>
                        @if ($showLastLogin ?? true)
                            <dt class="col-5">Login terakhir</dt>
                            <dd class="col-7">{{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->format('d M Y H:i') : '-' }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- ROLE --}}
            <div class="card">
                <div class="card-header"><span class="access-section-title">Role</span></div>
                <div class="card-body">
                    @if ($isSelf && !$isSuper)
                        <div class="alert alert-warning small py-2">
                            <i class="bi bi-lock"></i> Ini akun Anda sendiri. Role hanya bisa diubah oleh Super Admin.
                        </div>
                    @endif
                    @foreach ($roles as $role)
                        @php $lockRole = !$role->can_manage || ($isSelf && !$isSuper); @endphp
                        <div class="border rounded p-2 mb-2 {{ $role->assigned ? 'border-primary' : '' }}">
                            <div class="form-check mb-1">
                                <input class="form-check-input role-check" type="checkbox" value="{{ $role->id }}" id="role-{{ $role->id }}"
                                    {{ $role->assigned ? 'checked' : '' }} {{ $lockRole ? 'disabled' : '' }}>
                                <label class="form-check-label small" for="role-{{ $role->id }}">
                                    <strong>{{ $role->name }}</strong>
                                    @if ($role->is_system)<span class="badge bg-dark">Super Admin</span>@endif
                                    @if (!$role->is_active)<span class="badge bg-secondary">Role nonaktif</span>@endif
                                    @if ($role->expired)<span class="badge bg-danger">Kedaluwarsa</span>@endif
                                    @if (!$role->can_manage)<i class="bi bi-lock text-muted" title="Di luar wewenang Anda"></i>@endif
                                </label>
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Berlaku s/d</span>
                                <input type="datetime-local" class="form-control role-expires" data-role="{{ $role->id }}"
                                    value="{{ $role->expires_at ? \Carbon\Carbon::parse($role->expires_at)->format('Y-m-d\TH:i') : '' }}"
                                    {{ $lockRole ? 'disabled' : '' }}>
                            </div>
                        </div>
                    @endforeach
                    <div class="form-text">Kosongkan "Berlaku s/d" untuk role tanpa batas waktu. User dengan lebih dari satu role memilih role saat login.</div>
                </div>
            </div>
        </div>

        {{-- OVERRIDE MENU --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <span class="access-section-title">Override Menu</span>
                    <span class="ms-auto small text-muted">Override berlaku untuk role apa pun yang sedang aktif</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered access-table mb-0">
                            <thead>
                                <tr>
                                    <th>Menu</th>
                                    <th class="text-center">Dari Role</th>
                                    <th class="text-center" style="width:190px">Override</th>
                                    <th class="text-center">Hasil</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($menus as $menu)
                                    @php $fromRole = in_array($menu->id, $roleMenuIds); @endphp
                                    <tr class="menu-row" data-id="{{ $menu->id }}" data-parent="{{ $menu->parent_id }}" data-role="{{ $fromRole ? 1 : 0 }}">
                                        <td>
                                            <span class="menu-indent" style="width: {{ $menu->depth * 20 }}px"></span>
                                            @if ($menu->icon)<i class="{{ $menu->icon }}"></i>@endif
                                            {{ $menu->label }}
                                            @if (!$menu->is_active)<span class="badge bg-secondary">Nonaktif</span>@endif
                                            @if ($menu->permission_slug)
                                                <span class="text-muted small" title="Juga butuh permission ini pada role aktif"><i class="bi bi-key"></i> {{ $menu->permission_slug }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {!! $fromRole ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-dash-circle text-muted"></i>' !!}
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm menu-override" data-id="{{ $menu->id }}">
                                                <option value="default" {{ !isset($overrides[$menu->id]) ? 'selected' : '' }}>Ikuti role</option>
                                                <option value="grant" {{ ($overrides[$menu->id] ?? null) === 'grant' ? 'selected' : '' }}>Beri akses</option>
                                                <option value="revoke" {{ ($overrides[$menu->id] ?? null) === 'revoke' ? 'selected' : '' }}>Cabut akses</option>
                                            </select>
                                        </td>
                                        <td class="text-center menu-result"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="form-text mt-2">
                        "Dari Role" = gabungan menu semua role aktif user. "Hasil" memperhitungkan override dan aturan parent
                        (sub menu ikut tersembunyi jika parent-nya tidak punya akses).
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('aftersales.atpm.page_access._scripts')
<script>
    $(function () {
        const type = @json($type);

        const recalc = () => {
            const access = {};
            $('.menu-row').each(function () {
                const mode = $(this).find('.menu-override').val();
                access[$(this).data('id')] = mode === 'grant' ? true : mode === 'revoke' ? false : $(this).data('role') === 1;
            });
            $('.menu-row').each(function () {
                let ok = access[$(this).data('id')], parent = $(this).data('parent'), guard = 0;
                while (ok && parent && guard++ < 50) {
                    ok = !!access[parent];
                    parent = $(`.menu-row[data-id="${parent}"]`).data('parent');
                }
                $(this).find('.menu-result').html(ok
                    ? '<span class="badge bg-success">Tampil</span>'
                    : '<span class="badge bg-light text-muted border">Tidak tampil</span>');
                $(this).toggleClass('table-warning', $(this).find('.menu-override').val() !== 'default');
            });
        };

        $('.menu-override').on('change', recalc);
        recalc();

        $('#btn-save-user-access').on('click', function () {
            const expires = {};
            $('.role-expires').each(function () { expires[$(this).data('role')] = this.value || null; });

            const overrides = {};
            $('.menu-override').each(function () { overrides[$(this).data('id')] = this.value; });

            afselRequest({
                url: '{{ route($acx['route'] . 'users.update', ['kdUser' => $kdUser]) }}',
                data: {
                    type,
                    // Role terkunci tetap dikirim; server hanya mengubah role yang boleh dikelola
                    role_ids: $('.role-check:checked').map((i, el) => Number(el.value)).get(),
                    expires,
                    overrides,
                },
                confirm: 'Simpan role & override menu untuk ' + @json($userName) + '?',
            });
        });
    });
</script>
@endsection
