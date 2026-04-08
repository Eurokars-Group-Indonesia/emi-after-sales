<nav class="navbar navbar-light navbar-custom px-3 d-flex justify-content-between">
    <button class="btn btn-outline-secondary" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>

    <div class="d-flex align-items-center gap-3">
        <div class="dropdown">
            <i class="bi bi-bell fs-5" data-bs-toggle="dropdown" style="cursor:pointer"></i>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><a class="dropdown-item" href="#">No new notifications</a></li>
            </ul>
        </div>

        <div class="dropdown">
            <img src="https://i.pravatar.cc/40" class="rounded-circle" data-bs-toggle="dropdown" style="cursor:pointer">
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><span class="dropdown-item text-muted small">{{ session('user.name') }}</span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#">Profile</a></li>
                <li><a class="dropdown-item" href="#">Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('logout') }}" method="POST">
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
