@extends($acx['layout'])

@section('title', 'Access Configuration ' . $typeLabel)

@section('navtop')
    {{ view($acx['navtop']) }}
@endsection

@section('sidebar')
    {{ view($acx['sidebar']) }}
@endsection

{{--
    Access Configuration Sales (seperti Menu Access Configuration di eurokars-exam).
    Tab Per Role  → ms_sales_{type}_menu_roles
    Tab Per User  → ms_sales_{type}_menu_users (override: beri / cabut)
    Tab Preview   → simulasi sidebar per role (algoritma sama dengan RbacEngine::calculateMenuAccess)
--}}
@section('content')
<div class="content afsel-ui ma">
    @include('aftersales.atpm.page_access._header', [
        'title'    => 'Access Configuration',
        'tabRoute' => $acx['route'] . 'menu-access',
        'crumbs'   => [['title' => 'Access Configuration ' . $typeLabel]],
    ])

    <div class="ma-tabs" role="tablist" aria-label="Bagian access configuration">
        <button type="button" class="ma-tab is-active" role="tab" id="tab-role" aria-selected="true" aria-controls="panel-role" data-tab="role">
            <i class="bi bi-shield"></i> Per Role
        </button>
        <button type="button" class="ma-tab" role="tab" id="tab-user" aria-selected="false" aria-controls="panel-user" data-tab="user" tabindex="-1">
            <i class="bi bi-person"></i> Per User
        </button>
        <button type="button" class="ma-tab" role="tab" id="tab-preview" aria-selected="false" aria-controls="panel-preview" data-tab="preview" tabindex="-1">
            <i class="bi bi-eye"></i> Preview
        </button>
    </div>

    {{-- ===================== PER ROLE ===================== --}}
    <section id="panel-role" class="ma-panel" role="tabpanel" aria-labelledby="tab-role">
        <div class="ma-intro">
            <h2 class="ma-h2">Menu Access per Role</h2>
            <p>Pilih role, centang menu yang boleh dibuka, lalu simpan. Parent menu otomatis ikut tercentang.</p>
        </div>

        <div class="ma-grid">
            <aside class="ma-card ma-picker" aria-labelledby="role-pick-title">
                <div class="ma-picker-head">
                    <span id="role-pick-title"><i class="bi bi-shield"></i> Pilih Role</span>
                    <span class="ma-muted">{{ count($roles) }} role</span>
                </div>
                <div class="ma-picker-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="role-search" class="form-control form-control-sm" placeholder="Cari role..." aria-label="Cari role">
                </div>
                <div class="ma-picker-list" id="role-list" role="listbox" aria-label="Daftar role"></div>
            </aside>

            <div class="ma-card ma-main">
                <div class="ma-empty" id="role-empty">
                    <span class="ma-empty-icon"><i class="bi bi-shield"></i></span>
                    <strong>Belum ada role dipilih</strong>
                    <span>Pilih role di sebelah kiri untuk melihat dan mengubah akses menunya.</span>
                </div>

                <div id="role-editor" hidden>
                    <div class="ma-editor-head">
                        <div class="min-w-0">
                            <div class="ma-eyebrow">Role {{ $typeLabel }}</div>
                            <div class="ma-editor-title" id="role-title"></div>
                            <div class="ma-muted small" id="role-desc"></div>
                        </div>
                        <div class="ma-editor-actions">
                            <span class="ma-dirty" id="role-dirty" hidden><i class="bi bi-dot"></i>Belum disimpan</span>
                            <button type="button" class="btn-fi btn-fi-primary btn-fi-sm" id="btn-save-role"><i class="bi bi-save"></i> Simpan</button>
                        </div>
                    </div>

                    <div class="afsel-note" id="role-note" hidden></div>

                    <div class="ma-toolbar">
                        <div class="ma-search">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input type="search" id="role-menu-search" class="form-control form-control-sm" placeholder="Cari menu..." aria-label="Cari menu">
                        </div>
                        <span class="ma-count" id="role-count"></span>
                        <button type="button" class="btn-fi btn-fi-secondary btn-fi-sm" id="btn-role-all">Pilih semua</button>
                        <button type="button" class="btn-fi btn-fi-secondary btn-fi-sm" id="btn-role-none">Kosongkan</button>
                    </div>

                    <div class="ma-tree" id="role-tree"></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== PER USER ===================== --}}
    <section id="panel-user" class="ma-panel" role="tabpanel" aria-labelledby="tab-user" hidden>
        <div class="ma-intro">
            <h2 class="ma-h2">Override Menu per User</h2>
            <p>Secara default user mengikuti menu role-nya. Pakai <strong>Beri</strong> untuk menambah menu atau <strong>Cabut</strong> untuk menyembunyikan menu khusus untuk user ini.</p>
        </div>

        <div class="ma-grid">
            <aside class="ma-card ma-picker" aria-labelledby="user-pick-title">
                <div class="ma-picker-head">
                    <span id="user-pick-title"><i class="bi bi-person"></i> Pilih User</span>
                    <span class="ma-muted" id="user-result-count"></span>
                </div>
                <div class="ma-picker-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="user-search" class="form-control form-control-sm"
                           placeholder="{{ $type === 'dealer' ? 'Cari username, nama, atau dealer...' : 'Cari username atau nama...' }}" aria-label="Cari user">
                </div>
                <div class="ma-picker-list" id="user-list" role="listbox" aria-label="Daftar user"></div>
            </aside>

            <div class="ma-card ma-main">
                <div class="ma-empty" id="user-empty">
                    <span class="ma-empty-icon"><i class="bi bi-person"></i></span>
                    <strong>Belum ada user dipilih</strong>
                    <span>Cari dan pilih user di sebelah kiri. Akun user diambil dari database WRS Sales.</span>
                </div>

                <div id="user-editor" hidden>
                    <div class="ma-editor-head">
                        <div class="min-w-0">
                            <div class="ma-eyebrow">User {{ $typeLabel }}</div>
                            <div class="ma-editor-title" id="user-title"></div>
                            <div class="ma-muted small" id="user-sub"></div>
                            <div class="mt-1" id="user-roles"></div>
                        </div>
                        <div class="ma-editor-actions">
                            <span class="ma-dirty" id="user-dirty" hidden><i class="bi bi-dot"></i>Belum disimpan</span>
                            <button type="button" class="btn-fi btn-fi-secondary btn-fi-sm" id="btn-user-reset" title="Hapus semua override">Ikut role semua</button>
                            <button type="button" class="btn-fi btn-fi-primary btn-fi-sm" id="btn-save-user"><i class="bi bi-save"></i> Simpan</button>
                        </div>
                    </div>

                    <div class="afsel-note is-warn" id="user-note" hidden></div>

                    <div class="ma-toolbar">
                        <div class="ma-search">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input type="search" id="user-menu-search" class="form-control form-control-sm" placeholder="Cari menu..." aria-label="Cari menu">
                        </div>
                        <span class="ma-count" id="user-count"></span>
                        <span class="ma-legend">
                            <span><i class="bi bi-check-circle-fill text-success"></i> Tampil</span>
                            <span><i class="bi bi-dash-circle ma-muted"></i> Tidak tampil</span>
                        </span>
                    </div>

                    <div class="ma-tree" id="user-tree"></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== PREVIEW ===================== --}}
    <section id="panel-preview" class="ma-panel" role="tabpanel" aria-labelledby="tab-preview" hidden>
        <div class="ma-intro d-flex flex-wrap align-items-end justify-content-between gap-2">
            <div>
                <h2 class="ma-h2">Preview Menu per Role</h2>
                <p>Simulasi sidebar yang dilihat user saat role ini aktif (permission menu ikut dihitung, override per user tidak).</p>
            </div>
            <select id="preview-role" class="form-select form-select-sm ma-preview-select" aria-label="Pilih role untuk preview">
                <option value="">Pilih role...</option>
                @foreach ($roles as $role)
                    <option value="{{ $role['id'] }}">{{ $role['name'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="ma-preview-wrap">
            <div class="ma-preview-side" aria-label="Preview sidebar">
                <div class="ma-preview-brand">
                    <img src="{{ asset('assets/img/mazda_with_white_26.png') }}" alt="">
                    <span><strong>SALES</strong><em>{{ $typeLabel }}</em></span>
                </div>
                <div class="ma-preview-nav" id="preview-nav">
                    <div class="ma-preview-empty">Pilih role untuk melihat menu.</div>
                </div>
            </div>
            <div class="ma-card ma-preview-info" id="preview-info">
                <div class="ma-empty">
                    <span class="ma-empty-icon"><i class="bi bi-eye"></i></span>
                    <strong>Belum ada role dipilih</strong>
                    <span>Pilih role di atas untuk melihat menu yang dapat diakses.</span>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .ma .ma-tabs { display: flex; flex-wrap: wrap; gap: 4px; border-bottom: 1px solid var(--mz-line); margin-bottom: 18px; }
    .ma .ma-tab {
        display: inline-flex; align-items: center; gap: 8px; padding: 9px 16px; margin-bottom: -1px; border: 0; background: none;
        border-bottom: 2px solid transparent; font-size: 14px; font-weight: 600; color: #5b6672; cursor: pointer;
    }
    .ma .ma-tab:hover { color: var(--mz-ink); border-bottom-color: #c3ccd8; }
    .ma .ma-tab.is-active { color: var(--mz-red-dark); border-bottom-color: var(--mz-red); }
    .ma .ma-tab:focus-visible { outline: 2px solid var(--mz-red); outline-offset: -2px; }
    .ma .ma-intro { margin-bottom: 14px; }
    .ma .ma-intro p { margin: 2px 0 0; font-size: 13px; color: var(--mz-muted); }
    .ma .ma-h2 { font-size: 17px; font-weight: 700; color: var(--mz-ink); margin: 0; }
    .ma .ma-grid { display: grid; grid-template-columns: 320px minmax(0, 1fr); gap: 16px; align-items: start; }
    .ma .ma-card { background: #fff; border-radius: 12px; box-shadow: var(--mz-shadow); border: 1px solid var(--mz-line-soft); }
    .ma .ma-muted { color: var(--mz-muted); }

    /* picker (role / user) */
    .ma .ma-picker { position: sticky; top: 16px; overflow: hidden; }
    .ma .ma-picker-head { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: var(--mz-bg-soft); border-bottom: 1px solid var(--mz-line); font-size: 12px; }
    .ma .ma-picker-head > span:first-child { font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--mz-ink); }
    .ma .ma-picker-search, .ma .ma-search { position: relative; }
    .ma .ma-picker-search { padding: 12px 14px; border-bottom: 1px solid var(--mz-line); }
    .ma .ma-picker-search .bi, .ma .ma-search .bi { position: absolute; left: 25px; top: 50%; transform: translateY(-50%); color: var(--mz-muted); font-size: 13px; pointer-events: none; }
    .ma .ma-search .bi { left: 10px; }
    .ma .ma-picker-search input, .ma .ma-search input { padding-left: 32px; }
    .ma .ma-picker-list { max-height: calc(100vh - 290px); min-height: 160px; overflow-y: auto; padding: 6px; }
    .ma .ma-pick {
        display: flex; align-items: center; gap: 12px; width: 100%; padding: 10px; border: 1px solid transparent; border-radius: 10px;
        background: none; text-align: left; cursor: pointer; transition: background .15s, border-color .15s;
    }
    .ma .ma-pick:hover { background: var(--mz-bg-soft); }
    .ma .ma-pick.is-active { background: var(--mz-red-tint); border-color: rgba(31, 111, 209, .35); }
    .ma .ma-pick:focus-visible { outline: 2px solid var(--mz-red); outline-offset: -2px; }
    .ma .ma-pick-icon { flex: 0 0 38px; width: 38px; height: 38px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: #eef2f7; color: var(--mz-ink-2); font-size: 16px; }
    .ma .ma-pick.is-active .ma-pick-icon { background: var(--mz-red); color: #fff; }
    .ma .ma-pick-text { flex: 1 1 auto; min-width: 0; }
    .ma .ma-pick-name { display: block; font-weight: 700; font-size: 13px; color: var(--mz-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ma .ma-pick-sub { display: block; font-size: 12px; color: var(--mz-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ma .ma-pick-lock { flex: 0 0 auto; font-size: 12px; padding: 3px 8px; border-radius: 999px; background: #fff4d6; color: #8a6100; border: 1px solid #f2dca4; }
    .ma .ma-picker-msg { padding: 18px; text-align: center; font-size: 12px; color: var(--mz-muted); }

    /* editor */
    .ma .ma-main { min-height: 260px; }
    .ma .ma-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; padding: 56px 24px; text-align: center; font-size: 13px; color: var(--mz-muted); }
    .ma .ma-empty strong { color: var(--mz-ink); font-size: 14px; }
    .ma .ma-empty-icon { width: 52px; height: 52px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #eef2f7; color: var(--mz-ink-2); font-size: 20px; margin-bottom: 8px; }
    .ma .ma-editor-head { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 12px; padding: 16px 18px; border-bottom: 1px solid var(--mz-line); }
    .ma .ma-eyebrow { font-size: 10px; letter-spacing: .16em; text-transform: uppercase; color: var(--mz-muted); }
    .ma .ma-editor-title { font-size: 17px; font-weight: 700; color: var(--mz-ink); }
    .ma .ma-editor-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .ma .ma-dirty { font-size: 12px; font-weight: 600; color: #8a6100; }
    .ma #role-editor > .afsel-note, .ma #user-editor > .afsel-note { margin: 12px 18px 0; }
    .ma .ma-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 12px 18px; border-bottom: 1px solid var(--mz-line-soft); }
    .ma .ma-toolbar .ma-search { flex: 1 1 220px; max-width: 320px; }
    .ma .ma-count { font-size: 12px; color: var(--mz-muted); margin-right: auto; }
    .ma .ma-legend { display: inline-flex; gap: 12px; font-size: 12px; color: var(--mz-muted); }

    /* tree */
    .ma .ma-tree { padding: 6px 10px 12px; max-height: calc(100vh - 330px); min-height: 200px; overflow-y: auto; }
    .ma .ma-row { display: flex; align-items: center; gap: 10px; padding: 7px 8px; border-radius: 8px; font-size: 13px; color: var(--mz-ink); }
    .ma .ma-row:hover { background: #f6f8fb; }
    .ma .ma-row.is-root { font-weight: 700; }
    .ma .ma-row.is-root:not(:first-child) { margin-top: 6px; border-top: 1px dashed var(--mz-line); border-radius: 0; padding-top: 11px; }
    .ma .ma-row label { display: flex; align-items: center; gap: 10px; flex: 1 1 auto; min-width: 0; margin: 0; cursor: pointer; }
    .ma .ma-row .form-check-input { margin: 0; flex: 0 0 auto; }
    .ma .ma-row-icon { width: 18px; text-align: center; color: var(--mz-ink-2); flex: 0 0 auto; }
    .ma .ma-row-label { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ma .ma-row-meta { flex: 0 0 auto; display: inline-flex; gap: 4px; }
    .ma .ma-chip { display: inline-block; font-size: 10px; font-weight: 600; padding: 1px 7px; border-radius: 999px; background: #eef2f7; color: var(--mz-ink-2); white-space: nowrap; }
    .ma .ma-chip.is-perm { background: #fff; border: 1px solid var(--mz-line); font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 500; }
    .ma .ma-chip.is-role { background: var(--mz-red-tint); color: var(--mz-red-dark); }
    .ma .ma-chip.is-grant { background: #e7f4ec; color: #146c43; }
    .ma .ma-chip.is-revoke { background: #fde8e8; color: #b42318; }
    .ma .ma-row.is-hidden-search { display: none; }
    .ma mark { background: #fff1a8; padding: 0 1px; border-radius: 2px; }

    /* segmented control per user */
    .ma .ma-seg { display: inline-flex; border: 1px solid #cfd6e0; border-radius: 8px; overflow: hidden; flex: 0 0 auto; }
    .ma .ma-seg button { border: 0; background: #fff; padding: 3px 10px; font-size: 12px; font-weight: 600; color: #5b6672; cursor: pointer; }
    .ma .ma-seg button + button { border-left: 1px solid #cfd6e0; }
    .ma .ma-seg button[aria-pressed="true"][data-mode="default"] { background: var(--mz-ink); color: #fff; }
    .ma .ma-seg button[aria-pressed="true"][data-mode="grant"] { background: #198754; color: #fff; }
    .ma .ma-seg button[aria-pressed="true"][data-mode="revoke"] { background: #b42318; color: #fff; }
    .ma .ma-seg button:focus-visible { outline: 2px solid var(--mz-red); outline-offset: -2px; }
    .ma .ma-state { flex: 0 0 18px; text-align: center; }

    /* preview */
    .ma .ma-preview-select { width: auto; min-width: 240px; }
    .ma .ma-preview-wrap { display: grid; grid-template-columns: 270px minmax(0, 1fr); gap: 16px; align-items: start; }
    .ma .ma-preview-side { border-radius: 12px; overflow: hidden; background: linear-gradient(180deg, #002856, #003f88); color: #f5f8fc; box-shadow: var(--mz-shadow); min-height: 360px; }
    .ma .ma-preview-brand { display: flex; align-items: center; gap: 10px; padding: 14px 16px; border-bottom: 1px solid rgba(255, 255, 255, .12); }
    .ma .ma-preview-brand img { height: 22px; }
    .ma .ma-preview-brand span { display: flex; flex-direction: column; line-height: 1.15; }
    .ma .ma-preview-brand strong { font-size: .8rem; letter-spacing: .14em; }
    .ma .ma-preview-brand em { font-style: normal; font-size: .6rem; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; background: #2f7fe8; padding: 1px 7px; border-radius: 3px; align-self: flex-start; margin-top: 3px; }
    .ma .ma-preview-nav { padding: 8px 8px 14px; font-size: 12.5px; }
    .ma .ma-prev-item { display: flex; align-items: center; gap: 9px; padding: 6px 10px; border-radius: 6px; color: #d6e2f2; }
    .ma .ma-prev-item.lvl-0 { color: #fff; font-weight: 600; }
    .ma .ma-prev-item .bi { width: 16px; text-align: center; color: #a9c1e2; }
    .ma .ma-prev-children { margin: 1px 0 4px 18px; padding-left: 4px; border-left: 2px solid rgba(47, 127, 232, .7); background: rgba(0, 18, 46, .3); border-radius: 0 6px 6px 0; }
    .ma .ma-preview-empty { padding: 18px 10px; text-align: center; color: #b5c7df; font-size: 12px; }
    .ma .ma-preview-info { padding: 16px 18px; }
    .ma .ma-stat-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; margin: 12px 0 14px; }
    .ma .ma-stat { padding: 10px 12px; border-radius: 10px; background: var(--mz-bg-soft); }
    .ma .ma-stat b { display: block; font-size: 20px; color: var(--mz-ink); }
    .ma .ma-stat span { font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: var(--mz-muted); }

    @media (max-width: 992px) {
        .ma .ma-grid, .ma .ma-preview-wrap { grid-template-columns: 1fr; }
        .ma .ma-picker { position: static; }
        .ma .ma-picker-list { max-height: 260px; }
        .ma .ma-tree { max-height: none; }
    }
</style>

@include('aftersales.atpm.page_access._scripts')
<script>
(function () {
    const TYPE  = @json($type);
    const MENUS = @json($menus);
    const ROLES = @json($roles);
    const URL_ROLE_SYNC = @json(route($acx['route'] . 'menu-access.role.sync'));
    const URL_USERS     = @json(route($acx['route'] . 'menu-access.users'));
    const URL_USER      = @json(url('sales/atpm/access/menu-access/user'));

    const esc = s => $('<div>').text(s ?? '').html();
    const byId = {}; const children = {};
    MENUS.forEach(m => { byId[m.id] = m; (children[m.parent_id ?? 0] = children[m.parent_id ?? 0] || []).push(m.id); });
    const ancestors = id => { const out = []; let p = byId[id]?.parent_id; let g = 0; while (p && byId[p] && g++ < 50) { out.push(p); p = byId[p].parent_id; } return out; };
    const descendants = id => { const out = []; const st = [...(children[id] || [])]; while (st.length) { const c = st.pop(); out.push(c); st.push(...(children[c] || [])); } return out; };
    const iconHtml = m => `<i class="${esc(m.icon || (m.depth ? 'bi bi-dot' : 'bi bi-circle'))}"></i>`;
    const highlight = (text, q) => {
        if (!q) return esc(text);
        const i = text.toLowerCase().indexOf(q);
        return i < 0 ? esc(text) : esc(text.slice(0, i)) + '<mark>' + esc(text.slice(i, i + q.length)) + '</mark>' + esc(text.slice(i + q.length));
    };
    /** Hapus menu yang parent-nya tidak ada di set (berapa pun kedalamannya) — sama seperti sidebar. */
    const prune = set => { let changed = true; while (changed) { changed = false; for (const id of [...set]) { const p = byId[id]?.parent_id; if (p && !set.has(p)) { set.delete(id); changed = true; } } } return set; };
    /** Filter tree untuk pencarian: tampilkan menu yang cocok + leluhurnya. */
    const searchFilter = (rowsSel, q) => {
        const match = new Set();
        if (q) MENUS.forEach(m => { if ((m.label + ' ' + (m.name || '')).toLowerCase().includes(q)) { match.add(m.id); ancestors(m.id).forEach(a => match.add(a)); } });
        $(rowsSel).each(function () {
            const id = Number(this.dataset.id);
            this.classList.toggle('is-hidden-search', !!q && !match.has(id));
            $(this).find('.ma-row-label').html(highlight(byId[id].label, q));
        });
    };
    const confirmLeave = dirty => !dirty || confirm('Perubahan belum disimpan. Lanjutkan tanpa menyimpan?');

    // ───────────── Tabs ─────────────
    const tabs = Array.from(document.querySelectorAll('.ma-tab'));
    const showTab = name => {
        tabs.forEach(t => {
            const on = t.dataset.tab === name;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            t.tabIndex = on ? 0 : -1;
            document.getElementById('panel-' + t.dataset.tab).hidden = !on;
        });
        if (name === 'user' && !userLoaded) loadUsers('');
        try { history.replaceState(null, '', '#' + name); } catch (e) {}
    };
    tabs.forEach((t, i) => {
        t.addEventListener('click', () => showTab(t.dataset.tab));
        t.addEventListener('keydown', e => {
            if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
            const next = tabs[(i + (e.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length];
            next.focus(); showTab(next.dataset.tab);
        });
    });

    // ───────────── PER ROLE ─────────────
    let role = null, roleSel = new Set(), roleSaved = '', roleDirty = false;

    const roleSub = r => r.is_system ? 'Semua menu aktif' : `${r.menu_ids.length} menu · ${r.users_count} user`;
    const renderRoleList = () => {
        const q = $('#role-search').val().trim().toLowerCase();
        const list = ROLES.filter(r => !q || r.name.toLowerCase().includes(q) || r.slug.includes(q));
        $('#role-list').html(list.length ? list.map(r => `
            <button type="button" class="ma-pick ${role && role.id === r.id ? 'is-active' : ''}" role="option" aria-selected="${role && role.id === r.id}" data-id="${r.id}">
                <span class="ma-pick-icon"><i class="bi ${r.is_system ? 'bi-shield-fill-check' : 'bi-shield'}"></i></span>
                <span class="ma-pick-text">
                    <span class="ma-pick-name">${esc(r.name)}${r.is_active ? '' : ' <span class="ma-chip">Nonaktif</span>'}</span>
                    <span class="ma-pick-sub">${esc(roleSub(r))}</span>
                </span>
                ${(r.is_system || !r.can_manage) ? '<span class="ma-pick-lock" title="' + (r.is_system ? 'Super Admin: semua menu' : 'Di luar wewenang Anda') + '"><i class="bi bi-lock"></i></span>' : ''}
            </button>`).join('') : '<div class="ma-picker-msg">Role tidak ditemukan.</div>');
    };

    const roleLocked = () => !role || role.is_system || !role.can_manage;
    const setRoleDirty = () => {
        roleDirty = [...roleSel].sort((a, b) => a - b).join(',') !== roleSaved;
        $('#role-dirty').prop('hidden', !roleDirty);
        $('#role-count').text(`${role.is_system ? MENUS.length : roleSel.size} dari ${MENUS.length} menu dipilih`);
    };

    const renderRoleTree = () => {
        const locked = roleLocked();
        $('#role-tree').html(MENUS.map(m => {
            const checked = role.is_system || roleSel.has(m.id);
            return `<div class="ma-row ${m.depth ? '' : 'is-root'}" data-id="${m.id}" style="padding-left:${8 + m.depth * 26}px">
                <label>
                    <input type="checkbox" class="form-check-input" value="${m.id}" ${checked ? 'checked' : ''} ${locked ? 'disabled' : ''}>
                    <span class="ma-row-icon">${iconHtml(m)}</span>
                    <span class="ma-row-label">${esc(m.label)}</span>
                </label>
                <span class="ma-row-meta">${m.permission_slug ? `<span class="ma-chip is-perm" title="Hanya tampil jika role punya permission ini">${esc(m.permission_slug)}</span>` : ''}</span>
            </div>`;
        }).join(''));
        searchFilter('#role-tree .ma-row', $('#role-menu-search').val().trim().toLowerCase());
        setRoleDirty();
    };

    const selectRole = id => {
        if (role && role.id === id) return;
        if (!confirmLeave(roleDirty)) return;
        role = ROLES.find(r => r.id === id);
        roleSel = new Set(role.menu_ids);
        roleSaved = [...roleSel].sort((a, b) => a - b).join(',');
        $('#role-empty').prop('hidden', true);
        $('#role-editor').prop('hidden', false);
        $('#role-title').text(role.name);
        $('#role-desc').text(role.description || ('Slug: ' + role.slug + ' · Level ' + role.level));
        const note = role.is_system
            ? '<i class="bi bi-shield-fill-check"></i><span>Super Admin otomatis mendapat <strong>semua menu aktif</strong> dan semua permission, sehingga tidak perlu diatur.</span>'
            : (!role.can_manage ? '<i class="bi bi-lock"></i><span>Role ini di luar wewenang Anda (level lebih tinggi). Hanya bisa dilihat.</span>' : '');
        $('#role-note').html(note).prop('hidden', !note);
        $('#btn-save-role, #btn-role-all, #btn-role-none').prop('disabled', roleLocked());
        renderRoleList();
        renderRoleTree();
    };

    $('#role-search').on('input', renderRoleList);
    $('#role-list').on('click', '.ma-pick', function () { selectRole(Number(this.dataset.id)); });
    $('#role-menu-search').on('input', function () { searchFilter('#role-tree .ma-row', this.value.trim().toLowerCase()); });

    $('#role-tree').on('change', 'input[type=checkbox]', function () {
        const id = Number(this.value);
        if (this.checked) { roleSel.add(id); ancestors(id).forEach(a => roleSel.add(a)); }
        else { roleSel.delete(id); descendants(id).forEach(d => roleSel.delete(d)); }
        $('#role-tree input[type=checkbox]').each(function () { this.checked = roleSel.has(Number(this.value)); });
        setRoleDirty();
    });
    $('#btn-role-all').on('click', () => { MENUS.forEach(m => roleSel.add(m.id)); renderRoleTree(); });
    $('#btn-role-none').on('click', () => { roleSel.clear(); renderRoleTree(); });

    $('#btn-save-role').on('click', function () {
        if (roleLocked()) return;
        afselRequest({
            url: URL_ROLE_SYNC,
            data: { type: TYPE, role_id: role.id, menu_ids: [...roleSel] },
            confirm: `Simpan ${roleSel.size} menu untuk role "${role.name}"?`,
            reload: false,
        }).then(res => {
            if (!res) return;
            role.menu_ids = res.menu_ids || [...roleSel];
            roleSel = new Set(role.menu_ids);
            roleSaved = [...roleSel].sort((a, b) => a - b).join(',');
            renderRoleList(); renderRoleTree();
        });
    });

    // ───────────── PER USER ─────────────
    let userLoaded = false, user = null, userData = null, userOv = {}, userSaved = '', userDirty = false, searchTimer;

    const loadUsers = q => {
        userLoaded = true;
        $('#user-list').html('<div class="ma-picker-msg"><span class="spinner-border spinner-border-sm"></span> Memuat user...</div>');
        axios.get(URL_USERS, { params: { type: TYPE, q } }).then(res => {
            const rows = res.data.data || [];
            $('#user-result-count').text(q ? `${rows.length} hasil` : 'Punya role');
            $('#user-list').html(rows.length ? rows.map(u => `
                <button type="button" class="ma-pick ${user && user.kd_user === u.kd_user ? 'is-active' : ''}" role="option" data-id="${esc(u.kd_user)}">
                    <span class="ma-pick-icon"><i class="bi ${u.is_active ? 'bi-person' : 'bi-person-slash'}"></i></span>
                    <span class="ma-pick-text">
                        <span class="ma-pick-name">${esc(u.name)}</span>
                        <span class="ma-pick-sub">${esc(u.username)}${u.dealer ? ' · ' + esc(u.dealer) : ''}${u.is_active ? '' : ' · Nonaktif'}</span>
                    </span>
                </button>`).join('') : `<div class="ma-picker-msg">${q ? 'User tidak ditemukan.' : 'Ketik untuk mencari user.'}</div>`);
        }).catch(() => $('#user-list').html('<div class="ma-picker-msg text-danger">Gagal memuat user dari database WRS Sales.</div>'));
    };

    $('#user-search').on('input', function () { clearTimeout(searchTimer); const q = this.value.trim(); searchTimer = setTimeout(() => loadUsers(q), 350); });

    const ovKey = o => Object.keys(o).sort().map(k => k + ':' + o[k]).join(',');
    const hasSystemRole = () => userData && userData.user.roles.some(r => r.is_system);
    const roleGives = id => hasSystemRole() || !!userData.role_menus[id];
    const effectiveSet = () => {
        const set = new Set();
        MENUS.forEach(m => { const mode = userOv[m.id] || 'default'; if (mode === 'grant' || (mode === 'default' && roleGives(m.id))) set.add(m.id); });
        return prune(set);
    };

    const renderUserTree = () => {
        const eff = effectiveSet();
        $('#user-tree').html(MENUS.map(m => {
            const mode = userOv[m.id] || 'default';
            const fromRole = userData.role_menus[m.id];
            const raw = mode === 'grant' || (mode === 'default' && roleGives(m.id));
            const state = eff.has(m.id)
                ? '<i class="bi bi-check-circle-fill text-success" title="Tampil di sidebar user"></i>'
                : `<i class="bi bi-dash-circle ma-muted" title="${raw ? 'Tidak tampil karena parent menu tidak tampil' : 'Tidak tampil'}"></i>`;
            const seg = ['default', 'grant', 'revoke'].map(md =>
                `<button type="button" data-mode="${md}" aria-pressed="${mode === md}">${{ default: 'Ikut role', grant: 'Beri', revoke: 'Cabut' }[md]}</button>`).join('');
            return `<div class="ma-row ${m.depth ? '' : 'is-root'}" data-id="${m.id}" style="padding-left:${8 + m.depth * 26}px">
                <span class="ma-state">${state}</span>
                <span class="ma-row-icon">${iconHtml(m)}</span>
                <span class="ma-row-label">${esc(m.label)}</span>
                <span class="ma-row-meta ms-auto">
                    ${hasSystemRole() ? '' : (fromRole ? `<span class="ma-chip is-role" title="Dari role: ${esc(fromRole.join(', '))}">${esc(fromRole[0])}${fromRole.length > 1 ? ' +' + (fromRole.length - 1) : ''}</span>` : '')}
                    ${mode === 'grant' ? '<span class="ma-chip is-grant">Diberi</span>' : ''}${mode === 'revoke' ? '<span class="ma-chip is-revoke">Dicabut</span>' : ''}
                </span>
                <span class="ma-seg" role="group" aria-label="Akses ${esc(m.label)}">${seg}</span>
            </div>`;
        }).join(''));
        searchFilter('#user-tree .ma-row', $('#user-menu-search').val().trim().toLowerCase());
        userDirty = ovKey(userOv) !== userSaved;
        $('#user-dirty').prop('hidden', !userDirty);
        const n = Object.keys(userOv).length;
        $('#user-count').text(`${eff.size} menu tampil · ${n} override`);
    };

    const selectUser = kd => {
        if (user && user.kd_user === kd) return;
        if (!confirmLeave(userDirty)) return;
        $('#user-empty').prop('hidden', true);
        $('#user-editor').prop('hidden', false);
        $('#user-tree').html('<div class="ma-picker-msg"><span class="spinner-border spinner-border-sm"></span> Memuat akses user...</div>');
        axios.get(URL_USER + '/' + encodeURIComponent(kd), { params: { type: TYPE } }).then(res => {
            userData = res.data; user = res.data.user;
            userOv = Object.assign({}, res.data.overrides);
            userSaved = ovKey(userOv); userDirty = false;
            $('#user-title').text(user.name);
            $('#user-sub').text(user.username + ' · ' + user.kd_user + (user.is_active ? '' : ' · Nonaktif di WRS Sales'));
            $('#user-roles').html(user.roles.length
                ? user.roles.map((r, i) => `<span class="afsel-tag ${i === 0 ? 'is-dark' : ''}">${esc(r.name)}</span>`).join('')
                : '<span class="afsel-tag is-soft-red">Belum ada role</span>');
            const notes = [];
            if (!user.roles.length) notes.push('User belum punya role Sales sehingga belum bisa login. Atur role di Access Control &gt; User Access.');
            if (hasSystemRole()) notes.push('User punya role Super Admin: saat role itu aktif semua menu tampil dan override tidak berlaku.');
            $('#user-note').html(notes.map(n => '<i class="bi bi-info-circle"></i><span>' + n + '</span>').join('<br>')).prop('hidden', !notes.length);
            $('#user-list .ma-pick').each(function () { this.classList.toggle('is-active', this.dataset.id === kd); });
            renderUserTree();
        }).catch(err => {
            $('#user-tree').html(`<div class="ma-picker-msg text-danger">${esc(err.response?.data?.message || 'Gagal memuat akses user.')}</div>`);
        });
    };

    $('#user-list').on('click', '.ma-pick', function () { selectUser(this.dataset.id); });
    $('#user-menu-search').on('input', function () { searchFilter('#user-tree .ma-row', this.value.trim().toLowerCase()); });
    $('#user-tree').on('click', '.ma-seg button', function () {
        const id = Number($(this).closest('.ma-row').data('id'));
        const mode = this.dataset.mode;
        if (mode === 'default') delete userOv[id]; else userOv[id] = mode;
        renderUserTree();
    });
    $('#btn-user-reset').on('click', () => { userOv = {}; renderUserTree(); });
    $('#btn-save-user').on('click', function () {
        if (!user) return;
        afselRequest({
            url: URL_USER + '/' + encodeURIComponent(user.kd_user),
            data: { type: TYPE, overrides: userOv },
            confirm: `Simpan override menu untuk "${user.name}"?`,
            reload: false,
        }).then(res => { if (res) { userSaved = ovKey(userOv); renderUserTree(); } });
    });

    // ───────────── PREVIEW ─────────────
    const renderPreview = id => {
        const r = ROLES.find(x => x.id === id);
        if (!r) {
            $('#preview-nav').html('<div class="ma-preview-empty">Pilih role untuk melihat menu.</div>');
            return;
        }
        const perms = new Set(r.perm_slugs);
        const set = r.is_system
            ? new Set(MENUS.map(m => m.id))
            : prune(new Set(MENUS.filter(m => r.menu_ids.includes(m.id) && (!m.permission_slug || perms.has(m.permission_slug))).map(m => m.id)));
        const blockedByPerm = r.is_system ? [] : MENUS.filter(m => r.menu_ids.includes(m.id) && m.permission_slug && !perms.has(m.permission_slug));

        const build = (parentId, lvl) => (children[parentId] || []).filter(cid => set.has(cid)).map(cid => {
            const m = byId[cid];
            const kids = build(cid, lvl + 1);
            return `<div class="ma-prev-item lvl-${Math.min(lvl, 1)}">${iconHtml(m)}<span>${esc(m.label)}</span>${kids ? '<i class="bi bi-chevron-down ms-auto" style="font-size:10px"></i>' : ''}</div>`
                + (kids ? `<div class="ma-prev-children">${kids}</div>` : '');
        }).join('');

        $('#preview-nav').html(build(0, 0) || '<div class="ma-preview-empty">Role ini belum punya menu.</div>');

        const leaves = [...set].filter(i => byId[i].route_name || byId[i].url).length;
        $('#preview-info').html(`
            <div class="ma-eyebrow">Preview role</div>
            <div class="ma-editor-title">${esc(r.name)} ${r.is_system ? '<span class="afsel-tag is-dark">Super Admin</span>' : ''}</div>
            <div class="ma-muted small">${esc(r.description || '')}</div>
            <div class="ma-stat-row">
                <div class="ma-stat"><b>${set.size}</b><span>Menu tampil</span></div>
                <div class="ma-stat"><b>${leaves}</b><span>Halaman</span></div>
                <div class="ma-stat"><b>${r.is_system ? 'Semua' : r.perm_slugs.length}</b><span>Permission</span></div>
                <div class="ma-stat"><b>${r.users_count}</b><span>User</span></div>
            </div>
            ${blockedByPerm.length ? `<div class="afsel-note is-warn"><i class="bi bi-exclamation-triangle"></i><span>${blockedByPerm.length} menu dicentang tapi tidak tampil karena role belum punya permission-nya: ${blockedByPerm.map(m => '<strong>' + esc(m.label) + '</strong> (<code>' + esc(m.permission_slug) + '</code>)').join(', ')}. Atur di Access Control &gt; Role.</span></div>` : ''}
            <div class="afsel-note"><i class="bi bi-info-circle"></i><span>Override per user (tab Per User) tidak termasuk dalam preview ini.</span></div>`);
    };
    $('#preview-role').on('change', function () { renderPreview(Number(this.value)); });

    // ───────────── init ─────────────
    renderRoleList();
    window.addEventListener('beforeunload', e => { if (roleDirty || userDirty) { e.preventDefault(); e.returnValue = ''; } });
    const initial = (location.hash || '').replace('#', '');
    if (['user', 'preview'].includes(initial)) showTab(initial);
})();
</script>
@endsection
