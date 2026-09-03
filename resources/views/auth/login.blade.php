<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#174ea6">
    <title>Masuk — Sistem Arsip Surat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --blue-900: #103b74;
            --blue-800: #174f95;
            --blue-700: #1d63b7;
            --blue-100: #e8f2ff;
            --blue-50: #f4f8fd;
            --ink: #1e293b;
            --muted: #64748b;
            --line: #d8e1ec;
            --danger: #b42318;
        }

        * { box-sizing: border-box; }

        html { min-width: 320px; height: 100%; background: var(--blue-50); }

        body {
            margin: 0;
            height: 100%;
            min-height: 100vh;
            overflow: hidden;
            color: var(--ink);
            background: var(--blue-50);
            font-family: Arial, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        button, input { font: inherit; }

        .page {
            position: relative;
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(440px, .92fr) minmax(520px, 1.08fr);
            background:
                linear-gradient(90deg, rgba(7, 34, 68, .68) 0%, rgba(9, 45, 87, .44) 48%, rgba(6, 29, 58, .62) 100%),
                url("{{ asset('images/gedung-login.webp') }}") center / cover no-repeat fixed;
            isolation: isolate;
        }

        .sky-effects {
            position: absolute;
            z-index: 0;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .twinkle-star,
        .shooting-star {
            position: absolute;
            display: block;
            border-radius: 50%;
            background: #fff;
            pointer-events: none;
        }

        .twinkle-star {
            width: var(--star-size, 2px);
            height: var(--star-size, 2px);
            opacity: .2;
            box-shadow: 0 0 7px rgba(255, 255, 255, .86);
            animation: star-twinkle var(--twinkle-duration, 2.8s) ease-in-out infinite;
            animation-delay: var(--twinkle-delay, 0s);
        }

        .shooting-star {
            top: var(--star-top, -8%);
            left: var(--star-left, 110%);
            width: 3px;
            height: 3px;
            opacity: 0;
            box-shadow: 0 0 9px #fff, 0 0 18px rgba(191, 222, 255, .92), 0 0 28px rgba(111, 179, 255, .66);
            animation: shooting-star-fall var(--fall-duration, 4s) linear infinite;
            animation-delay: var(--fall-delay, 0s);
        }

        .shooting-star::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 100%;
            width: var(--trail-length, 52px);
            height: 1px;
            background: linear-gradient(90deg, rgba(255, 255, 255, .96), transparent);
            transform: translateY(-50%);
            transform-origin: left center;
        }

        @keyframes star-twinkle {
            0%, 100% { opacity: .16; transform: scale(.75); }
            48% { opacity: .92; transform: scale(1.28); }
            62% { opacity: .48; transform: scale(1); }
        }

        @keyframes shooting-star-fall {
            0% { opacity: 0; transform: translate3d(0, 0, 0) rotate(-45deg); }
            8% { opacity: 1; }
            72% { opacity: .9; }
            100% { opacity: 0; transform: translate3d(-620px, 620px, 0) rotate(-45deg); }
        }

        .login-panel {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 100vh;
            padding: clamp(18px, 2.4vw, 36px);
            overflow: hidden;
            background: transparent;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 16px;
            width: 100%;
            padding: 0 0 18px;
            border: 0;
            border-bottom: 1px solid #dce7f4;
            border-radius: 0;
            color: var(--blue-900);
            background: transparent;
            box-shadow: none;
            text-decoration: none;
        }

        .brand-logos {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-logos::after {
            content: "";
            width: 1px;
            height: 42px;
            margin-left: 2px;
            background: var(--line);
        }

        .logo-pemkab {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }

        .logo-bapenda {
            width: 126px;
            height: 48px;
            object-fit: contain;
        }

        .brand-text { display: grid; gap: 2px; }
        .brand-title { font-size: 17px; font-weight: 800; letter-spacing: -.01em; }
        .brand-subtitle { color: var(--muted); font-size: 12px; }

        .form-wrap {
            position: relative;
            z-index: 1;
            width: min(100%, 510px);
            margin: 0 auto;
            padding: clamp(24px, 2.2vw, 32px) clamp(28px, 2.5vw, 38px) 20px;
            border: 1px solid #dce7f4;
            border-radius: 22px;
            background: rgba(248, 251, 255, .96);
            box-shadow: 0 22px 55px rgba(24, 66, 116, .11), 0 3px 10px rgba(24, 66, 116, .05);
        }

        .welcome-icon {
            display: grid;
            place-items: center;
            width: 45px;
            height: 45px;
            margin: 19px 0 13px;
            border-radius: 15px;
            color: var(--blue-700);
            background: var(--blue-100);
        }

        .welcome-icon svg { width: 24px; height: 24px; }

        h1 {
            margin: 0;
            color: #16243a;
            font-size: clamp(32px, 3.3vw, 43px);
            line-height: 1.12;
            letter-spacing: -.04em;
        }

        .intro {
            max-width: 440px;
            margin: 10px 0 21px;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.65;
        }

        .alert {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-bottom: 20px;
            padding: 13px 15px;
            border: 1px solid #f4b9b4;
            border-radius: 10px;
            color: var(--danger);
            background: #fff4f2;
            font-size: 14px;
            line-height: 1.45;
        }

        .field { margin-bottom: 16px; }

        label {
            display: block;
            margin-bottom: 8px;
            color: #26364d;
            font-size: 15px;
            font-weight: 700;
        }

        .hint {
            display: block;
            margin: 7px 2px 0;
            color: #7a8798;
            font-size: 12px;
            line-height: 1.4;
        }

        .input-shell { position: relative; }

        .input-shell > svg {
            position: absolute;
            top: 50%;
            left: 17px;
            width: 21px;
            height: 21px;
            color: #718096;
            pointer-events: none;
            transform: translateY(-50%);
        }

        input[type="email"], input[type="password"], input[type="text"] {
            width: 100%;
            height: 54px;
            padding: 0 54px 0 51px;
            border: 1.5px solid var(--line);
            border-radius: 11px;
            outline: none;
            color: var(--ink);
            background: #fff;
            font-size: 16px;
            transition: border-color .18s, box-shadow .18s;
        }

        input:hover { border-color: #aabbd0; }
        input:focus { border-color: var(--blue-700); box-shadow: 0 0 0 4px rgba(29, 99, 183, .13); }
        input::placeholder { color: #98a4b3; }
        input[aria-invalid="true"] { border-color: #c85252; box-shadow: 0 0 0 4px rgba(200, 82, 82, .1); }
        input[aria-invalid="true"]:focus { border-color: #b63d3d; box-shadow: 0 0 0 4px rgba(182, 61, 61, .15); }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 9px;
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            padding: 0;
            border: 0;
            border-radius: 8px;
            color: #607087;
            background: transparent;
            cursor: pointer;
            transform: translateY(-50%);
        }

        .toggle-password:hover, .toggle-password:focus-visible { color: var(--blue-800); background: var(--blue-100); outline: 3px solid rgba(29, 99, 183, .13); outline-offset: 1px; }
        .toggle-password svg { width: 21px; height: 21px; }

        .error {
            display: block;
            margin-top: 7px;
            color: var(--danger);
            font-size: 13px;
            font-weight: 600;
        }

        .options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 0 0 18px;
        }

        .remember {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin: 0;
            color: #46566c;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .remember input { width: 19px; height: 19px; accent-color: var(--blue-700); }

        .help-link {
            color: var(--blue-700);
            font-size: 14px;
            font-weight: 700;
            text-underline-offset: 3px;
        }

        .submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 11px;
            width: 100%;
            height: 55px;
            border: 0;
            border-radius: 11px;
            color: #fff;
            background: var(--blue-700);
            box-shadow: 0 9px 20px rgba(29, 99, 183, .2);
            cursor: pointer;
            font-size: 16px;
            font-weight: 750;
            transition: background .18s, transform .18s, box-shadow .18s;
        }

        .submit:hover:not(:disabled) { background: var(--blue-800); box-shadow: 0 11px 24px rgba(23, 79, 149, .25); transform: translateY(-1px); }
        .submit:focus-visible { outline: 4px solid rgba(29, 99, 183, .2); outline-offset: 2px; }
        .submit:active:not(:disabled) { transform: translateY(0) scale(.99); }
        .submit svg { width: 20px; height: 20px; }

        .safe-note {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            margin: 14px 0 0;
            color: #7a8798;
            font-size: 12px;
        }

        .safe-note svg { width: 15px; height: 15px; }

        .panel-foot {
            position: relative;
            z-index: 1;
            width: 100%;
            margin-top: 17px;
            padding: 16px 0 0;
            border-top: 1px solid #dce7f4;
            border-radius: 0;
            color: #718096;
            background: transparent;
            text-align: center;
            font-size: 12px;
            line-height: 1.6;
        }

        .visual {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            overflow: hidden;
            color: #fff;
            background: transparent;
            isolation: isolate;
        }

        .visual::before {
            content: "";
            position: absolute;
            z-index: 1;
            inset: 0;
            background: linear-gradient(180deg, transparent 32%, rgba(5, 31, 62, .54) 100%);
        }

        .building {
            display: none;
        }

        .visual-top {
            position: absolute;
            z-index: 2;
            top: clamp(28px, 4vw, 55px);
            right: clamp(28px, 4vw, 55px);
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 10px 14px;
            border: 1px solid rgba(255,255,255,.34);
            border-radius: 8px;
            background: rgba(15, 58, 109, .5);
            backdrop-filter: blur(10px);
            font-size: 13px;
            font-weight: 600;
        }

        .status-dot { width: 8px; height: 8px; border-radius: 50%; background: #8ee6a8; box-shadow: 0 0 0 4px rgba(142,230,168,.16); }

        .visual-copy {
            position: absolute;
            z-index: 2;
            right: clamp(35px, 6vw, 90px);
            bottom: clamp(45px, 8vw, 100px);
            left: clamp(35px, 6vw, 90px);
            max-width: 660px;
        }

        .visual-icon {
            display: grid;
            place-items: center;
            width: 59px;
            height: 59px;
            margin-bottom: 22px;
            border: 1px solid rgba(255,255,255,.3);
            border-radius: 14px;
            background: rgba(255,255,255,.16);
            backdrop-filter: blur(8px);
        }

        .visual-icon svg { width: 29px; height: 29px; }

        .visual-copy h2 {
            max-width: 640px;
            margin: 0;
            font-size: clamp(36px, 4.7vw, 66px);
            line-height: 1.08;
            letter-spacing: -.045em;
        }

        .visual-copy p {
            max-width: 550px;
            margin: 20px 0 0;
            color: rgba(255,255,255,.84);
            font-size: 16px;
            line-height: 1.65;
        }

        @media (max-width: 930px) {
            .page { grid-template-columns: 1fr; }
            .login-panel { min-height: auto; }
            .visual { min-height: 390px; grid-row: 1; }
            .visual-copy { bottom: 42px; }
            .visual-copy p { margin-top: 12px; }
            .visual-icon { display: none; }
            body { overflow: auto; }
            .form-wrap { margin: 24px auto; }
        }

        @media (max-width: 560px) {
            .visual { min-height: 310px; }
            .visual-top { top: 18px; right: 18px; }
            .visual-copy { right: 24px; bottom: 26px; left: 24px; }
            .visual-copy h2 { font-size: 33px; }
            .visual-copy p { display: none; }
            .login-panel { padding: 28px 22px 32px; }
            .brand { align-items: flex-start; flex-direction: column; gap: 10px; }
            .brand-logos::after { display: none; }
            .logo-pemkab { width: 43px; height: 43px; }
            .logo-bapenda { width: 115px; height: 43px; }
            .form-wrap { margin: 20px auto; padding: 24px 20px 18px; border-radius: 18px; }
            h1 { font-size: 36px; }
            .intro { font-size: 15px; }
            .options { align-items: flex-start; flex-direction: column; gap: 13px; }
            .panel-foot { text-align: center; }
        }

        @media (prefers-reduced-motion: reduce) {
            .twinkle-star { animation: none; opacity: .42; }
            .shooting-star { display: none; }
        }

    </style>
</head>
<body>
    <x-toast />
    <main class="page">
        <div id="stars-container" class="sky-effects" aria-hidden="true"></div>

        <section class="login-panel" aria-labelledby="login-title">
            <div class="form-wrap">
                <a class="brand" href="{{ route('login') }}" aria-label="Sistem Arsip Surat Bapenda Kabupaten Jember">
                    <span class="brand-logos" aria-hidden="true">
                        <img class="logo-pemkab" src="{{ asset('images/logo-pemkab-jember.png') }}" alt="">
                        <img class="logo-bapenda" src="{{ asset('images/logo-bapenda-jember.png') }}" alt="">
                    </span>
                    <span class="brand-text">
                        <span class="brand-title">Sistem Arsip Surat</span>
                        <span class="brand-subtitle">Bidang Penetapan dan Keberatan</span>
                    </span>
                </a>

                <span class="welcome-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 10h.01M12 10h.01M16 10h.01M4 5h16v11H9l-5 4z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <h1 id="login-title">Halo Kakak Comel</h1>
                <p class="intro">Silakan masuk untuk mencatat, mengarsipkan atau melihat perkembangan surat yang sedang diproses.</p>

                <form method="POST" action="{{ route('login.store') }}" data-loading-form>
                    @csrf
                    <div class="field">
                        <label for="email">Alamat email</label>
                        <div class="input-shell">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16v12H4z" stroke-linejoin="round"/><path d="m4 7 8 6 8-6" stroke-linejoin="round"/></svg>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="Contoh: nama@kantor.go.id" autocomplete="email" required autofocus @if($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
                        </div>
                        <span class="hint">Masukkan email yang sudah didaftarkan oleh admin.</span>
                        @error('email')<span class="error" id="email-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="field">
                        <label for="password">Kata sandi</label>
                        <div class="input-shell">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                            <input id="password" name="password" type="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required @if($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
                            <button class="toggle-password" type="button" aria-label="Tampilkan kata sandi" aria-pressed="false">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                            </button>
                        </div>
                        <span class="hint">Tekan ikon mata di sebelah kanan untuk melihat kata sandi.</span>
                        @error('password')<span class="error" id="password-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="options">
                        <label class="remember" for="remember">
                            <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))>
                            Ingat saya di perangkat ini
                        </label>
                        <a class="help-link" href="mailto:admin@example.com?subject=Bantuan%20masuk%20Sistem%20Arsip%20Surat">Lupa kata sandi?</a>
                    </div>

                    <button class="submit" type="submit" data-loading-label="Memproses...">
                        Masuk ke Sistem Arsip
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M14 7l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>

                    <p class="safe-note">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 5 6v5c0 4.6 2.8 8 7 10 4.2-2 7-5.4 7-10V6z"/><path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Data Kakak tersimpan dan dilindungi dengan aman.
                    </p>
                </form>

                <div class="panel-foot">&copy; {{ date('Y') }} Sistem Arsip Surat &middot; Membantu pekerjaan surat-menyurat lebih tertata.</div>
            </div>
        </section>

        <aside class="visual" aria-label="Gedung kantor">
            <img class="building" src="{{ asset('images/gedung-login.webp') }}" alt="Gedung kantor">
            <div class="visual-top"><span class="status-dot"></span> Sistem siap digunakan</div>
            <div class="visual-copy">
                <span class="visual-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7.5h6l2 2H20v10H4z" stroke-linejoin="round"/><path d="M7.5 14h9M7.5 17h6" stroke-linecap="round"/></svg>
                </span>
                <h2>Bidang Penetapan dan Keberatan.</h2>
                <p>Sistem ini memudahkan kepala bidang mengawasi efisiensi kerja, mulai dari status masuknya surat, rekan tim yang memproses, hingga capaian akhir pengerjaannya.</p>
            </div>
        </aside>
    </main>

    <script>
        const toggle = document.querySelector('.toggle-password');
        const password = document.querySelector('#password');

        toggle.addEventListener('click', () => {
            const isVisible = password.type === 'text';
            password.type = isVisible ? 'password' : 'text';
            toggle.setAttribute('aria-pressed', String(!isVisible));
            toggle.setAttribute('aria-label', isVisible ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi');
        });

        const starsContainer = document.querySelector('#stars-container');

        if (starsContainer) {
            const compactScreen = window.matchMedia('(max-width: 560px)').matches;
            const twinkleCount = compactScreen ? 24 : 46;
            const shootingStarCount = compactScreen ? 7 : 13;

            for (let index = 0; index < twinkleCount; index += 1) {
                const star = document.createElement('span');
                const size = (Math.random() * 1.8 + 1).toFixed(2);

                star.className = 'twinkle-star';
                star.style.left = `${Math.random() * 100}%`;
                star.style.top = `${Math.random() * 100}%`;
                star.style.setProperty('--star-size', `${size}px`);
                star.style.setProperty('--twinkle-duration', `${(Math.random() * 2.6 + 1.8).toFixed(2)}s`);
                star.style.setProperty('--twinkle-delay', `${(Math.random() * -4).toFixed(2)}s`);
                starsContainer.appendChild(star);
            }

            for (let index = 0; index < shootingStarCount; index += 1) {
                const star = document.createElement('span');

                star.className = 'shooting-star';
                star.style.setProperty('--star-left', `${Math.floor(Math.random() * 105 + 35)}%`);
                star.style.setProperty('--star-top', `${Math.floor(Math.random() * -50)}%`);
                star.style.setProperty('--fall-duration', `${(Math.random() * 3 + 3.4).toFixed(2)}s`);
                star.style.setProperty('--fall-delay', `${(Math.random() * 7).toFixed(2)}s`);
                star.style.setProperty('--trail-length', `${Math.floor(Math.random() * 42 + 38)}px`);
                starsContainer.appendChild(star);
            }
        }
    </script>
</body>
</html>
