@foreach ($menus as $menu)
    @if ($menu->children->count())
        @php
            // cek apakah salah satu child (atau nested) adalah route aktif
            $isParentActive = false;
            $stack = $menu->children->toArray();
            foreach ($menu->children as $child) {
                if ($child->route && request()->routeIs($child->route)) {
                    $isParentActive = true;
                    break;
                }
                // satu level lebih dalam
                if (!empty($child['children'])) {
                    foreach ($child['children'] as $grandchild) {
                        if (!empty($grandchild['route']) && request()->routeIs($grandchild['route'])) {
                            $isParentActive = true;
                            break 2;
                        }
                    }
                }
            }
        @endphp

        <a href="#"
           class="menu-toggle {{ $isParentActive ? 'active' : '' }}"
           data-target="menu-{{ $menu->id }}">
            <span>
                <i class="{{ $menu->icon }}"></i>{{ $menu->title }}
            </span>
            <i class="bi bi-chevron-down arrow {{ $isParentActive ? 'rotate' : '' }}"></i>
        </a>

        <div id="menu-{{ $menu->id }}" class="submenu {{ $isParentActive ? 'open' : '' }}">
            @include('aftersales.atpm.components.menu-item', ['menus' => $menu->children])
        </div>

    @else
        @php
            $isActive = $menu->route && request()->routeIs($menu->route);
        @endphp
        <a href="{{ $menu->route ? route($menu->route) : '#' }}"
           class="menu-link {{ $isActive ? 'active' : '' }}">
            @if ($menu->icon)
                <i class="{{ $menu->icon }}"></i>
            @endif
            {{ $menu->title }}
        </a>
    @endif
@endforeach
