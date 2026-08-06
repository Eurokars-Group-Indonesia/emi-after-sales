<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sales</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #e8eaed;
        }

        /* ── full-page layout ── */
        .page-wrap {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── top bar with logo ── */
        .top-bar {
            padding: 18px 32px;
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }
        .top-bar img {
            height: 44px;
            object-fit: contain;
        }
        .top-bar .divider {
            width: 1px;
            height: 36px;
            background: #bbb;
        }
        .top-bar .brand-text {
            font-size: 0.78rem;
            color: #555;
            line-height: 1.4;
        }

        /* ── center area ── */
        .center-area {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 24px 24px;
        }

        /* ── login card ── */
        .login-card {
            background: #fff;
            border-radius: 6px;
            box-shadow: 0 8px 40px rgba(0,0,0,.13);
            display: flex;
            width: 100%;
            max-width: 860px;
            min-height: 380px;
            overflow: hidden;
        }

        /* left: car image panel */
        .card-visual {
            flex: 0 0 42%;
            background: linear-gradient(180deg, #002856, #003f88);
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: flex-end;
            justify-content: center;
            overflow: hidden;
            padding: 0;
            position: relative;
        }
        .card-visual img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center bottom;
            display: block;
        }
        .card-visual .car-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #aaa;
            font-size: 6rem;
        }

        /* right: form panel */
        .card-form {
            flex: 1;
            padding: 44px 40px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .card-form .title-main {
            font-size: 2.2rem;
            font-weight: 700;
            color: #444;
            line-height: 1.15;
            margin-bottom: 24px;
        }
        .card-form .title-main span {
            color: #003f88;
            display: block;
        }

        .card-form .form-control,
        .card-form .form-select {
            border-radius: 4px;
            border: 1px solid #ccc;
            padding: 10px 14px;
            font-size: 0.9rem;
            background: #f7f8fa;
            color: #333;
        }
        .card-form .form-control:focus,
        .card-form .form-select:focus {
            border-color: #003f88;
            box-shadow: 0 0 0 3px rgba(0,63,136,.15);
            background: #fff;
        }
        .card-form .form-control::placeholder { color: #aaa; }

        .card-form .form-check-label { font-size: 0.85rem; color: #666; }
        .card-form .forgot-link {
            font-size: 0.85rem;
            color: #666;
            text-decoration: none;
        }
        .card-form .forgot-link:hover { color: #003f88; }

        .btn-login {
            background: linear-gradient(180deg, #002856, #003f88);
            border: none;
            border-radius: 4px;
            padding: 10px 32px;
            font-weight: 600;
            font-size: 0.95rem;
            color: #fff;
            transition: opacity .2s;
        }
        .btn-login:hover { opacity: .85; color: #fff; }

        /* ── footer ── */
        .page-footer {
            text-align: center;
            padding: 14px;
            font-size: 0.78rem;
            color: #888;
            flex-shrink: 0;
        }

        /* ── responsive ── */
        @media (max-width: 640px) {
            .card-visual { display: none; }
            .card-form { padding: 32px 24px; }
            .card-form .title-main { font-size: 1.7rem; }
            .top-bar { justify-content: center; }
        }
    </style>
</head>
<body>
<div class="page-wrap">

    {{-- Top bar --}}
    <div class="top-bar">
        <img src="{{ asset('logo-with-text.png') }}" alt="EMI Sales" style="height:69px;">
        
    </div>

    {{-- Center card --}}
    <div class="center-area">
        <div class="login-card">

            {{-- Left: car visual --}}
            <div class="card-visual">
                <img src="{{ asset('assets/img/CX30-3.jpg') }}" alt="Mazda CX-30">
            </div>

            {{-- Right: form --}}
            <div class="card-form">
                <div class="title-main">
                    SALES
                    <!-- <span>Report Retention</span> -->
                </div>

                @if($errors->any())
                    <div class="alert alert-danger py-2 mb-3" style="font-size:.85rem; border-radius:4px;">
                        <i class="bi bi-exclamation-triangle me-1"></i>{{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('sales.login.auth') }}" method="post">
                    @csrf

                    <div class="mb-3">
                        <input type="text"
                               class="form-control @error('username') is-invalid @enderror"
                               name="username" id="username"
                               placeholder="Username"
                               value="{{ old('username') }}" required autofocus>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <input type="password"
                               class="form-control @error('password') is-invalid @enderror"
                               name="password" id="password"
                               placeholder="Password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <select class="form-select @error('login_as') is-invalid @enderror"
                                name="login_as" required>
                            <option value="">- Login As -</option>
                            <option value="atpm"   {{ old('login_as') == 'atpm'   ? 'selected' : '' }}>ATPM</option>
                            <option value="dealer" {{ old('login_as') == 'dealer' ? 'selected' : '' }}>Dealer</option>
                        </select>
                        @error('login_as')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember Me</label>
                        </div>
                    </div> -->

                    <button type="submit" class="btn btn-login">Login</button>
                    <div class="d-flex align-items-center justify-content-between mb-4" style="margin-top:10px;">
                       <p class="form-check-label">Please using your <b>WRS Application</b> Account</p>
                    </div>
                </form>
            </div>

        </div>
    </div>

    {{-- Footer --}}
    <div class="page-footer">
        &copy; 2026 IT Team Eurokars Group Indonesia. All rights reserved.
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
