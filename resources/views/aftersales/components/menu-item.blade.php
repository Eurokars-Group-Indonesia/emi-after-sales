{{--
    Menu sidebar After Sales (ATPM & Dealer), rekursif tanpa batas kedalaman.
    Logika sama dengan eurokars-exam (partials/sidebar-menu-item):
      - URL menu  : url → route_name → '#'
      - Aktif     : path halaman sama persis, atau diawali path menu (khusus path ≥ 2 segmen),
                    jadi halaman turunan (create/edit/detail) ikut menandai menunya.
      - Parent yang punya halaman sendiri: link + tombol chevron terpisah untuk buka/tutup.
    $level = kedalaman menu (0 = parent/top level), dipakai untuk gradasi warna per level.
--}}
@php
    $level = $level ?? 0;
    $currentPath = request()->path();

    $afselMenuUrl = function ($m) {
        if (!empty($m->url)) {
            return $m->url;
        }
        $r = $m->route_name ?? $m->route ?? null;

        return $r && Route::has($r) ? route($r) : '#';
    };
    $afselPathOf = fn ($u) => $u !== '#' ? ltrim(parse_url($u, PHP_URL_PATH) ?? '', '/') : '';
    $afselIsActivePath = function ($p) use ($currentPath) {
        if ($p === '') {
            return false;
        }

        return $currentPath === $p || ((substr_count($p, '/') + 1) >= 2 && str_starts_with($currentPath, $p . '/'));
    };
    $afselHasActiveDescendant = function ($m) use (&$afselHasActiveDescendant, $afselMenuUrl, $afselPathOf, $afselIsActivePath) {
        foreach ($m->children ?? [] as $child) {
            if ($afselIsActivePath($afselPathOf($afselMenuUrl($child))) || $afselHasActiveDescendant($child)) {
                return true;
            }
        }

        return false;
    };
    $lvlClass = 'lvl-' . min($level, 3);
@endphp

@foreach ($menus as $menu)
    @php
        $menuLabel = $menu->label ?? $menu->title;
        $menuUrl   = $afselMenuUrl($menu);
        $hasKids   = $menu->children && $menu->children->count();
        $isActive  = $afselIsActivePath($afselPathOf($menuUrl));
        $kidActive = $hasKids && $afselHasActiveDescendant($menu);
        $open      = $isActive || $kidActive;
        $groupId   = 'menu-' . $menu->id;
        $iconHtml  = $menu->icon
            ? '<i class="' . e($menu->icon) . '"></i>'
            : '<i class="bi ' . ($level > 0 ? 'bi-dot' : 'bi-circle') . '"></i>';
    @endphp

    <div class="menu-node" data-level="{{ $level }}">
        @if ($hasKids && $menuUrl !== '#')
            {{-- Punya halaman sendiri + children: link ke halamannya, chevron terpisah untuk buka/tutup --}}
            <div class="menu-row">
                <a href="{{ $menuUrl }}"
                   class="menu-link {{ $lvlClass }} {{ $isActive ? 'active' : '' }} {{ $kidActive ? 'active-parent' : '' }}"
                   @if ($isActive) aria-current="page" @endif>
                    <span class="menu-icon">{!! $iconHtml !!}</span>
                    <span class="menu-label">{{ $menuLabel }}</span>
                </a>
                <button type="button" class="menu-toggle menu-toggle-btn"
                        data-target="{{ $groupId }}"
                        aria-expanded="{{ $open ? 'true' : 'false' }}"
                        aria-controls="{{ $groupId }}"
                        aria-label="Sub menu {{ $menuLabel }}">
                    <i class="bi bi-chevron-right arrow" aria-hidden="true"></i>
                </button>
            </div>
        @elseif ($hasKids)
            <a href="#"
               class="menu-toggle {{ $lvlClass }} {{ $kidActive ? 'active-parent' : '' }}"
               data-target="{{ $groupId }}"
               role="button"
               aria-expanded="{{ $open ? 'true' : 'false' }}"
               aria-controls="{{ $groupId }}">
                <span class="menu-icon">{!! $iconHtml !!}</span>
                <span class="menu-label">{{ $menuLabel }}</span>
                <i class="bi bi-chevron-right arrow" aria-hidden="true"></i>
            </a>
        @else
            <a href="{{ $menuUrl }}"
               class="menu-link {{ $lvlClass }} {{ $isActive ? 'active' : '' }}"
               @if ($isActive) aria-current="page" @endif>
                <span class="menu-icon">{!! $iconHtml !!}</span>
                <span class="menu-label">{{ $menuLabel }}</span>
            </a>
        @endif

        @if ($hasKids)
            <div id="{{ $groupId }}" class="submenu sub-{{ $lvlClass }} {{ $open ? 'open' : '' }}">
                <div class="submenu-inner">
                    @include('aftersales.components.menu-item', ['menus' => $menu->children, 'level' => $level + 1])
                </div>
            </div>
        @endif
    </div>
@endforeach
