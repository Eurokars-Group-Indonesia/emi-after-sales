<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b1f3f">
    <title>Pilih Role - Mazda Sales</title>
    @include('partials.favicon')

    <link href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap/bootstrap-icons.css') }}" rel="stylesheet">

    <style>
        html, body { min-height: 100%; margin: 0; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
        body {
            background:
                radial-gradient(circle at 85% 0%, rgba(77, 147, 240, .35), transparent 40%),
                linear-gradient(135deg, #001b3d 0%, #002856 45%, #003f88 100%);
        }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px; }
        .card-role { width: 100%; max-width: 480px; background: #fff; border-radius: 16px; box-shadow: 0 24px 60px rgba(0, 15, 40, .35); overflow: hidden; }
        .card-role-head { padding: 22px 28px 18px; color: #fff; background: linear-gradient(135deg, #002856, #003f88); position: relative; }
        .card-role-head::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: linear-gradient(90deg, #1f6fd1, #4d93f0 55%, rgba(77, 147, 240, 0)); }
        .card-role-head img { height: 22px; opacity: .95; }
        .eyebrow { font-size: 10px; letter-spacing: .18em; text-transform: uppercase; color: #c9d8ec; margin-top: 12px; }
        .card-role-head h1 { font-size: 1.3rem; font-weight: 700; margin: 2px 0 0; }
        .card-role-body { padding: 22px 28px 26px; }
        .role-option { display: flex; gap: 12px; align-items: flex-start; border: 1px solid #dbe3ee; border-radius: 10px; padding: 12px 14px; margin-bottom: 10px; cursor: pointer; transition: border-color .15s, background .15s; }
        .role-option:hover { border-color: #1f6fd1; background: #f5f9ff; }
        .role-option:has(input:checked) { border-color: #003f88; background: #e9f2fd; box-shadow: 0 0 0 3px rgba(31, 111, 209, .12); }
        .role-option input { margin-top: 4px; }
        .role-option .desc { font-size: .8rem; color: #6b7a90; }
        .role-option .badge { font-size: .65rem; }
        .btn-primary { background: #003f88; border-color: #003f88; }
        .btn-primary:hover { background: #002856; border-color: #002856; }
        .form-check-input:checked { background-color: #003f88; border-color: #003f88; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card-role">
        <div class="card-role-head">
            <img src="{{ asset('assets/img/mazda_with_white_26.png') }}" alt="Mazda">
            <div class="eyebrow">WRS Connect Sales &middot; {{ strtoupper(session('user.loginAs')) }}</div>
            <h1>Pilih Role</h1>
        </div>
        <div class="card-role-body">
            <p class="text-muted small mb-3">
                Halo <strong>{{ session('user.name') }}</strong>, akun Anda memiliki lebih dari satu role Sales.
                Pilih role yang ingin digunakan pada sesi ini. Role bisa diganti kapan saja dari menu akun.
            </p>

            @if ($errors->any())
                <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('sales.role.select.store') }}" method="POST">
                @csrf

                @foreach ($roles as $role)
                    <label class="role-option">
                        <input type="radio" class="form-check-input" name="role_id" value="{{ $role->id }}"
                               {{ ($activeRoleId ?: $roles->first()->id) === $role->id ? 'checked' : '' }} required>
                        <span>
                            <strong>{{ $role->name }}</strong>
                            @if ($role->is_system)<span class="badge bg-dark ms-1">Super Admin</span>@endif
                            @if ($activeRoleId === $role->id)<span class="badge bg-primary ms-1">Aktif</span>@endif
                            @if ($role->description)
                                <span class="d-block desc">{{ $role->description }}</span>
                            @endif
                        </span>
                    </label>
                @endforeach

                <button type="submit" class="btn btn-primary w-100 mt-2">Lanjutkan</button>
            </form>

            <div class="d-flex justify-content-between align-items-center mt-3">
                @if ($activeRoleId)
                    <a href="{{ route($homeRoute) }}" class="btn btn-link btn-sm text-decoration-none px-0">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                @else
                    <span></span>
                @endif
                <form action="{{ route('sales.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-link btn-sm text-danger text-decoration-none px-0">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
