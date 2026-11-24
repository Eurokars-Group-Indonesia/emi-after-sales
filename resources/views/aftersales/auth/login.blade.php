<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mazda After Sales</title>
    @include('partials.favicon')

    <link href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap/bootstrap-icons.css') }}" rel="stylesheet">

    <style>
        :root {
            --mz-ink: #111418;
            --mz-graphite: #1b2027;
            --mz-red: #a6192e;
            --mz-red-dark: #8c1427;
            --mz-line: #d3d7dc;
            --mz-muted: #6b747d;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, 'Helvetica Neue', Arial, sans-serif;
            background: #121519;
            color: var(--mz-ink);
        }

        /* ── Layout: foto kiri, panel login kanan ── */
        .lg {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(440px, 1fr);
            min-height: 100vh;
        }

        /* ── Panel foto ── */
        .lg-hero {
            position: relative;
            overflow: hidden;
            background: #0d0f12 url('{{ asset('assets/img/mazda-option.jpg') }}') 58% center / cover no-repeat;
            color: #fff;
        }
        .lg-hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(90deg, rgba(10, 12, 15, .78) 0%, rgba(10, 12, 15, .35) 45%, rgba(10, 12, 15, .15) 70%, rgba(10, 12, 15, .6) 100%),
                linear-gradient(0deg, rgba(10, 12, 15, .55) 0%, rgba(10, 12, 15, 0) 35%);
        }
        .lg-hero-inner {
            position: relative;
            z-index: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 48px 56px;
        }
        .lg-brand { display: flex; align-items: center; gap: 22px; }
        .lg-brand img { height: 62px; width: auto; filter: invert(1) drop-shadow(0 2px 6px rgba(0, 0, 0, .4)); }
        .lg-brand-divider { width: 2px; height: 58px; background: rgba(255, 255, 255, .85); }
        .lg-brand-text { font-size: 30px; font-weight: 600; letter-spacing: .01em; text-shadow: 0 2px 8px rgba(0, 0, 0, .45); }

        /* Tagline menempel di bawah panel foto (desktop) */
        .lg-tagline { position: relative; bottom: 4px; margin-top: auto; max-width: 560px; }
        .lg-tagline h1 {
            margin: 0;
            font-size: clamp(26px, 2.9vw, 42px);
            font-weight: 400;
            line-height: 1.22;
            text-shadow: 0 2px 12px rgba(0, 0, 0, .5);
        }
        .lg-tagline-line { width: 110px; height: 3px; margin-top: 24px; background: #fff; border-radius: 2px; }
        .lg-tagline-line::after { content: ''; display: block; width: 36px; height: 3px; background: var(--mz-red); }

        /* ── Panel login ── */
        .lg-side {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 56px;
            background: linear-gradient(160deg, #262b32 0%, #16191d 55%, #0f1114 100%);
        }
        /* Potongan miring di antara foto dan panel */
        .lg-side::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: -150px;
            width: 300px;
            background: linear-gradient(160deg, #262b32 0%, #16191d 60%);
            clip-path: polygon(62% 0, 100% 0, 100% 100%, 0 100%);
            pointer-events: none;
        }

        .lg-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 400px;
            background: #f6f6f7;
            border-radius: 14px;
            padding: 34px 32px 22px;
            box-shadow: 0 30px 70px rgba(0, 0, 0, .45);
        }
        .lg-title { margin: 0; text-align: center; font-size: 26px; font-weight: 700; letter-spacing: -.01em; color: var(--mz-ink); }
        .lg-subtitle, .lg-divider {
            display: flex;
            align-items: center;
            gap: 16px;
            color: #3a424b;
            text-align: center;
        }
        .lg-subtitle { margin: 6px 0 22px; font-size: 15px; }
        .lg-subtitle::before, .lg-subtitle::after,
        .lg-divider::before, .lg-divider::after { content: ''; flex: 1; height: 1px; background: #cfd3d8; }

        .lg-alert {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            padding: 10px 14px;
            margin-bottom: 16px;
            border-radius: 8px;
            background: #fff1f3;
            border: 1px solid #f0c3ca;
            color: var(--mz-red-dark);
            font-size: 14px;
        }

        .lg-field { position: relative; margin-bottom: 12px; }
        .lg-field > .bi {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            color: #4a535c;
            pointer-events: none;
        }
        .lg-input {
            width: 100%;
            height: 42px;
            padding: 0 44px 0 42px;
            font-size: 14px;
            color: var(--mz-ink);
            background: #fff;
            border: 1px solid var(--mz-line);
            border-radius: 8px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .lg-input::placeholder { color: #8a929c; }
        .lg-input:focus { border-color: var(--mz-graphite); box-shadow: 0 0 0 3px rgba(166, 25, 46, .15); }
        .lg-input.is-invalid { border-color: var(--mz-red); }
        .lg-eye {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 6px;
            background: none;
            color: #4a535c;
            font-size: 16px;
            cursor: pointer;
        }
        .lg-eye:hover { color: var(--mz-ink); background: #f0f1f3; }
        .lg-eye:focus-visible, .lg-btn:focus-visible, .lg-help a:focus-visible { outline: 2px solid var(--mz-red); outline-offset: 2px; }

        /* Login sebagai: ATPM / Dealer */
        .lg-role { display: grid; grid-template-columns: 1fr 1fr; gap: 0; margin-bottom: 18px; padding: 3px; background: #e9eaec; border-radius: 10px; }
        .lg-role input { position: absolute; opacity: 0; pointer-events: none; }
        .lg-role label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 34px;
            margin: 0;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            color: #4a535c;
            cursor: pointer;
            transition: background .15s, color .15s, box-shadow .15s;
        }
        .lg-role input:checked + label { background: #fff; color: var(--mz-ink); box-shadow: 0 1px 4px rgba(0, 0, 0, .12), inset 0 -2px 0 var(--mz-red); }
        .lg-role input:focus-visible + label { outline: 2px solid var(--mz-red); outline-offset: 1px; }
        .lg-role-label { font-size: 13px; color: var(--mz-muted); margin: 0 0 6px 2px; }

        .lg-btn {
            width: 100%;
            height: 42px;
            border: 0;
            border-radius: 8px;
            background: var(--mz-ink);
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: .01em;
            box-shadow: 0 6px 16px rgba(0, 0, 0, .25);
            transition: background .15s, transform .1s;
        }
        .lg-btn:hover { background: #000; }
        .lg-btn:active { transform: translateY(1px); }
        .lg-btn[disabled] { opacity: .8; cursor: progress; }

        .lg-divider { margin: 22px 0 10px; font-size: 13px; color: #4a535c; }
        .lg-help { text-align: center; font-size: 13px; color: var(--mz-muted); }
        .lg-help a, .lg-help .lg-support {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
            font-size: 14px;
            color: var(--mz-red);
            text-decoration: none;
        }
        .lg-help a:hover { color: var(--mz-red-dark); text-decoration: underline; }
        .lg-help .lg-support { color: #3a424b; }
        .lg-footer { margin: 18px 0 0; text-align: center; font-size: 13px; color: #6b747d; }

        /* ── Responsive ── */
        @media (max-width: 1200px) {
            .lg { grid-template-columns: minmax(0, 1.2fr) minmax(420px, 1fr); }
            .lg-hero-inner, .lg-side { padding: 40px; }
        }

        /* Tablet & HP: foto jadi banner di atas, kartu login menimpa bagian bawahnya */
        @media (max-width: 900px) {
            .lg { grid-template-columns: 1fr; grid-template-rows: auto 1fr; }
            .lg-hero { min-height: 300px; background-position: center 40%; }
            .lg-hero-inner { padding: 28px 28px 110px; }
            .lg-tagline { margin-top: 32px; }
            .lg-side { align-items: flex-start; padding: 0 24px 32px; background: #16191d; }
            .lg-side::before { display: none; }
            .lg-card { margin-top: -86px; }
        }

        @media (max-width: 480px) {
            .lg-hero { min-height: 250px; }
            .lg-hero-inner { padding: 22px 20px 96px; }
            .lg-brand { gap: 14px; }
            .lg-brand img { height: 44px; }
            .lg-brand-divider { height: 40px; }
            .lg-brand-text { font-size: 22px; }
            .lg-tagline { margin-top: 24px; }
            .lg-tagline h1 { font-size: 24px; }
            .lg-tagline-line { margin-top: 16px; width: 80px; }
            .lg-side { padding: 0 14px 24px; }
            .lg-card { padding: 26px 20px 20px; border-radius: 12px; margin-top: -72px; }
            .lg-title { font-size: 24px; }
            .lg-subtitle { font-size: 14px; margin-bottom: 18px; }
            .lg-input, .lg-btn { height: 42px; font-size: 16px; }
        }

        /* Layar desktop yang pendek */
        @media (min-width: 901px) and (max-height: 720px) {
            .lg-card { padding: 26px 32px 18px; }
            .lg-subtitle { margin-bottom: 20px; }
            .lg-divider { margin-top: 20px; }
        }
    </style>
</head>
<body>
<main class="lg">

    {{-- Foto & brand --}}
    <section class="lg-hero" aria-label="Mazda After Sales">
        <div class="lg-hero-inner">
            <div class="lg-brand">
                <img src="{{ asset('assets/img/mazda_with_26.png') }}" alt="Mazda">
                <span class="lg-brand-divider" aria-hidden="true"></span>
                <span class="lg-brand-text">After Sales</span>
            </div>

            <div class="lg-tagline">
                <h1>Mazda After Sales</h1>
                <div class="lg-tagline-line" aria-hidden="true"></div>
            </div>
        </div>
    </section>

    {{-- Login --}}
    <section class="lg-side">
        <div class="lg-card">
            <h2 class="lg-title">After Sales</h2>
            <p class="lg-subtitle">System Access</p>

            @if ($errors->any())
                <div class="lg-alert" role="alert">
                    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ route('aftersales.login.auth') }}" method="post" id="login-form" novalidate>
                @csrf

                <div class="lg-field">
                    <i class="bi bi-person-fill" aria-hidden="true"></i>
                    <input type="text" class="lg-input @error('username') is-invalid @enderror"
                           name="username" id="username" placeholder="Username" aria-label="Username"
                           value="{{ old('username') }}" autocomplete="username" required autofocus>
                </div>

                <div class="lg-field">
                    <i class="bi bi-lock-fill" aria-hidden="true"></i>
                    <input type="password" class="lg-input @error('password') is-invalid @enderror"
                           name="password" id="password" placeholder="Password" aria-label="Password"
                           autocomplete="current-password" required>
                    <button type="button" class="lg-eye" id="toggle-password" aria-label="Tampilkan password" aria-pressed="false">
                        <i class="bi bi-eye-slash" aria-hidden="true"></i>
                    </button>
                </div>

                <p class="lg-role-label" id="login-as-label">Login sebagai</p>
                <div class="lg-role" role="radiogroup" aria-labelledby="login-as-label">
                    @php $loginAs = old('login_as', 'atpm'); @endphp
                    <input type="radio" name="login_as" id="login-as-atpm" value="atpm" {{ $loginAs === 'atpm' ? 'checked' : '' }} required>
                    <label for="login-as-atpm"><i class="bi bi-building" aria-hidden="true"></i> ATPM</label>
                    <input type="radio" name="login_as" id="login-as-dealer" value="dealer" {{ $loginAs === 'dealer' ? 'checked' : '' }}>
                    <label for="login-as-dealer"><i class="bi bi-shop" aria-hidden="true"></i> Dealer</label>
                </div>

                <button type="submit" class="lg-btn" id="btn-login">Login</button>
            </form>

            <div class="lg-divider">Need Help?</div>
            <div class="lg-help">
                Gunakan akun WRS After Sales Anda.<br>
                @if ($supportUrl = config('afsel.support_url'))
                    <a href="{{ $supportUrl }}" @if (str_starts_with($supportUrl, 'http')) target="_blank" rel="noopener" @endif>
                        <i class="bi bi-question-circle" aria-hidden="true"></i> Contact Support
                    </a>
                @else
                    <span class="lg-support"><i class="bi bi-question-circle" aria-hidden="true"></i> Hubungi Admin</span>
                @endif
            </div>

            <p class="lg-footer">Mazda After Sales System &copy; {{ date('Y') }}</p>
        </div>
    </section>

</main>

<script>
    (function () {
        var pwd = document.getElementById('password');
        var eye = document.getElementById('toggle-password');
        eye.addEventListener('click', function () {
            var show = pwd.type === 'password';
            pwd.type = show ? 'text' : 'password';
            eye.setAttribute('aria-pressed', show ? 'true' : 'false');
            eye.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
            eye.innerHTML = '<i class="bi ' + (show ? 'bi-eye' : 'bi-eye-slash') + '" aria-hidden="true"></i>';
            pwd.focus();
        });

        var form = document.getElementById('login-form');
        var btn = document.getElementById('btn-login');
        form.addEventListener('submit', function (e) {
            var user = document.getElementById('username');
            if (!user.value.trim()) { e.preventDefault(); user.focus(); user.classList.add('is-invalid'); return; }
            if (!pwd.value) { e.preventDefault(); pwd.focus(); pwd.classList.add('is-invalid'); return; }
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memproses...';
        });
        ['username', 'password'].forEach(function (id) {
            document.getElementById(id).addEventListener('input', function () { this.classList.remove('is-invalid'); });
        });
    })();
</script>
</body>
</html>
