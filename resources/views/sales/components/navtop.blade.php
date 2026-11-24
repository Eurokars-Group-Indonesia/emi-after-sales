{{--
    Navbar atas WRS Connect Sales (ATPM & Dealer).
    Param: $portal ('ATPM' / 'Dealer'), $homeRoute
    Menampilkan role aktif (RBAC Sales) + tautan Ganti Role jika user punya lebih dari satu role.
--}}
@php
    $nameParts = preg_split('/\s+/', trim((string) session('user.name', 'U'))) ?: ['U'];
    $initials  = strtoupper(count($nameParts) >= 2
        ? mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[1], 0, 1)
        : mb_substr($nameParts[0], 0, 2));

    $salesActiveRole = sales_role();
    $salesRoleCount  = ($salesType = sales_type())
        ? app(\App\Services\Sales\SalesRbacService::class)->getAssignedRoles($salesType, (string) session('user.id'))->count()
        : 0;
    $subtitle = session('user.loginAs') === 'dealer'
        ? (session('user.nm_dealer') ?: session('user.kd_dealer'))
        : session('user.username');
@endphp

<nav class="navbar navbar-light navbar-custom px-3 d-flex justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3 min-w-0">
        <button type="button" class="sl-nav-toggle" onclick="toggleSidebar()" aria-label="Buka / tutup menu">
            <i class="bi bi-list"></i>
        </button>
        <a href="{{ route($homeRoute) }}" class="sl-nav-context text-decoration-none">
            <span class="sl-nav-context-app">WRS Connect Sales</span>
            <span class="sl-nav-context-title">{{ $portal }}{{ session('user.loginAs') === 'dealer' && session('user.nm_dealer') ? ' · ' . session('user.nm_dealer') : '' }}</span>
        </a>
    </div>

    <div class="d-flex align-items-center gap-3">
        @if ($salesActiveRole)
            @if ($salesRoleCount > 1)
                <a href="{{ route('sales.role.select') }}" class="sl-role-chip d-none d-sm-inline-flex" title="Ganti role aktif">
                    <i class="bi bi-person-badge"></i>{{ $salesActiveRole->name }}<i class="bi bi-arrow-left-right"></i>
                </a>
            @else
                <span class="sl-role-chip d-none d-sm-inline-flex" title="Role aktif">
                    <i class="bi bi-person-badge"></i>{{ $salesActiveRole->name }}
                </span>
            @endif
        @endif

        <div class="dropdown">
            <button type="button" class="sl-avatar-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu akun {{ session('user.name') }}">
                {{ $initials }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end sl-user-menu">
                <li class="sl-user-head">
                    <span class="sl-avatar-btn" aria-hidden="true">{{ $initials }}</span>
                    <span class="min-w-0">
                        <span class="d-block sl-user-name text-truncate">{{ session('user.name') }}</span>
                        <span class="d-block sl-user-sub text-truncate">{{ $subtitle }}</span>
                    </span>
                </li>
                @if ($salesActiveRole)
                    <li>
                        <span class="dropdown-item-text">
                            <i class="bi bi-person-badge me-1"></i>Role aktif: <strong>{{ $salesActiveRole->name }}</strong>
                        </span>
                    </li>
                    @if ($salesRoleCount > 1)
                        <li>
                            <a class="dropdown-item" href="{{ route('sales.role.select') }}">
                                <i class="bi bi-arrow-left-right me-1"></i> Ganti Role
                            </a>
                        </li>
                    @endif
                    <li><hr class="dropdown-divider my-1"></li>
                @endif
                <li>
                    <span class="dropdown-item-text text-muted">
                        <i class="bi bi-info-circle me-1"></i>Profil &amp; password diatur di WRS Sales
                    </span>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <form action="{{ route('sales.logout') }}" method="POST" class="mb-1">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right me-1"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
