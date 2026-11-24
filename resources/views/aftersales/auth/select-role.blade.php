<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Role - After Sales</title>
    @include('partials.favicon')

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        html, body { min-height: 100%; margin: 0; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; background: #e8eaed; }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px; }
        .card-role { width: 100%; max-width: 460px; background: #fff; border-radius: 6px; box-shadow: 0 4px 24px rgba(0,0,0,.08); padding: 32px; }
        .card-role h1 { font-size: 1.4rem; font-weight: 700; color: #003f88; margin-bottom: 4px; }
        .role-option { display: flex; gap: 12px; align-items: flex-start; border: 1px solid #dde1e6; border-radius: 6px; padding: 12px 14px; margin-bottom: 10px; cursor: pointer; }
        .role-option:hover { border-color: #003f88; }
        .role-option input { margin-top: 4px; }
        .role-option .desc { font-size: .8rem; color: #777; }
        .btn-primary { background: #003f88; border-color: #003f88; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card-role">
        <h1>Pilih Role</h1>
        <p class="text-muted small mb-4">
            Halo {{ session('user.name') }}, akun Anda memiliki lebih dari satu role.
            Pilih role yang ingin digunakan pada sesi ini.
        </p>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('aftersales.role.select.store') }}" method="POST">
            @csrf

            @foreach ($roles as $role)
                <label class="role-option">
                    <input type="radio" class="form-check-input" name="role_id" value="{{ $role->id }}"
                           {{ ($activeRoleId ?: $roles->first()->id) === $role->id ? 'checked' : '' }} required>
                    <span>
                        <strong>{{ $role->name }}</strong>
                        @if ($role->description)
                            <span class="d-block desc">{{ $role->description }}</span>
                        @endif
                    </span>
                </label>
            @endforeach

            <button type="submit" class="btn btn-primary w-100 mt-2">Lanjutkan</button>
        </form>

        <form action="{{ route('aftersales.logout') }}" method="POST" class="mt-3 text-center">
            @csrf
            <button type="submit" class="btn btn-link btn-sm text-danger">Logout</button>
        </form>
    </div>
</div>
</body>
</html>
