<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0b1f3f">
    <title>Login - Mazda Sales</title>
    @include('partials.favicon')

    <link href="{{ asset('assets/vendor/bootstrap/bootstrap-icons.min.css') }}" rel="stylesheet">

    <style>
        :root {
            --sl-navy: #0b1f3f;
            --sl-blue: #003f88;
            --sl-blue-dark: #002856;
            --sl-sky: #2f6fd1;
            --sl-ink: #14181f;
            --sl-text: #3b4350;
            --sl-muted: #6b7482;
            --sl-line: #d7dce3;
            --sl-field: #f5f7fa;
            --sl-danger: #b42318;
            --sl-surface: #ffffff;
            --sl-page: #eef1f5;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, 'Helvetica Neue', Arial, sans-serif;
            background: var(--sl-page);
            color: var(--sl-ink);
            -webkit-font-smoothing: antialiased;
        }

        /* ── Layout: foto kiri, form kanan ── */
        .sl {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(460px, 1fr);
            min-height: 100vh;
            min-height: 100dvh;
        }

        /* ── Panel foto ── */
        .sl-hero {
            position: relative;
            overflow: hidden;
            color: #fff;
            background: var(--sl-navy) url('{{ asset('assets/img/All-New-Mazda-CX-60-front-1.jpg') }}') 42% center / cover no-repeat;
        }
        .sl-hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg, rgba(11, 31, 63, .72) 0%, rgba(11, 31, 63, .12) 38%, rgba(11, 31, 63, .2) 62%, rgba(11, 31, 63, .9) 100%),
                linear-gradient(90deg, rgba(11, 31, 63, .55) 0%, rgba(11, 31, 63, 0) 60%);
        }
        .sl-hero-inner {
            position: relative;
            z-index: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 44px 56px;
        }
        .sl-brand { display: flex; align-items: center; gap: 20px; }
        .sl-brand img { height: 58px; width: auto; filter: drop-shadow(0 2px 6px rgba(0, 0, 0, .35)); }
        .sl-brand-divider { width: 2px; height: 50px; background: rgba(255, 255, 255, .8); }
        .sl-brand-text { font-size: 28px; font-weight: 600; letter-spacing: .01em; text-shadow: 0 2px 8px rgba(0, 0, 0, .4); }

        .sl-tagline { max-width: 540px; }
        .sl-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            margin-bottom: 16px;
            border: 1px solid rgba(255, 255, 255, .35);
            border-radius: 999px;
            background: rgba(255, 255, 255, .1);
            backdrop-filter: blur(6px);
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .sl-tagline h1 {
            margin: 0;
            font-size: clamp(28px, 3vw, 44px);
            font-weight: 400;
            line-height: 1.2;
            text-shadow: 0 2px 12px rgba(0, 0, 0, .45);
        }
        .sl-tagline h1 b { font-weight: 700; }
        .sl-tagline p { margin: 14px 0 0; font-size: 16px; line-height: 1.5; color: rgba(255, 255, 255, .85); max-width: 440px; }

        /* ── Panel form ── */
        .sl-side {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 56px 24px;
            background:
                radial-gradient(circle at 100% 0%, rgba(47, 111, 209, .08), transparent 45%),
                var(--sl-page);
        }
        .sl-card {
            width: 100%;
            max-width: 400px;
            background: var(--sl-surface);
            border-radius: 16px;
            padding: 36px 32px 28px;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .06), 0 20px 48px rgba(16, 24, 40, .1);
        }
        .sl-card-head { margin-bottom: 24px; }
        .sl-card-icon {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            margin-bottom: 16px;
            border-radius: 12px;
            background: linear-gradient(160deg, var(--sl-blue), var(--sl-blue-dark));
            color: #fff;
            font-size: 20px;
            box-shadow: 0 6px 14px rgba(0, 63, 136, .3);
        }
        .sl-title { margin: 0; font-size: 26px; font-weight: 700; letter-spacing: -.01em; }
        .sl-subtitle { margin: 6px 0 0; font-size: 15px; color: var(--sl-muted); }

        .sl-alert {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 11px 14px;
            margin-bottom: 18px;
            border-radius: 10px;
            background: #fef3f2;
            border: 1px solid #fecdca;
            color: var(--sl-danger);
            font-size: 14px;
            line-height: 1.4;
        }
        .sl-alert .bi { margin-top: 1px; }

        .sl-label { display: block; margin: 0 0 6px 2px; font-size: 13px; font-weight: 600; color: var(--sl-text); }
        .sl-field { position: relative; margin-bottom: 16px; }
        .sl-field > .bi {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            color: #7a8494;
            pointer-events: none;
            transition: color .15s;
        }
        .sl-input {
            width: 100%;
            height: 46px;
            padding: 0 46px 0 42px;
            font: inherit;
            font-size: 15px;
            color: var(--sl-ink);
            background: var(--sl-field);
            border: 1px solid var(--sl-line);
            border-radius: 10px;
            outline: none;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }
        .sl-input::placeholder { color: #9aa3b0; }
        .sl-input:focus { background: #fff; border-color: var(--sl-blue); box-shadow: 0 0 0 4px rgba(0, 63, 136, .12); }
        .sl-field:focus-within > .bi { color: var(--sl-blue); }
        .sl-input.is-invalid { border-color: var(--sl-danger); box-shadow: 0 0 0 4px rgba(180, 35, 24, .1); }
        .sl-eye {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 8px;
            background: none;
            color: #7a8494;
            font-size: 17px;
            cursor: pointer;
        }
        .sl-eye:hover { color: var(--sl-ink); background: #e9edf2; }
        .sl-caps { display: none; margin: -10px 0 14px 2px; font-size: 12.5px; color: #b54708; }
        .sl-caps.show { display: flex; gap: 6px; align-items: center; }

        /* Login sebagai: ATPM / Dealer */
        .sl-role { position: relative; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 22px; }
        .sl-role input { position: absolute; opacity: 0; pointer-events: none; }
        .sl-role label {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 52px;
            margin: 0;
            padding: 8px 12px;
            border: 1px solid var(--sl-line);
            border-radius: 10px;
            background: #fff;
            cursor: pointer;
            transition: border-color .15s, background .15s, box-shadow .15s;
        }
        .sl-role label:hover { border-color: #b3bdca; }
        .sl-role-ico {
            flex: 0 0 32px;
            height: 32px;
            display: grid;
            place-items: center;
            border-radius: 8px;
            background: #eef2f7;
            color: var(--sl-text);
            font-size: 16px;
            transition: background .15s, color .15s;
        }
        .sl-role-txt { display: flex; flex-direction: column; line-height: 1.2; }
        .sl-role-txt b { font-size: 14px; color: var(--sl-ink); }
        .sl-role-txt small { font-size: 12px; color: var(--sl-muted); }
        .sl-role input:checked + label { border-color: var(--sl-blue); background: #f2f6fc; box-shadow: 0 0 0 1px var(--sl-blue) inset; }
        .sl-role input:checked + label .sl-role-ico { background: var(--sl-blue); color: #fff; }
        .sl-role input:focus-visible + label { outline: 2px solid var(--sl-blue); outline-offset: 2px; }

        .sl-btn {
            width: 100%;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 0;
            border-radius: 10px;
            background: linear-gradient(180deg, var(--sl-blue) 0%, var(--sl-blue-dark) 100%);
            color: #fff;
            font: inherit;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: .01em;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(0, 40, 86, .28);
            transition: filter .15s, transform .1s, box-shadow .15s;
        }
        .sl-btn:hover { filter: brightness(1.1); }
        .sl-btn:active { transform: translateY(1px); box-shadow: 0 4px 10px rgba(0, 40, 86, .25); }
        .sl-btn[disabled] { opacity: .85; cursor: progress; }
        .sl-btn .bi { font-size: 18px; transition: transform .15s; }
        .sl-btn:hover .bi { transform: translateX(3px); }
        .sl-eye:focus-visible, .sl-btn:focus-visible { outline: 2px solid var(--sl-sky); outline-offset: 2px; }

        .sl-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, .45);
            border-top-color: #fff;
            border-radius: 50%;
            animation: sl-spin .7s linear infinite;
        }
        @keyframes sl-spin { to { transform: rotate(360deg); } }

        .sl-note {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-top: 20px;
            padding: 12px 14px;
            border-radius: 10px;
            background: #f5f7fa;
            font-size: 13px;
            line-height: 1.45;
            color: var(--sl-text);
        }
        .sl-note .bi { color: var(--sl-blue); font-size: 16px; margin-top: 1px; }

        .sl-footer { margin: 22px 0 0; text-align: center; font-size: 12.5px; color: var(--sl-muted); }

        /* ── Responsive ── */
        @media (max-width: 1200px) {
            .sl { grid-template-columns: minmax(0, 1.1fr) minmax(440px, 1fr); }
            .sl-hero-inner, .sl-side { padding-left: 40px; padding-right: 40px; }
        }

        /* Tablet & HP: foto jadi banner di atas, kartu menimpa bagian bawahnya */
        @media (max-width: 900px) {
            .sl { grid-template-columns: 1fr; grid-template-rows: auto 1fr; }
            .sl-hero { min-height: 320px; background-position: 55% 40%; }
            .sl-hero-inner { justify-content: flex-start; gap: 28px; padding: 28px 28px 110px; }
            .sl-tagline p { display: none; }
            .sl-side { justify-content: flex-start; padding: 0 24px 24px; }
            .sl-card { margin-top: -84px; position: relative; z-index: 2; }
        }

        @media (max-width: 480px) {
            .sl-hero { min-height: 270px; }
            .sl-hero-inner { gap: 22px; padding: max(20px, env(safe-area-inset-top)) 20px 96px; }
            .sl-brand { gap: 14px; }
            .sl-brand img { height: 42px; }
            .sl-brand-divider { height: 36px; }
            .sl-brand-text { font-size: 21px; }
            .sl-eyebrow { font-size: 11.5px; padding: 5px 10px; margin-bottom: 12px; }
            .sl-tagline h1 { font-size: 25px; }
            .sl-side { padding: 0 14px max(20px, env(safe-area-inset-bottom)); }
            .sl-card { padding: 26px 20px 22px; border-radius: 14px; margin-top: -72px; }
            .sl-card-icon { display: none; }
            .sl-card-head { margin-bottom: 20px; }
            .sl-title { font-size: 23px; }
            .sl-subtitle { font-size: 14px; }
            .sl-input { font-size: 16px; } /* cegah auto-zoom iOS */
            .sl-role { gap: 8px; }
            .sl-role label { padding: 8px 10px; gap: 8px; }
            .sl-role-ico { flex-basis: 28px; height: 28px; font-size: 14px; }
        }

        @media (max-width: 340px) {
            .sl-role { grid-template-columns: 1fr; }
        }

        /* Layar desktop yang pendek */
        @media (min-width: 901px) and (max-height: 740px) {
            .sl-side { padding-top: 28px; padding-bottom: 16px; }
            .sl-card { padding: 26px 32px 22px; }
            .sl-card-icon { display: none; }
            .sl-card-head { margin-bottom: 18px; }
            .sl-note { margin-top: 16px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition: none !important; animation-duration: 1.5s !important; }
        }
    </style>
</head>
<body>
<main class="sl">

    {{-- Foto & brand --}}
    <section class="sl-hero" aria-label="Mazda Sales">
        <div class="sl-hero-inner">
            <div class="sl-brand">
                <img src="{{ asset('assets/img/mazda_with_white_26.png') }}" alt="Mazda">
                <span class="sl-brand-divider" aria-hidden="true"></span>
                <span class="sl-brand-text">Sales</span>
            </div>

            <div class="sl-tagline">
                <span class="sl-eyebrow"><i class="bi bi-grid-1x2" aria-hidden="true"></i> WRS Connect</span>
                <h1>Internal tools<br><b>Connect to WRS Sales</b></h1>
                <!-- <p>Akses administrasi, laporan sales person, dan email scheduler dalam satu tempat.</p> -->
            </div>
        </div>
    </section>

    {{-- Login --}}
    <section class="sl-side">
        <div class="sl-card">
            <div class="sl-card-head">
                <div class="sl-card-icon" aria-hidden="true"><i class="bi bi-person-badge"></i></div>
                <h2 class="sl-title">Selamat datang</h2>
                <!-- <p class="sl-subtitle">Masuk ke Mazda Sales System</p> -->
            </div>

            @if ($errors->any())
                <div class="sl-alert" role="alert">
                    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ route('sales.login.auth') }}" method="post" id="login-form" novalidate>
                @csrf

                <label class="sl-label" for="username">Username</label>
                <div class="sl-field">
                    <i class="bi bi-person" aria-hidden="true"></i>
                    <input type="text" class="sl-input @error('username') is-invalid @enderror"
                           name="username" id="username" placeholder="Masukkan username"
                           value="{{ old('username') }}" autocomplete="username"
                           autocapitalize="none" autocorrect="off" spellcheck="false" required
                           @unless(old('username')) autofocus @endunless>
                </div>

                <label class="sl-label" for="password">Password</label>
                <div class="sl-field">
                    <i class="bi bi-lock" aria-hidden="true"></i>
                    <input type="password" class="sl-input @error('password') is-invalid @enderror"
                           name="password" id="password" placeholder="Masukkan password"
                           autocomplete="current-password" required
                           @if(old('username')) autofocus @endif>
                    <button type="button" class="sl-eye" id="toggle-password" aria-label="Tampilkan password" aria-pressed="false">
                        <i class="bi bi-eye-slash" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="sl-caps" id="caps-warning" role="status">
                    <i class="bi bi-capslock-fill" aria-hidden="true"></i> Caps Lock aktif
                </div>

                <span class="sl-label" id="login-as-label">Login sebagai</span>
                @php $loginAs = old('login_as', 'atpm'); @endphp
                <div class="sl-role" role="radiogroup" aria-labelledby="login-as-label">
                    <input type="radio" name="login_as" id="login-as-atpm" value="atpm" {{ $loginAs === 'atpm' ? 'checked' : '' }} required>
                    <label for="login-as-atpm">
                        <span class="sl-role-ico"><i class="bi bi-building" aria-hidden="true"></i></span>
                        <span class="sl-role-txt"><b>ATPM</b><small></small></span>
                    </label>
                    <input type="radio" name="login_as" id="login-as-dealer" value="dealer" {{ $loginAs === 'dealer' ? 'checked' : '' }}>
                    <label for="login-as-dealer">
                        <span class="sl-role-ico"><i class="bi bi-shop" aria-hidden="true"></i></span>
                        <span class="sl-role-txt"><b>DEALER</b><small></small></span>
                    </label>
                </div>

                <button type="submit" class="sl-btn" id="btn-login">
                    <span>Login</span><i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
            </form>

            <div class="sl-note">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                <span>Gunakan akun <b>WRS Sales</b> Anda. Lupa password? Hubungi admin WRS.</span>
            </div>
        </div>

        <p class="sl-footer">&copy; {{ date('Y') }} IT Team Eurokars Group Indonesia</p>
    </section>

</main>

<script>
    (function () {
        var form = document.getElementById('login-form');
        var user = document.getElementById('username');
        var pwd  = document.getElementById('password');
        var eye  = document.getElementById('toggle-password');
        var btn  = document.getElementById('btn-login');
        var caps = document.getElementById('caps-warning');

        eye.addEventListener('click', function () {
            var show = pwd.type === 'password';
            pwd.type = show ? 'text' : 'password';
            eye.setAttribute('aria-pressed', show ? 'true' : 'false');
            eye.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
            eye.innerHTML = '<i class="bi ' + (show ? 'bi-eye' : 'bi-eye-slash') + '" aria-hidden="true"></i>';
            pwd.focus();
        });

        ['keydown', 'keyup'].forEach(function (ev) {
            pwd.addEventListener(ev, function (e) {
                if (e.getModifierState) caps.classList.toggle('show', e.getModifierState('CapsLock'));
            });
        });
        pwd.addEventListener('blur', function () { caps.classList.remove('show'); });

        form.addEventListener('submit', function (e) {
            if (!user.value.trim()) { e.preventDefault(); user.classList.add('is-invalid'); user.focus(); return; }
            if (!pwd.value) { e.preventDefault(); pwd.classList.add('is-invalid'); pwd.focus(); return; }
            btn.disabled = true;
            btn.innerHTML = '<span class="sl-spinner" aria-hidden="true"></span><span>Memproses...</span>';
        });

        [user, pwd].forEach(function (el) {
            el.addEventListener('input', function () { this.classList.remove('is-invalid'); });
        });

        // Tombol back browser setelah submit: aktifkan lagi tombol login
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) { btn.disabled = false; btn.innerHTML = '<span>Login</span><i class="bi bi-arrow-right" aria-hidden="true"></i>'; }
        });
    })();
</script>
</body>
</html>
