{{--
    Sidebar WRS Connect (After Sales & Sales, ATPM & Dealer).
    Param:
      - $portal : label portal, mis. 'ATPM' / 'Dealer'
      - $menus  : koleksi menu (tree, children)
      - $brand  : judul aplikasi (default 'AFTER SALES')
      - $theme  : 'aftersales' (default: grafit + Soul Red) | 'sales' (biru Sales)
    Fitur: search menu, gradasi warna per level, multi-expand (menu lain tidak ikut tertutup),
           icon > saat tertutup dan v saat terbuka.
--}}
@php
    $brand = $brand ?? 'AFTER SALES';
    $theme = $theme ?? 'aftersales';
@endphp
<style>
    .sidebar {
        --mz-bg-top:      #121519;
        --mz-bg-bottom:   #1b2027;
        --mz-red:         #a6192e;   /* Mazda Soul Red */
        --mz-red-bright:  #d42a45;
        --mz-silver:      #c3c8ce;   /* Mazda silver */
        --mz-text-0:      #f4f5f7;   /* level 0 : parent */
        --mz-text-1:      #cdd2d8;   /* level 1 : sub menu */
        --mz-text-2:      #a9b0b9;   /* level 2 : sub+1 */
        --mz-text-3:      #8f97a1;   /* level 3+: sub+n */
        --mz-lvl-1-bg:    #20262e;
        --mz-lvl-2-bg:    #29303a;
        --mz-lvl-3-bg:    #323a46;
        --mz-red-rgb:     166, 25, 46;   /* rgb aksen untuk bayangan / transparansi */
        --mz-red-bright-rgb: 212, 42, 69;
        --mz-icon-1:      #9ea6b0;
        --mz-icon-2:      #8a929c;
        --mz-icon-3:      #7c848e;
        --mz-guide-2:     #6d7682;
        --mz-guide-3:     #56606c;

        font-size: 0.8rem;
        width: 250px;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        display: flex;
        flex-direction: column;
        background: linear-gradient(180deg, var(--mz-bg-top), var(--mz-bg-bottom));
        color: var(--mz-text-0);
        box-shadow: 4px 0 14px rgba(0, 0, 0, 0.25);
        border-right: 1px solid rgba(255, 255, 255, 0.04);
        z-index: 999;
        transition: transform .3s ease, margin-left .3s ease;
    }

    /* ── Brand ── */
    .sidebar .sb-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 16px 16px 14px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.07);
        position: relative;
    }
    .sidebar .sb-brand::after {
        content: '';
        position: absolute;
        left: 16px;
        bottom: -1px;
        width: 42px;
        height: 2px;
        background: var(--mz-red);
        border-radius: 2px;
    }
    .sidebar .sb-brand img {
        height: 26px;
        width: auto;
        /* File logo sudah putih (mazda_with_white_26.png): jangan di-invert lagi */
        opacity: .92;
    }
    .sidebar .sb-brand-text {
        display: flex;
        flex-direction: column;
        line-height: 1.15;
    }
    .sidebar .sb-brand-title {
        font-weight: 700;
        letter-spacing: .14em;
        font-size: .82rem;
        color: #fff;
    }
    .sidebar .sb-brand-portal {
        margin-top: 3px;
        align-self: flex-start;
        font-size: .62rem;
        font-weight: 600;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #fff;
        background: var(--mz-red);
        padding: 1px 7px;
        border-radius: 3px;
    }

    /* ── Search ── */
    .sidebar .sb-search {
        padding: 12px 12px 8px;
    }
    .sidebar .sb-search-box {
        position: relative;
    }
    .sidebar .sb-search-box .bi-search {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--mz-text-3);
        font-size: .78rem;
        pointer-events: none;
    }
    .sidebar .sb-search input {
        width: 100%;
        height: 32px;
        padding: 0 30px 0 30px;
        font-size: .78rem;
        color: var(--mz-text-0);
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.10);
        border-radius: 6px;
        outline: none;
        transition: border-color .15s, background .15s, box-shadow .15s;
    }
    .sidebar .sb-search input::placeholder { color: var(--mz-text-3); }
    .sidebar .sb-search input:focus {
        background: rgba(255, 255, 255, 0.09);
        border-color: var(--mz-red-bright);
        box-shadow: 0 0 0 3px rgba(var(--mz-red-rgb), .25);
    }
    .sidebar .sb-search-clear,
    .sidebar .sb-search-kbd {
        position: absolute;
        right: 7px;
        top: 50%;
        transform: translateY(-50%);
    }
    .sidebar .sb-search-clear {
        display: none;
        border: 0;
        background: transparent;
        color: var(--mz-text-2);
        padding: 0 4px;
        line-height: 1;
        cursor: pointer;
    }
    .sidebar .sb-search-clear:hover { color: #fff; }
    .sidebar .sb-search-kbd {
        font-size: .65rem;
        color: var(--mz-text-3);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 3px;
        padding: 0 5px;
        line-height: 1.4;
        pointer-events: none;
    }
    .sidebar .sb-search.has-value .sb-search-clear { display: block; }
    .sidebar .sb-search.has-value .sb-search-kbd { display: none; }

    /* ── Nav ── */
    .sidebar .sb-nav {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 4px 0 16px;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, .18) transparent;
    }
    .sidebar .sb-nav::-webkit-scrollbar { width: 6px; }
    .sidebar .sb-nav::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, .18); border-radius: 3px; }

    .sidebar .sb-empty {
        display: none;
        padding: 18px 16px;
        color: var(--mz-text-3);
        font-size: .76rem;
        text-align: center;
    }
    .sidebar .sb-empty i { display: block; font-size: 1.2rem; margin-bottom: 4px; }

    /* item dasar (parent maupun leaf) — selalu rata kiri */
    .sidebar .sb-nav a {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 10px;
        text-align: left;
        text-decoration: none;
        position: relative;
        padding: 7px 14px 7px 16px;
        margin: 1px 8px;
        border-radius: 6px;
        transition: background .15s, color .15s;
        font-size: .8rem;
    }
    .sidebar .sb-nav a .menu-icon {
        flex: 0 0 18px;
        display: inline-flex;
        justify-content: center;
        font-size: .85rem;
    }
    .sidebar .sb-nav a .menu-label {
        flex: 1 1 auto;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sidebar .sb-nav a .arrow {
        flex: 0 0 auto;
        font-size: .7rem;
        opacity: .75;
        transition: transform .25s ease;
    }
    .sidebar .sb-nav a[aria-expanded="true"] .arrow {
        transform: rotate(90deg);   /* > menjadi v */
        opacity: 1;
    }
    .sidebar .sb-nav a:hover {
        background: rgba(255, 255, 255, 0.07);
        color: #fff;
    }
    .sidebar .sb-nav a:focus-visible {
        outline: 2px solid var(--mz-red-bright);
        outline-offset: -2px;
    }

    /* Level 0 : parent menu */
    .sidebar .sb-nav a.lvl-0 {
        color: var(--mz-text-0);
        font-weight: 600;
        letter-spacing: .01em;
    }
    .sidebar .sb-nav a.lvl-0 .menu-icon { color: var(--mz-silver); }
    .sidebar .sb-nav a.lvl-0[aria-expanded="true"] {
        background: rgba(255, 255, 255, 0.05);
    }

    /* Level 1 : sub menu */
    .sidebar .sb-nav a.lvl-1 {
        color: var(--mz-text-1);
        font-weight: 500;
        padding-left: 12px;
        margin-left: 6px;
    }
    .sidebar .sb-nav a.lvl-1 .menu-icon { color: var(--mz-icon-1); flex-basis: 14px; font-size: .78rem; }

    /* Level 2 : sub+1 */
    .sidebar .sb-nav a.lvl-2 {
        color: var(--mz-text-2);
        font-size: .77rem;
        padding: 6px 12px 6px 10px;
        margin-left: 6px;
    }
    .sidebar .sb-nav a.lvl-2 .menu-icon { color: var(--mz-icon-2); flex-basis: 12px; font-size: .74rem; }

    /* Level 3+ : sub+n */
    .sidebar .sb-nav a.lvl-3 {
        color: var(--mz-text-3);
        font-size: .75rem;
        padding: 5px 12px 5px 10px;
        margin-left: 6px;
    }
    .sidebar .sb-nav a.lvl-3 .menu-icon { color: var(--mz-icon-3); flex-basis: 12px; font-size: .72rem; }

    /* Container sub menu: gradasi warna + garis panduan per level */
    .sidebar .submenu {
        display: grid;
        grid-template-rows: 0fr;
        transition: grid-template-rows .25s ease;
    }
    .sidebar .submenu.open { grid-template-rows: 1fr; }
    .sidebar .submenu-inner {
        overflow: hidden;
        min-height: 0;
        transition: margin .25s ease;
    }
    /* margin vertikal hanya saat terbuka, agar jarak antar parent tertutup tetap rata */
    .sidebar .submenu:not(.open) > .submenu-inner {
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }
    .sidebar .submenu.sub-lvl-0 > .submenu-inner {
        background: var(--mz-lvl-1-bg);
        margin: 2px 8px 4px 24px;
        border-left: 2px solid var(--mz-red);
        border-radius: 0 6px 6px 0;
    }
    .sidebar .submenu.sub-lvl-1 > .submenu-inner {
        background: var(--mz-lvl-2-bg);
        margin: 1px 8px 3px 20px;
        border-left: 2px solid var(--mz-guide-2);
        border-radius: 0 6px 6px 0;
    }
    .sidebar .submenu.sub-lvl-2 > .submenu-inner,
    .sidebar .submenu.sub-lvl-3 > .submenu-inner {
        background: var(--mz-lvl-3-bg);
        margin: 1px 8px 3px 18px;
        border-left: 2px dashed var(--mz-guide-3);
        border-radius: 0 6px 6px 0;
    }
    .sidebar .submenu-inner a { margin-right: 4px; }

    /* Parent yang punya halaman sendiri: link + tombol chevron terpisah */
    .sidebar .menu-row {
        display: flex;
        align-items: center;
    }
    .sidebar .menu-row > a { flex: 1 1 auto; min-width: 0; margin-right: 0; }
    .sidebar .menu-row > .menu-toggle-btn {
        flex: 0 0 28px;
        height: 28px;
        margin-right: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: inherit;
        cursor: pointer;
    }
    .sidebar .menu-row > .menu-toggle-btn:hover { background: rgba(255, 255, 255, 0.08); color: #fff; }
    .sidebar .menu-row > .menu-toggle-btn:focus-visible { outline: 2px solid var(--mz-red-bright); outline-offset: -2px; }
    .sidebar .menu-row > .menu-toggle-btn .arrow { font-size: .7rem; opacity: .75; transition: transform .25s ease; }
    .sidebar .menu-row > .menu-toggle-btn[aria-expanded="true"] .arrow { transform: rotate(90deg); opacity: 1; }
    .sidebar .menu-row > a.lvl-0 + .menu-toggle-btn { color: var(--mz-text-0); }

    /* Parent yang berisi menu aktif */
    .sidebar .sb-nav a.active-parent { color: #fff; }
    .sidebar .sb-nav a.active-parent > .menu-icon { color: var(--mz-red-bright); }

    /* Menu aktif (leaf) */
    .sidebar .sb-nav a.menu-link.active {
        color: #fff !important;
        font-weight: 600;
        background: linear-gradient(90deg, rgba(var(--mz-red-rgb), .95), rgba(var(--mz-red-rgb), .35));
        box-shadow: 0 2px 8px rgba(var(--mz-red-rgb), .30);
    }
    .sidebar .sb-nav a.menu-link.active .menu-icon { color: #fff !important; }
    .sidebar .sb-nav a.menu-link.active::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 6px;
        bottom: 6px;
        width: 3px;
        background: var(--mz-red-bright);
        border-radius: 0 3px 3px 0;
    }
    .sidebar .submenu-inner a.menu-link.active::before { display: none; }

    /* Search state */
    .sidebar .menu-node.search-hidden { display: none; }
    .sidebar .sb-nav mark {
        background: rgba(var(--mz-red-bright-rgb), .45);
        color: #fff;
        padding: 0 1px;
        border-radius: 2px;
    }

    /* ── Tema Sales: biru WRS Connect Sales (#002856 → #003f88) dengan aksen biru langit ── */
    .sidebar.sidebar-theme-sales {
        --mz-bg-top:      #002856;
        --mz-bg-bottom:   #003f88;
        --mz-red:         #2f7fe8;   /* aksen (badge, garis, menu aktif) */
        --mz-red-bright:  #6aa8ff;
        --mz-red-rgb:     47, 127, 232;
        --mz-red-bright-rgb: 106, 168, 255;
        --mz-silver:      #c9d8ec;
        --mz-text-0:      #f5f8fc;
        --mz-text-1:      #d6e2f2;
        --mz-text-2:      #b5c7df;
        --mz-text-3:      #93a9c7;
        --mz-lvl-1-bg:    rgba(0, 18, 46, .38);
        --mz-lvl-2-bg:    rgba(0, 14, 38, .45);
        --mz-lvl-3-bg:    rgba(0, 10, 30, .5);
        --mz-icon-1:      #a9c1e2;
        --mz-icon-2:      #92aed6;
        --mz-icon-3:      #7f9cc6;
        --mz-guide-2:     #4f78b0;
        --mz-guide-3:     #3e6497;
        box-shadow: 4px 0 14px rgba(0, 20, 50, .3);
    }
    .sidebar.sidebar-theme-sales .sb-brand { border-bottom-color: rgba(255, 255, 255, 0.12); }

    /* Mobile */
    @media (max-width: 768px) {
        .sidebar { transform: translateX(-100%); }
        .sidebar.show { transform: translateX(0); }
    }
</style>

<div class="sidebar sidebar-theme-{{ $theme }}" id="sidebar">
    <div class="sb-brand">
        <img src="{{ asset('assets/img/mazda_with_white_26.png') }}" alt="Mazda">
        <div class="sb-brand-text">
            <span class="sb-brand-title">{{ $brand }}</span>
            <span class="sb-brand-portal">{{ $portal }}</span>
        </div>
    </div>

    <div class="sb-search" id="sbSearch">
        <div class="sb-search-box">
            <i class="bi bi-search"></i>
            <input type="search" id="sbSearchInput" placeholder="Cari menu..." autocomplete="off" aria-label="Cari menu">
            <span class="sb-search-kbd" title="Tekan / untuk mencari">/</span>
            <button type="button" class="sb-search-clear" id="sbSearchClear" aria-label="Hapus pencarian">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <nav class="sb-nav" id="sbNav">
        @if ($menus->isEmpty())
            <div class="sb-empty" style="display:block">
                <i class="bi bi-inbox"></i>
                Tidak ada menu untuk role ini
            </div>
        @else
            @include('aftersales.components.menu-item', ['menus' => $menus, 'level' => 0])
        @endif

        <div class="sb-empty" id="sbEmpty">
            <i class="bi bi-search"></i>
            Menu tidak ditemukan
        </div>
    </nav>
</div>

<script>
    (function () {
        const nav = document.getElementById('sbNav');
        if (!nav) return;

        const searchWrap = document.getElementById('sbSearch');
        const input = document.getElementById('sbSearchInput');
        const clearBtn = document.getElementById('sbSearchClear');
        const empty = document.getElementById('sbEmpty');

        function setOpen(toggle, open) {
            const target = document.getElementById(toggle.dataset.target);
            if (!target) return;
            target.classList.toggle('open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        // Toggle sub menu: hanya menu yang diklik; menu lain yang terbuka tetap terbuka.
        nav.querySelectorAll('.menu-toggle').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                setOpen(this, this.getAttribute('aria-expanded') !== 'true');
            });
        });

        nav.querySelectorAll('.menu-link').forEach(function (link) {
            link.addEventListener('click', function () {
                nav.querySelectorAll('.menu-link.active').forEach(function (l) { l.classList.remove('active'); });
                this.classList.add('active');
                if (typeof closeSidebar === 'function') closeSidebar();
            });
        });

        // ── Search ──
        const labels = Array.from(nav.querySelectorAll('.menu-label'));
        labels.forEach(function (l) { l.dataset.text = l.textContent.trim(); });

        let savedOpen = null; // state buka/tutup sebelum pencarian

        function escapeHtml(s) {
            return s.replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function highlight(label, q) {
            const text = label.dataset.text;
            if (!q) { label.textContent = text; return; }
            const idx = text.toLowerCase().indexOf(q);
            if (idx < 0) { label.textContent = text; return; }
            label.innerHTML = escapeHtml(text.slice(0, idx))
                + '<mark>' + escapeHtml(text.slice(idx, idx + q.length)) + '</mark>'
                + escapeHtml(text.slice(idx + q.length));
        }

        // Kembalikan true jika node (atau turunannya) cocok.
        function filterNode(node, q, forceShow) {
            const link = node.querySelector(':scope > a, :scope > .menu-row > a');
            const toggle = node.querySelector(':scope > .menu-toggle, :scope > .menu-row > .menu-toggle');
            const label = link.querySelector('.menu-label');
            const selfMatch = label.dataset.text.toLowerCase().includes(q);
            highlight(label, selfMatch ? q : '');

            let childMatch = false;
            const sub = node.querySelector(':scope > .submenu > .submenu-inner');
            if (sub) {
                sub.querySelectorAll(':scope > .menu-node').forEach(function (child) {
                    if (filterNode(child, q, forceShow || selfMatch)) childMatch = true;
                });
                if (toggle) setOpen(toggle, childMatch);
            }

            const visible = forceShow || selfMatch || childMatch;
            node.classList.toggle('search-hidden', !visible);
            return selfMatch || childMatch;
        }

        function runSearch() {
            const q = input.value.trim().toLowerCase();
            searchWrap.classList.toggle('has-value', input.value.length > 0);
            const roots = nav.querySelectorAll(':scope > .menu-node');

            if (!q) {
                nav.querySelectorAll('.menu-node.search-hidden').forEach(function (n) { n.classList.remove('search-hidden'); });
                labels.forEach(function (l) { highlight(l, ''); });
                if (savedOpen) {
                    nav.querySelectorAll('.menu-toggle').forEach(function (t) {
                        setOpen(t, savedOpen.has(t.dataset.target));
                    });
                    savedOpen = null;
                }
                empty.style.display = 'none';
                return;
            }

            if (!savedOpen) {
                savedOpen = new Set(
                    Array.from(nav.querySelectorAll('.menu-toggle[aria-expanded="true"]')).map(function (t) { return t.dataset.target; })
                );
            }

            let any = false;
            roots.forEach(function (node) { if (filterNode(node, q, false)) any = true; });
            empty.style.display = any ? 'block' : 'none';
        }

        input.addEventListener('input', runSearch);

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                input.value = '';
                runSearch();
                input.blur();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const first = Array.from(nav.querySelectorAll('.menu-link')).find(function (a) {
                    return a.offsetParent !== null && a.querySelector('mark');
                });
                if (first && first.getAttribute('href') !== '#') window.location.href = first.href;
            }
        });

        clearBtn.addEventListener('click', function () {
            input.value = '';
            runSearch();
            input.focus();
        });

        // Tekan "/" untuk fokus ke pencarian menu
        document.addEventListener('keydown', function (e) {
            if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
            const tag = (document.activeElement && document.activeElement.tagName) || '';
            if (/^(INPUT|TEXTAREA|SELECT)$/.test(tag) || document.activeElement.isContentEditable) return;
            e.preventDefault();
            input.focus();
        });
    })();
</script>
