@extends($acx['layout'])

@section('title', 'User Access ' . $typeLabel)

@section('navtop')
    {{ view($acx['navtop']) }}
@endsection

@section('sidebar')
    {{ view($acx['sidebar']) }}
@endsection

@section('content')
<div class="content afsel-ui">
    @include('aftersales.atpm.page_access._header', [
        'title' => 'User Access',
        'tabRoute' => $acx['route'] . 'users',
        'crumbs' => [['title' => 'User ' . $typeLabel]],
    ])

    @if (!empty($userSourceNote))
        <div class="afsel-note"><i class="bi bi-info-circle"></i><span>{{ $userSourceNote }}</span></div>
    @endif

    <div class="afsel-stats">
        <div class="afsel-stat"><span class="afsel-stat-num">{{ number_format($stats->total) }}</span><span class="afsel-stat-label">Total user</span></div>
        <div class="afsel-stat"><span class="afsel-stat-num">{{ number_format($stats->active) }}</span><span class="afsel-stat-label">Active</span></div>
        <div class="afsel-stat"><span class="afsel-stat-num">{{ number_format($stats->inactive) }}</span><span class="afsel-stat-label">Inactive</span></div>
        <button type="button" class="afsel-stat" id="stat-no-role" aria-pressed="false" title="Klik untuk menampilkan user yang belum punya role">
            <span class="afsel-stat-num">{{ number_format($stats->no_role) }}</span>
            <span class="afsel-stat-label">Belum punya role</span>
        </button>
    </div>

    <div class="card">
        <div class="afsel-toolbar">
            <input type="search" id="search-input" class="form-control form-control-sm afsel-grow" placeholder="Cari username, nama, atau email" aria-label="Cari user">
            @if ($type === 'dealer')
                <select id="filter-dealer" class="form-select form-select-sm" aria-label="Filter dealer (hanya dealer aktif)">
                    <option value="">Semua dealer</option>
                    @foreach ($dealers as $d)
                        <option value="{{ $d->kd_dealer }}">{{ $d->nm_dealer }}</option>
                    @endforeach
                </select>
            @endif
            <select id="filter-status" class="form-select form-select-sm" aria-label="Filter status">
                <option value="">Semua status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <button type="button" id="btn-reset" class="btn-fi btn-fi-secondary btn-fi-sm">Reset</button>
            @if ($canManageUser)
                <button type="button" id="btn-add-user" class="btn-fi btn-fi-primary btn-fi-sm afsel-push">
                    <i class="bi bi-plus-lg"></i> Tambah User
                </button>
            @endif
        </div>
        <div class="card-body">
            <p class="small mb-2" id="info-no-role" hidden>
                Menampilkan user yang belum punya role.
                <a href="#" id="clear-no-role">Tampilkan semua</a>
            </p>
            <table id="user-table" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        @if ($type === 'dealer')
                            <th>Dealer</th>
                        @endif
                        <th>Role</th>
                        @if ($showLastLogin ?? true)
                            <th>Login terakhir</th>
                        @endif
                        <th>Status</th>
                        <th class="text-end" style="width:130px">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@if ($canManageUser)
