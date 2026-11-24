{{--
    Isi halaman Home WRS Connect Sales (ATPM & Dealer).
    Param: $portal ('ATPM' / 'Dealer'), $menus (tree menu RBAC Sales user yang sedang login)
    Akses cepat = semua menu yang punya halaman (selain Home), diambil dari menu sesuai role aktif.
--}}
@php
    $quickLinks = [];
    $collect = function ($items, ?string $group) use (&$collect, &$quickLinks) {
        foreach ($items as $m) {
            $url = $m->url ?: (($m->route_name && Route::has($m->route_name)) ? route($m->route_name) : null);
            if ($url && !request()->routeIs($m->route_name ?? '__none__')) {
                $quickLinks[] = ['label' => $m->label, 'url' => $url, 'icon' => $m->icon, 'group' => $group];
            }
            if ($m->children && $m->children->count()) {
                $collect($m->children, $group ? $group . ' › ' . $m->label : $m->label);
            }
        }
    };
    $collect($menus ?? collect(), null);

    $role = sales_role();
    $hour = (int) now()->format('G');
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));
@endphp

<div class="sl-hero">
    <div class="sl-hero-date">
        {{ now()->locale('id')->translatedFormat('l') }}
        <strong>{{ now()->locale('id')->translatedFormat('d F Y') }}</strong>
    </div>
    <div class="sl-hero-eyebrow">WRS Connect Sales &middot; {{ $portal }}</div>
    <div class="sl-hero-title">{{ $greeting }}, {{ session('user.name') }}</div>
    <p class="sl-hero-sub">
        @if (session('user.loginAs') === 'dealer' && session('user.nm_dealer'))
            {{ session('user.nm_dealer') }}
        @else
            Pilih menu di bawah atau gunakan pencarian menu di sidebar (tekan <kbd>/</kbd>).
        @endif
    </p>
    <div class="sl-hero-meta">
        <span class="sl-hero-pill"><i class="bi bi-person"></i>{{ session('user.username') }}</span>
        @if ($role)
            <span class="sl-hero-pill"><i class="bi bi-person-badge"></i>{{ $role->name }}</span>
        @endif
        <span class="sl-hero-pill"><i class="bi bi-grid"></i>{{ count($quickLinks) }} menu tersedia</span>
    </div>
</div>

<div class="sl-section-title"><i class="bi bi-lightning-charge"></i>Akses cepat</div>

@if (count($quickLinks))
    <div class="sl-quick-grid">
        @foreach ($quickLinks as $link)
            <a href="{{ $link['url'] }}" class="sl-quick">
                <span class="sl-quick-icon" aria-hidden="true"><i class="{{ $link['icon'] ?: 'bi bi-box-arrow-up-right' }}"></i></span>
                <span class="sl-quick-text">
                    <span class="sl-quick-label">{{ $link['label'] }}</span>
                    <span class="sl-quick-group">{{ $link['group'] ?: 'Menu utama' }}</span>
                </span>
            </a>
        @endforeach
    </div>
@else
    <div class="sl-empty">
        <i class="bi bi-inbox"></i>
        Belum ada menu untuk role Anda. Hubungi administrator WRS Connect Sales.
    </div>
@endif
