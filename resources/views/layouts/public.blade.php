<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101a2c">
    <meta name="description" content="Ledger membantu Anda mencatat transaksi dan mengelola keuangan pribadi dengan lebih terarah.">
    <title>@yield('title', 'Ledger — Keuangan pribadi, lebih terarah')</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <style>
        :root { color-scheme: light; --ink: #172238; --muted: #596579; --line: #dfe5ed; --paper: #fff; --canvas: #f5f7fb; --brand: #5145cd; --brand-dark: #3930a4; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; color: var(--ink); background: var(--canvas); font: 16px/1.65 Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        a { color: var(--brand-dark); }
        a:focus-visible, button:focus-visible { outline: 3px solid #8f88f2; outline-offset: 3px; }
        .site-header { background: #101a2c; color: #fff; }
        .nav, .container { width: min(1080px, calc(100% - 40px)); margin-inline: auto; }
        .nav { min-height: 76px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .brand { color: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 11px; font-size: 20px; font-weight: 750; letter-spacing: -.03em; }
        .brand img { width: 34px; height: 34px; }
        .nav-links { display: flex; align-items: center; gap: 24px; }
        .nav-links a { color: #dce4f3; text-decoration: none; font-size: 14px; }
        .nav-links a:hover, .footer a:hover { text-decoration: underline; }
        .nav-links .nav-cta { color: #fff; background: var(--brand); padding: 10px 17px; border-radius: 10px; font-weight: 650; }
        .hero { padding: clamp(64px, 10vw, 120px) 0 86px; background: radial-gradient(ellipse at 82% 8%, rgba(126, 115, 255, .22), transparent 34%), #101a2c; color: white; }
        .hero-inner { max-width: 740px; }
        .eyebrow { color: #b7b1ff; text-transform: uppercase; font-size: 12px; font-weight: 750; letter-spacing: .13em; }
        h1, h2, h3 { line-height: 1.2; letter-spacing: -.035em; }
        .hero h1 { max-width: 700px; margin: 18px 0; font-size: clamp(40px, 7vw, 70px); }
        .hero p { max-width: 640px; color: #c6d0e0; font-size: clamp(17px, 2.4vw, 20px); }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; }
        .button { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; padding: 0 20px; border-radius: 10px; text-decoration: none; font-weight: 700; }
        .button-primary { color: #fff; background: var(--brand); }
        .button-primary:hover { background: #6257dc; }
        .button-secondary { border: 1px solid #627089; color: #fff; }
        .features { padding: 64px 0 76px; }
        .section-intro { max-width: 620px; margin-bottom: 26px; }
        .section-intro h2 { margin: 0 0 10px; font-size: clamp(28px, 4vw, 38px); }
        .section-intro p, .feature p { color: var(--muted); }
        .feature-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .feature { padding: 24px; background: var(--paper); border: 1px solid var(--line); border-radius: 16px; }
        .feature h3 { margin: 0 0 8px; font-size: 18px; }
        .feature p { margin: 0; font-size: 14px; }
        .privacy-callout { padding: 24px; border-radius: 16px; background: #eeedff; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; }
        .privacy-callout p { margin: 0; color: #34324f; }
        .footer { padding: 26px 0; background: #101a2c; color: #dce4f3; font-size: 14px; }
        .footer-inner { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 14px; }
        .footer a { color: #dce4f3; }
        .policy { padding: 52px 0 84px; }
        .policy-card { max-width: 850px; padding: clamp(24px, 5vw, 54px); margin: auto; background: var(--paper); border: 1px solid var(--line); border-radius: 20px; box-shadow: 0 18px 60px rgba(29, 42, 66, .06); }
        .policy h1 { margin: 0 0 8px; font-size: clamp(34px, 5vw, 48px); }
        .policy .updated { margin: 0 0 30px; color: var(--muted); font-size: 14px; }
        .policy h2 { margin: 30px 0 10px; font-size: 21px; }
        .policy p, .policy li { color: #3c485b; }
        .policy ul { padding-left: 22px; }
        .policy li + li { margin-top: 7px; }
        .policy .notice { padding: 16px 18px; background: #f2f3ff; border-left: 4px solid var(--brand); border-radius: 8px; }
        @media (max-width: 700px) { .nav, .container { width: min(100% - 32px, 1080px); } .nav { min-height: 68px; } .nav-links { gap: 14px; } .nav-links a { font-size: 13px; } .feature-grid { grid-template-columns: 1fr; } .hero { padding: 70px 0 68px; } .footer-inner { flex-direction: column; } }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
    </style>
</head>
<body>
    <header class="site-header">
        <nav class="nav" aria-label="Navigasi utama">
            <a class="brand" href="/" aria-label="Ledger, beranda">
                <img src="/favicon.svg" alt="">
                <span>Ledger</span>
            </a>
            <div class="nav-links">
                <a href="{{ route('privacy-policy') }}">Kebijakan Privasi</a>
                <a class="nav-cta" href="/login">Masuk</a>
            </div>
        </nav>
    </header>

    @yield('content')

    <footer class="footer">
        <div class="container footer-inner">
            <span>Ledger · Keuangan pribadi, lebih terarah.</span>
            <a href="{{ route('privacy-policy') }}">Kebijakan Privasi</a>
        </div>
    </footer>
</body>
</html>
