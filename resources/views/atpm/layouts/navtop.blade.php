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
                <li><a class="dropdown-item" href="#">Profile</a></li>
                <li><a class="dropdown-item" href="#">Settings</a></li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item" href="#">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