<div class="modal fade afsel-ui" id="modal-user" tabindex="-1" aria-labelledby="modal-user-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="form-user" onsubmit="return false" autocomplete="off">
            <div class="modal-header">
                <span class="afsel-hero-avatar" id="modal-user-avatar" aria-hidden="true"><i class="bi bi-person-plus"></i></span>
                <div class="min-w-0">
                    <div class="afsel-eyebrow">User {{ $typeLabel }} {{ $acx['app'] }}</div>
                    <h6 class="modal-title" id="modal-user-title">Tambah User</h6>
                    <div class="afsel-hero-sub" id="modal-user-sub">Dibuat manual, di luar sync WRS</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="user-kd">
                <div class="afsel-note is-warn" id="note-sync" hidden>
                    User ini hasil sync WRS. Perubahan nama, username, email, dealer, dan status bisa tertimpa saat sync user berikutnya.
                </div>

                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label" for="user-name">Nama lengkap</label>
                        <input type="text" class="form-control form-control-sm" id="user-name" name="name" maxlength="255" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="user-username">Username</label>
                        <input type="text" class="form-control form-control-sm" id="user-username" name="username" maxlength="100" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="user-email">Email <span class="fw-normal text-muted">(opsional)</span></label>
                        <input type="email" class="form-control form-control-sm" id="user-email" name="email" maxlength="255">
                    </div>
                    @if ($type === 'dealer')
                        <div class="col-12">
                            <label class="form-label" for="user-dealer">Dealer</label>
                            <select class="form-select form-select-sm" id="user-dealer" name="fk_dealer" required>
                                <option value="">Pilih dealer</option>
                                @foreach ($dealers as $d)
                                    <option value="{{ $d->kd_dealer }}">{{ $d->nm_dealer }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-12">
                        <label class="form-label" for="user-password">Password</label>
                        <div class="input-group input-group-sm">
                            <input type="password" class="form-control" id="user-password" name="password" minlength="8" maxlength="100" autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary" id="btn-toggle-password" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                        </div>
                        <div class="form-text" id="password-help">Minimal 8 karakter.</div>
                    </div>
                    <div class="col-12" id="wrap-role">
                        <label class="form-label" for="user-role">Role</label>
                        <select class="form-select form-select-sm" id="user-role" name="role_id">
                            <option value="">Atur nanti</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">User tanpa role belum bisa login.</div>
                    </div>
                    <div class="col-12">
                        <label class="afsel-setting" for="user-is-active">
                            <span>
                                <span class="afsel-setting-title">Active</span>
                                <span class="afsel-setting-desc">User boleh login ke WRS Connect</span>
                            </span>
                            <span class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="user-is-active" name="is_active" checked>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-fi btn-fi-secondary btn-fi-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn-fi btn-fi-primary btn-fi-sm" id="btn-save-user">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endif

@include('aftersales.atpm.page_access._scripts')
<script>
    $(function () {
        const type = @json($type);
        const canManageUser = @json($canManageUser);
        const esc = s => $('<div>').text(s ?? '').html();
        const initials = name => (name || '?').replace(/[^A-Za-z\s]/g, ' ').trim().split(/\s+/)
            .filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('') || '?';
        let noRoleOnly = false;

        const columns = [
            {
                data: 'name', name: 'name',
                render: (v, t, r) => `
                    <div class="afsel-user">
                        <span class="afsel-avatar ${Number(r.is_active) ? '' : 'is-off'}" aria-hidden="true">${esc(initials(v))}</span>
                        <span>
                            <span class="fw-semibold">${esc(v)}</span>
                            ${r.is_local ? '<span class="afsel-tag is-soft-red ms-1" title="Dibuat manual, bukan dari sync WRS">MANUAL</span>' : ''}
                            <span class="afsel-sub">${esc(r.username)}</span>
                        </span>
                    </div>`,
            },
            { data: 'email', name: 'u.email', render: v => v ? esc(v) : '<span class="afsel-muted">-</span>' },
        ];
        if (type === 'dealer') {
            columns.push({
                data: 'nm_dealer', name: 'd.nm_dealer', defaultContent: '',
                render: (v, t, r) => v ? esc(v) : `<span class="afsel-muted">${esc(r.fk_dealer || '-')}</span>`,
            });
        }
        columns.push(
            {
                data: 'role_names', orderable: false, searchable: false,
                render: v => v
                    ? v.split('||').map((n, i) => `<span class="afsel-tag ${i === 0 ? 'is-dark' : ''}">${esc(n)}</span>`).join('')
                    : '<span class="afsel-tag is-soft-red">Belum ada role</span>',
            },
        );
        if (@json($showLastLogin ?? true)) {
            columns.push({
                data: 'last_login_at', name: 'u.last_login_at', searchable: false,
                render: v => v ? esc(v) : '<span class="afsel-muted">Belum pernah</span>',
            });
        }
        columns.push(
            {
                data: 'is_active', name: 'u.is_active', searchable: false,
                render: v => Number(v) ? '<span class="afsel-status is-on">Active</span>' : '<span class="afsel-status is-off">Inactive</span>',
            },
            {
                data: null, orderable: false, searchable: false, className: 'text-end text-nowrap',
                render: (v, t, r) => (canManageUser
                        ? `<button type="button" class="btn-fi btn-fi-secondary btn-fi-sm btn-edit-user" title="Ubah data user" aria-label="Ubah data ${esc(r.name)}"><i class="bi bi-pencil"></i></button> `
                        : '')
                    + `<a href="${esc(r.access_url)}" class="btn-fi btn-fi-secondary btn-fi-sm" title="Atur role dan menu">Akses</a>`,
            },
        );

        const dt = $('#user-table').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            ajax: {
                url: '{{ route($acx['route'] . 'users.datatable') }}',
                data: d => {
                    d.type = type;
                    d.fk_dealer = $('#filter-dealer').val() || '';
                    d.status = $('#filter-status').val();
                    d.no_role = noRoleOnly ? '1' : '';
                },
            },
            order: [[0, 'asc']],
            dom: 'rt<"d-flex flex-wrap align-items-center gap-3 mt-3"l i<"ms-auto"p>>',
            language: {
                processing: 'Memuat...',
                lengthMenu: '_MENU_ per halaman',
                info: '_START_–_END_ dari _TOTAL_ user',
                infoEmpty: 'Tidak ada user',
                infoFiltered: '',
                zeroRecords: 'User tidak ditemukan',
                emptyTable: 'Belum ada user',
                paginate: { previous: '‹', next: '›' },
            },
            columns,
        });

        let typingTimer;
        const reload = () => dt.search($('#search-input').val()).draw();
        $('#search-input').on('input', () => { clearTimeout(typingTimer); typingTimer = setTimeout(reload, 400); })
            .on('keydown', e => { if (e.key === 'Enter') { clearTimeout(typingTimer); reload(); } });
        $('#filter-dealer, #filter-status').on('change', reload);

        const setNoRole = on => {
            noRoleOnly = on;
            $('#stat-no-role').attr('aria-pressed', on ? 'true' : 'false');
            $('#info-no-role').prop('hidden', !on);
        };
        $('#stat-no-role').on('click', () => { setNoRole(!noRoleOnly); reload(); });
        $('#clear-no-role').on('click', e => { e.preventDefault(); setNoRole(false); reload(); });

        $('#btn-reset').on('click', function () {
            $('#search-input').val('');
            $('#filter-dealer, #filter-status').val('');
            setNoRole(false);
            reload();
        });

        if (!canManageUser) return;

        const modal = new bootstrap.Modal(document.getElementById('modal-user'));
        const form = document.getElementById('form-user');
        const storeUrl = @json($canManageUser ? route($acx['route'] . 'users.store') : '');
        const profileUrl = kd => '{{ route($acx['route'] . 'users') }}/' + encodeURIComponent(kd) + '/profile';

        const openForm = row => {
            form.reset();
            const isEdit = !!row;
            $('#user-kd').val(isEdit ? row.kd_user : '');
            $('#modal-user-title').text(isEdit ? row.name : 'Tambah User');
            $('#modal-user-sub').text(isEdit ? row.username + ' · ' + row.kd_user : 'Dibuat manual, di luar sync WRS');
            $('#modal-user-avatar').html(isEdit ? esc(initials(row.name)) : '<i class="bi bi-person-plus"></i>');
            $('#note-sync').prop('hidden', !isEdit || row.is_local);
            $('#wrap-role').toggle(!isEdit);
            $('#password-help').text(isEdit ? 'Kosongkan jika tidak diubah. Minimal 8 karakter.' : 'Minimal 8 karakter.');
            form.password.required = !isEdit;

            if (isEdit) {
                form.name.value = row.name || '';
                form.username.value = row.username || '';
                form.email.value = row.email || '';
                if (form.fk_dealer) form.fk_dealer.value = row.fk_dealer || '';
                form.is_active.checked = !!Number(row.is_active);
            }
            modal.show();
        };

        document.getElementById('modal-user').addEventListener('shown.bs.modal', () => form.name.focus());
        $('#btn-add-user').on('click', () => openForm(null));
        $('#user-table').on('click', '.btn-edit-user', function () {
            openForm(dt.row($(this).closest('tr')).data());
        });

        $('#btn-toggle-password').on('click', function () {
            const show = form.password.type === 'password';
            form.password.type = show ? 'text' : 'password';
            $(this).attr('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password')
                .html(`<i class="bi ${show ? 'bi-eye-slash' : 'bi-eye'}"></i>`);
        });

        $('#btn-save-user').on('click', function () {
            if (!form.reportValidity()) return;
            const kd = $('#user-kd').val();
            const data = Object.assign(afselFormData(form), { type });
            if (kd) delete data.role_id;

            afselRequest({
                method: kd ? 'put' : 'post',
                url: kd ? profileUrl(kd) : storeUrl,
                data,
                confirm: kd ? `Simpan perubahan user "${form.name.value}"?` : `Tambah user "${form.name.value}"?`,
            });
        });
    });
</script>
@endsection
