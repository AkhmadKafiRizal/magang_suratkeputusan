<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#103b74">
    <title>@yield('title') | {{ config('branding.short_name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { display: grid; min-height: 100vh; margin: 0; place-items: center; padding: 24px; color: #1e293b; background: #eef4fb; font-family: Arial, "Segoe UI", sans-serif; }
        .error-card { width: min(100%, 650px); overflow: hidden; border: 1px solid #d6e3f1; border-radius: 22px; background: #fff; box-shadow: 0 22px 55px rgba(24, 66, 116, .12); }
        .error-brand { display: flex; align-items: center; gap: 16px; padding: 24px 28px; border-bottom: 1px solid #deebf8; background: #f8fbff; }
        .error-logos { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
        .error-logos img:first-child { width: 42px; height: 42px; object-fit: contain; }
        .error-logos img:last-child { width: 116px; height: 42px; object-fit: contain; }
        .error-brand-copy { display: grid; min-width: 0; gap: 3px; padding-left: 16px; border-left: 1px solid #d8e1ec; }
        .error-brand-copy strong { color: #103b74; font-size: 19px; letter-spacing: .04em; }
        .error-brand-copy span { color: #405b79; font-size: 12px; font-weight: 700; line-height: 1.4; }
        .error-brand-copy small { color: #64748b; font-size: 11px; line-height: 1.4; }
        .error-content { padding: 42px 38px 40px; text-align: center; }
        .error-code { display: inline-flex; margin-bottom: 18px; padding: 7px 13px; border-radius: 999px; color: #174f95; background: #e8f2ff; font-size: 12px; font-weight: 800; letter-spacing: .08em; }
        h1 { margin: 0; color: #16243a; font-size: clamp(30px, 6vw, 42px); letter-spacing: -.035em; }
        p { max-width: 500px; margin: 14px auto 27px; color: #5c6b80; font-size: 16px; line-height: 1.65; }
        a { display: inline-flex; min-height: 46px; align-items: center; justify-content: center; padding: 11px 19px; border-radius: 10px; color: #fff; background: #1d63b7; font-weight: 700; text-decoration: none; box-shadow: 0 9px 20px rgba(29, 99, 183, .2); }
        a:hover, a:focus-visible { background: #174f95; outline: 3px solid rgba(29, 99, 183, .16); }
        .error-footer { padding: 16px 24px; color: #718096; background: #f8fbff; text-align: center; font-size: 12px; }
        @media (max-width: 600px) { .error-brand { align-items: flex-start; flex-direction: column; } .error-brand-copy { padding: 0; border: 0; } .error-content { padding: 34px 22px; } }
    </style>
</head>
<body>
    <main class="error-card">
        <header class="error-brand">
            <span class="error-logos" aria-hidden="true">
                <img src="{{ asset('images/logo-pemkab-jember.png') }}" alt="">
                <img src="{{ asset('images/logo-bapenda-jember.png') }}" alt="">
            </span>
            <span class="error-brand-copy">
                <strong>{{ config('branding.short_name') }}</strong>
                <span>{{ config('branding.full_name') }}</span>
                <small>{{ config('branding.agency') }}</small>
            </span>
        </header>
        <section class="error-content">
            <span class="error-code">@yield('code')</span>
            <h1>@yield('heading')</h1>
            <p>@yield('message')</p>
            <a href="{{ url('/dashboard') }}">Kembali ke Dashboard {{ config('branding.short_name') }}</a>
        </section>
        <footer class="error-footer">&copy; {{ date('Y') }} {{ config('branding.short_name') }} &middot; {{ config('branding.agency') }}</footer>
    </main>
</body>
</html>
