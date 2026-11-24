{{--
    Menu sidebar Sales ATPM (rekursif). Data dari tabel RBAC ms_sales_atpm_menus
    (label, route_name, url); tetap kompatibel dengan kolom lama (title, route).
--}}
@php
    $salesMenuRoute = fn ($m) => $m->route_name ?? $m->route ?? null;
    $salesMenuIsActive = function ($m) use (&$salesMenuIsActive, $salesMenuRoute) {
        $r = $salesMenuRoute($m);
        if ($r && request()->routeIs($r)) {
            return true;
        }
        foreach ($m->children ?? [] as $child) {
            if ($salesMenuIsActive($child)) {
                return true;
            }
        }
        return false;
    };
@endphp

@foreach ($menus as $menu)
    @php
        $menuLabel = $menu->label ?? $menu->title;
        $menuRoute = $salesMenuRoute($menu);
    @endphp

    @if ($menu->children && $menu->children->count())
        @php $isParentActive = $salesMenuIsActive($menu); @endphp

        <a href="#"
           class="menu-toggle {{ $isParentActive ? 'active' : '' }}"
           data-target="menu-{{ $menu->id }}">
            <span>
                <i class="{{ $menu->icon }}"></i>{{ $menuLabel }}
            </span>
            <i class="bi bi-chevron-down arrow {{ $isParentActive ? 'rotate' : '' }}"></i>
        </a>

        <div id="menu-{{ $menu->id }}" class="submenu {{ $isParentActive ? 'open' : '' }}">
            @include('sales.atpm.components.menu-item', ['menus' => $menu->children])
        </div>

    @else
        @php
            $isActive = $menuRoute && request()->routeIs($menuRoute);
            $href = $menuRoute && Route::has($menuRoute) ? route($menuRoute) : ($menu->url ?? '#');
        @endphp
        <a href="{{ $href }}"
           class="menu-link {{ $isActive ? 'active' : '' }}">
            <span>
                @if ($menu->icon)
                    <i class="{{ $menu->icon }}"></i>
                @endif
                {{ $menuLabel }}
            </span>
        </a>
    @endif
@endforeach
