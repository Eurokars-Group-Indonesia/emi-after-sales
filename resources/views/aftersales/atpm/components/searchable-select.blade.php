{{--
    Select yang bisa dicari (tanpa library tambahan).
    Pakai: <select data-afsel-search ...> lalu @include file ini sekali di halaman.
    Opsi: data-afsel-search="float" (daftar melayang), data-placeholder="..." (teks placeholder).
    <select> asli tetap dipakai sebagai nilai form (disembunyikan), jadi FormData / afselFormData tetap jalan.
    API: afselSearchSelect(selectEl) → { sync(), focus(), validate() }
--}}
<style>
    .afsel-ss { position: relative; }
    .afsel-ss-input-wrap { position: relative; }
    .afsel-ss-input-wrap .bi-search { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 12px; color: var(--mz-muted, #6b747d); pointer-events: none; }
    .afsel-ss-input-wrap .form-control { padding-left: 30px; padding-right: 30px; }
    .afsel-ss-clear { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); border: 0; background: none; padding: 0 4px; color: var(--mz-muted, #6b747d); line-height: 1; }
    .afsel-ss-clear:hover { color: var(--mz-red, #a6192e); }
    .afsel-ss-list {
        list-style: none; margin: 4px 0 0; padding: 4px; max-height: 240px; overflow-y: auto;
        background: #fff; border: 1px solid var(--mz-line, #e2e5e9); border-radius: 8px; box-shadow: 0 6px 16px rgba(16, 24, 40, .08);
    }
    .afsel-ss-item { display: flex; justify-content: space-between; gap: 8px; padding: 6px 10px; border-radius: 6px; font-size: 13px; cursor: pointer; color: var(--mz-ink, #1b2027); }
    .afsel-ss-item small { color: var(--mz-muted, #6b747d); font-size: 11px; white-space: nowrap; }
    .afsel-ss-item.is-active { background: var(--mz-bg-soft, #f6f7f9); }
    .afsel-ss-item.is-selected { font-weight: 600; box-shadow: inset 3px 0 0 var(--mz-red, #a6192e); }
    .afsel-ss-item mark { padding: 0; background: var(--mz-red-tint, #fff1f3); color: var(--mz-red, #a6192e); }
    .afsel-ss-empty { padding: 8px 10px; font-size: 12px; color: var(--mz-muted, #6b747d); }
    .afsel-ss-count { font-size: 11px; color: var(--mz-muted, #6b747d); margin-top: 4px; }
    /* data-afsel-search="float": daftar melayang di atas konten (untuk filter di toolbar) */
    .afsel-ss.is-float .afsel-ss-list { position: absolute; left: 0; right: 0; min-width: 240px; z-index: 1060; }
</style>
<script>
    window.afselSearchSelect = function (select) {
        if (select._afselSS) return select._afselSS;

        const uid = 'afsel-ss-' + Math.random().toString(36).slice(2, 8);
        const placeholderOpt = select.querySelector('option[value=""]');
        const options = () => Array.from(select.options).filter(o => o.value !== '' && !o.disabled);
        const norm = s => (s || '').toLowerCase();

        const wrap = document.createElement('div');
        wrap.className = 'afsel-ss' + (select.dataset.afselSearch === 'float' ? ' is-float' : '');
        wrap.innerHTML = `
            <div class="afsel-ss-input-wrap">
                <i class="bi bi-search"></i>
                <input type="text" class="form-control form-control-sm" autocomplete="off" role="combobox"
                       aria-autocomplete="list" aria-expanded="false" aria-controls="${uid}">
                <button type="button" class="afsel-ss-clear" aria-label="Kosongkan pilihan" hidden><i class="bi bi-x-lg"></i></button>
            </div>
            <ul class="afsel-ss-list" id="${uid}" role="listbox" hidden></ul>
            <div class="invalid-feedback"></div>`;
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);
        select.hidden = true;
        select.tabIndex = -1;

        const input = wrap.querySelector('input');
        const list = wrap.querySelector('ul');
        const clearBtn = wrap.querySelector('.afsel-ss-clear');
        const feedback = wrap.querySelector('.invalid-feedback');
        if (select.id) {
            document.querySelectorAll(`label[for="${select.id}"]`).forEach(l => l.setAttribute('for', select.id + '-search'));
            input.id = select.id + '-search';
        }
        input.placeholder = select.dataset.placeholder
            || 'Ketik untuk mencari' + (placeholderOpt ? ' — ' + placeholderOpt.text.replace(/^-\s*|\s*-$/g, '').toLowerCase() : '');
        if (select.getAttribute('aria-label')) input.setAttribute('aria-label', select.getAttribute('aria-label'));

        let active = -1;
        let items = [];

        const label = o => o.dataset.label || o.text;
        const highlight = (text, q) => {
            const frag = document.createDocumentFragment();
            const i = q ? norm(text).indexOf(q) : -1;
            if (i < 0) { frag.append(text); return frag; }
            const m = document.createElement('mark');
            m.textContent = text.slice(i, i + q.length);
            frag.append(text.slice(0, i), m, text.slice(i + q.length));
            return frag;
        };

        function render() {
            const q = norm(input.value.trim());
            const selectedText = select.value ? norm(select.selectedOptions[0].text) : null;
            // Saat input masih menampilkan pilihan sekarang, tampilkan semua opsi.
            const term = q === selectedText ? '' : q;
            items = options().filter(o => !term || norm(o.text).includes(term) || norm(o.value).includes(term));
            list.innerHTML = '';
            if (!items.length) {
                list.innerHTML = '<li class="afsel-ss-empty">Tidak ada yang cocok.</li>';
            }
            items.forEach((o, idx) => {
                const li = document.createElement('li');
                li.className = 'afsel-ss-item' + (o.value === select.value ? ' is-selected' : '');
                li.id = `${uid}-${idx}`;
                li.setAttribute('role', 'option');
                li.setAttribute('aria-selected', o.value === select.value);
                const name = document.createElement('span');
                name.append(highlight(label(o), term));
                li.append(name);
                if (o.dataset.code) {
                    const code = document.createElement('small');
                    code.append(highlight(o.dataset.code, term));
                    li.append(code);
                }
                li.addEventListener('mousedown', e => { e.preventDefault(); choose(o); });
                list.append(li);
            });
            setActive(items.findIndex(o => o.value === select.value));
        }

        function setActive(idx) {
            active = idx;
            list.querySelectorAll('.afsel-ss-item').forEach((li, i) => li.classList.toggle('is-active', i === idx));
            const el = list.children[idx];
            input.setAttribute('aria-activedescendant', el && el.id ? el.id : '');
            if (el) el.scrollIntoView({ block: 'nearest' });
        }

        function open() { render(); list.hidden = false; input.setAttribute('aria-expanded', 'true'); }
        function close() { list.hidden = true; input.setAttribute('aria-expanded', 'false'); }

        function choose(o) {
            select.value = o ? o.value : '';
            select.dispatchEvent(new Event('change', { bubbles: true }));
            sync();
            close();
            if (o) clearInvalid();
        }

        function clearInvalid() {
            input.classList.remove('is-invalid');
            feedback.style.display = '';
        }

        function sync() {
            input.value = select.value ? select.selectedOptions[0].text : '';
            clearBtn.hidden = !select.value;
        }

        input.addEventListener('focus', open);
        input.addEventListener('click', open);
        input.addEventListener('input', () => { open(); setActive(items.length ? 0 : -1); });
        input.addEventListener('blur', () => { close(); sync(); });
        input.addEventListener('keydown', e => {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (list.hidden) return open();
                const step = e.key === 'ArrowDown' ? 1 : -1;
                setActive(Math.max(0, Math.min(items.length - 1, active + step)));
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (!list.hidden && items[active]) choose(items[active]);
            } else if (e.key === 'Escape' && !list.hidden) {
                e.preventDefault();
                e.stopPropagation(); // jangan ikut menutup modal
                close();
                sync();
            }
        });
        clearBtn.addEventListener('click', () => { choose(null); input.focus(); });
        select.addEventListener('change', sync);
        if (select.form) select.form.addEventListener('reset', () => setTimeout(() => { sync(); clearInvalid(); }));

        sync();

        return (select._afselSS = {
            sync,
            focus: () => input.focus(),
            validate(message) {
                const ok = !!select.value;
                if (ok) {
                    clearInvalid();
                    return true;
                }
                input.classList.add('is-invalid');
                feedback.textContent = message || 'Wajib dipilih.';
                feedback.style.display = 'block';
                input.focus();
                return false;
            },
        });
    };

    document.querySelectorAll('select[data-afsel-search]').forEach(el => window.afselSearchSelect(el));
</script>
