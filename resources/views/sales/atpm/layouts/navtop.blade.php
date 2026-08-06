<nav class="navbar navbar-light navbar-custom px-3 d-flex justify-content-between">
    <button class="btn btn-outline-secondary" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>

    <div class="d-flex align-items-center gap-3">
        <!-- <div class="dropdown">
            <i class="bi bi-bell fs-5" data-bs-toggle="dropdown" style="cursor:pointer"></i>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><a class="dropdown-item" href="#">No new notifications</a></li>
            </ul>
        </div> -->

        <div class="dropdown">
            @php
                $nameParts = explode(' ', trim(session('user.name', 'U')));
                $initials = count($nameParts) >= 2
                    ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1))
                    : strtoupper(substr($nameParts[0], 0, 2));
            @endphp
            <div data-bs-toggle="dropdown" style="cursor:pointer; width:40px; height:40px; border-radius:50%; background:#003f88; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:14px; user-select:none;">
                {{ $initials }}
            </div>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><span class="dropdown-item text-muted small">{{ session('user.name') }}</span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#">Profile see on WRS</a></li>
                <!-- <li><a class="dropdown-item" href="#">Settings</a></li> -->
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('sales.logout') }}" method="POST">
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



