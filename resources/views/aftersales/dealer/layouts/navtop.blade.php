<nav class="navbar navbar-light navbar-custom px-3 d-flex justify-content-between">
    <div class="d-flex align-items-center gap-3 min-w-0">
        <button class="btn btn-outline-secondary" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>

        @if (session('user.kd_dealer'))
            {{-- Dealer user login --}}
            <div class="min-w-0 lh-sm" title="{{ session('user.nm_dealer') }} · {{ session('user.kd_dealer') }}">
                <div class="fw-semibold text-truncate" style="font-size:.9rem;max-width:min(52vw,460px)">
                    {{ session('user.nm_dealer') ?: 'Dealer' }}
                </div>
                <div class="text-muted" style="font-size:.75rem">Kode dealer {{ session('user.kd_dealer') }}</div>
            </div>
        @endif
    </div>

    @php
        $navUserName = trim((string) session('user.name', ''));
        $navWords = preg_split('/\s+/', $navUserName, -1, PREG_SPLIT_NO_EMPTY);
        $navInitials = collect($navWords)
            ->take(2)
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
            ->implode('');
        $navInitials = $navInitials !== '' ? $navInitials : 'U';
    @endphp

    <div class="d-flex align-items-center gap-3">
        <div class="dropdown">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-semibold"
                 data-bs-toggle="dropdown"
                 title="{{ $navUserName }}"
                 style="width:40px;height:40px;cursor:pointer;font-size:0.95rem;user-select:none">
                {{ $navInitials }}
            </div>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><span class="dropdown-item text-muted small">{{ session('user.name') }}</span></li>
                {{-- Role aktif & Ganti Role ada di halaman Profile --}}
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="{{ route('aftersales.dealer.profile') }}">
                        <i class="bi bi-person-circle me-1"></i> Profile
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('aftersales.logout') }}" method="POST">
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
