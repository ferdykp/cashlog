<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101a2c">
    <meta name="description" content="Ledger membantu Anda mencatat transaksi dan mengelola keuangan pribadi dengan lebih terarah.">
    <title>@yield('title', 'Ledger | Keuangan pribadi, lebih terarah')</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --ink: #172e29;
            --muted: #526761;
            --line: #d9e3dc;
            --paper: #fff;
            --canvas: #f4f6f2;
            --brand: #08796d;
            --brand-deep: #075e55;
            --night: #102a25;
            --mint: #a8d7c5;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; min-width: 320px; color: var(--ink); background: var(--canvas); font: 16px/1.65 Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        a { color: var(--brand-deep); }
        a:focus-visible, button:focus-visible { outline: 3px solid #65b8a4; outline-offset: 3px; }
        .site-header { background: var(--night); color: #fff; }
        .nav, .container { width: min(1160px, calc(100% - 48px)); margin-inline: auto; }
        .nav { min-height: 78px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .brand { color: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 11px; font: 700 21px/1 "Space Grotesk", Inter, sans-serif; letter-spacing: -.04em; }
        .brand img { width: 36px; height: 36px; }
        .nav-links { display: flex; align-items: center; gap: 28px; }
        .nav-links a { color: #e0ebe6; text-decoration: none; font-size: 14px; }
        .nav-links a:hover, .footer a:hover { color: #fff; text-decoration: underline; text-underline-offset: 4px; }
        .nav-links .nav-cta { display: inline-flex; min-height: 44px; align-items: center; padding: 0 19px; border: 1px solid rgba(207, 237, 224, .28); border-radius: 10px; color: #fff; background: rgba(255, 255, 255, .07); font-weight: 650; transition: background 180ms ease, border-color 180ms ease; }
        .nav-links .nav-cta:hover { background: rgba(255, 255, 255, .14); border-color: rgba(207, 237, 224, .55); text-decoration: none; }
        .hero { position: relative; overflow: hidden; padding: clamp(64px, 8vw, 108px) 0 clamp(68px, 8vw, 104px); color: #fff; background: radial-gradient(ellipse at 78% 45%, rgba(18, 121, 103, .26), transparent 42%), var(--night); }
        .hero::after { position: absolute; top: 0; right: 0; width: min(44vw, 620px); height: 1px; content: ""; background: linear-gradient(90deg, transparent, rgba(168, 215, 197, .48)); }
        .hero-layout { position: relative; z-index: 1; display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(360px, .85fr); align-items: center; gap: clamp(40px, 7vw, 92px); }
        .hero-copy { max-width: 650px; }
        .eyebrow { display: inline-flex; align-items: center; gap: 9px; color: var(--mint); font-size: 13px; font-weight: 650; letter-spacing: .035em; }
        .eyebrow::before { width: 22px; height: 1px; content: ""; background: currentColor; }
        h1, h2, h3 { font-family: "Space Grotesk", Inter, sans-serif; line-height: 1.15; letter-spacing: -.045em; }
        .hero h1 { max-width: 650px; margin: 22px 0 18px; font-size: clamp(42px, 5.4vw, 70px); font-weight: 600; }
        .hero-copy > p { max-width: 560px; margin: 0; color: #c4d2cd; font-size: clamp(16px, 1.7vw, 19px); line-height: 1.7; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; }
        .button { display: inline-flex; align-items: center; justify-content: center; min-height: 52px; padding: 0 22px; border: 1px solid transparent; border-radius: 10px; text-decoration: none; font-size: 15px; font-weight: 700; transition: transform 180ms ease, background 180ms ease, border-color 180ms ease; }
        .button:hover { transform: translateY(-1px); }
        .button-primary { color: #fff; background: var(--brand); box-shadow: 0 8px 24px rgba(0, 0, 0, .16); }
        .button-primary:hover { color: #fff; background: #0a8b7c; }
        .button-secondary { border-color: rgba(220, 237, 229, .3); color: #f3f8f5; background: rgba(255, 255, 255, .035); }
        .button-secondary:hover { color: #fff; border-color: rgba(220, 237, 229, .65); background: rgba(255, 255, 255, .08); }
        .hero-note { display: flex; align-items: center; gap: 10px; margin-top: 26px; color: #aabbb4; font-size: 13px; }
        .hero-note svg { flex: 0 0 auto; color: var(--mint); }
        .preview-wrap { position: relative; padding: 12px; border: 1px solid rgba(198, 231, 216, .18); border-radius: 22px; background: linear-gradient(145deg, rgba(255, 255, 255, .13), rgba(255, 255, 255, .035)); box-shadow: 0 28px 75px rgba(0, 0, 0, .24); }
        .preview-card { overflow: hidden; border: 1px solid rgba(18, 44, 37, .09); border-radius: 15px; color: var(--ink); background: #fbfcfa; }
        .preview-top { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 19px 21px 16px; border-bottom: 1px solid #e8eee9; }
        .preview-title { margin: 0; font: 600 15px/1.3 Inter, sans-serif; letter-spacing: -.02em; }
        .preview-caption { margin: 4px 0 0; color: #74847d; font-size: 11px; }
        .preview-period { padding: 6px 9px; border: 1px solid #e1e9e3; border-radius: 7px; color: #687a72; font-size: 10px; white-space: nowrap; }
        .preview-body { padding: 20px 21px 21px; }
        .preview-balance-label { color: #687a72; font-size: 11px; }
        .preview-balance { display: block; margin-top: 4px; font: 600 clamp(27px, 3vw, 36px)/1.2 "Space Grotesk", sans-serif; letter-spacing: -.055em; }
        .preview-change { display: inline-flex; align-items: center; gap: 5px; margin-top: 7px; color: #16805f; font-size: 11px; font-weight: 600; }
        .preview-chart { display: block; width: 100%; height: auto; margin: 22px 0 18px; }
        .preview-metrics { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .preview-metric { padding: 13px 14px; border: 1px solid #e6ece7; border-radius: 10px; background: #fff; }
        .preview-metric-label { display: flex; align-items: center; gap: 7px; color: #6b7b74; font-size: 11px; }
        .metric-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--brand); }
        .metric-dot.expense { background: #d58b73; }
        .preview-metric strong { display: block; margin-top: 7px; font: 600 17px/1.2 "Space Grotesk", sans-serif; letter-spacing: -.035em; }
        .preview-foot { padding: 10px 21px; border-top: 1px solid #e8eee9; color: #819087; font-size: 10px; }
        .features { padding: clamp(62px, 8vw, 94px) 0; }
        .section-intro { display: flex; align-items: end; justify-content: space-between; gap: 28px; margin-bottom: 30px; }
        .section-intro > div { max-width: 650px; }
        .section-kicker { margin: 0 0 9px; color: var(--brand-deep); font-size: 12px; font-weight: 700; letter-spacing: .045em; }
        .section-intro h2 { margin: 0; font-size: clamp(29px, 4vw, 42px); font-weight: 600; }
        .section-intro > p { max-width: 340px; margin: 0 0 3px; color: var(--muted); font-size: 14px; }
        .feature-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .feature { min-height: 206px; padding: 25px; border: 1px solid var(--line); border-radius: 15px; background: var(--paper); box-shadow: 0 7px 22px rgba(28, 59, 48, .035); }
        .feature-icon { display: grid; width: 42px; height: 42px; place-items: center; margin-bottom: 22px; border: 1px solid #d6e9e0; border-radius: 12px; color: var(--brand-deep); background: #edf5ef; }
        .feature h3 { margin: 0 0 9px; font-size: 19px; font-weight: 600; letter-spacing: -.03em; }
        .feature p { margin: 0; color: var(--muted); font-size: 14px; line-height: 1.65; }
        .privacy-callout { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-top: 22px; padding: 24px 26px; border: 1px solid #d8e7de; border-radius: 15px; background: #eaf3ed; }
        .privacy-copy { display: flex; align-items: flex-start; gap: 14px; }
        .privacy-mark { display: grid; flex: 0 0 40px; width: 40px; height: 40px; place-items: center; border-radius: 11px; color: var(--brand-deep); background: #d7e9df; }
        .privacy-copy h3 { margin: 0 0 4px; font-size: 16px; font-weight: 600; letter-spacing: -.02em; }
        .privacy-copy p { max-width: 590px; margin: 0; color: #526761; font-size: 13px; line-height: 1.55; }
        .privacy-callout .button { flex: 0 0 auto; min-height: 44px; padding-inline: 16px; font-size: 13px; }
        .privacy-callout .button-secondary { border-color: #b8d1c4; color: var(--brand-deep); background: rgba(255, 255, 255, .58); }
        .privacy-callout .button-secondary:hover { border-color: var(--brand); color: var(--brand-deep); background: #fff; }
        .footer { padding: 25px 0; color: #c6d7cf; background: var(--night); font-size: 13px; }
        .footer-inner { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 14px; }
        .footer a { color: #e0ebe6; }
        .policy { padding: 52px 0 84px; }
        .policy-card { max-width: 850px; padding: clamp(24px, 5vw, 54px); margin: auto; background: var(--paper); border: 1px solid var(--line); border-radius: 20px; box-shadow: 0 18px 60px rgba(29, 42, 66, .06); }
        .policy h1 { margin: 0 0 8px; font-size: clamp(34px, 5vw, 48px); }
        .policy .updated { margin: 0 0 30px; color: var(--muted); font-size: 14px; }
        .policy h2 { margin: 30px 0 10px; font-size: 21px; }
        .policy p, .policy li { color: #3c524b; }
        .policy ul { padding-left: 22px; }
        .policy li + li { margin-top: 7px; }
        .policy .notice { padding: 16px 18px; border-left: 4px solid var(--brand); border-radius: 8px; background: #eaf3ed; }
        .site-header { color: var(--ink); background: var(--paper); border-bottom: 1px solid var(--line); }
        .brand { color: var(--ink); }
        .nav-links a { color: var(--muted); }
        .nav-links a:hover, .footer a:hover { color: var(--brand-deep); }
        .nav-links .nav-cta { border-color: var(--line); color: var(--brand-deep); background: var(--paper); }
        .nav-links .nav-cta:hover { border-color: var(--brand); background: #f2f7f3; }
        .hero { background: var(--night); }
        .hero::after { display: none; }
        .button-primary { color: var(--brand-deep); background: #fff; box-shadow: none; }
        .button-primary:hover { color: var(--brand-deep); background: #eaf3ed; }
        .preview-wrap { padding: 0; border: 0; border-radius: 15px; background: transparent; box-shadow: none; }
        .preview-card { box-shadow: 0 3px 12px rgba(16, 42, 37, .12); }
        .footer { color: var(--muted); background: var(--canvas); border-top: 1px solid var(--line); }
        .footer a { color: var(--brand-deep); }
        @media (max-width: 900px) {
            .hero-layout { grid-template-columns: minmax(0, 1fr) minmax(310px, .85fr); gap: 34px; }
            .hero h1 { font-size: clamp(40px, 6vw, 58px); }
            .preview-wrap { padding: 9px; }
        }
        @media (max-width: 820px) {
            .hero-layout { grid-template-columns: 1fr; gap: 38px; }
            .preview-wrap { width: min(100%, 520px); margin-inline: auto; }
        }
        @media (max-width: 700px) {
            .nav, .container { width: min(100% - 36px, 1160px); }
            .nav { min-height: 68px; }
            .brand { font-size: 19px; }
            .brand img { width: 33px; height: 33px; }
            .nav-links { gap: 13px; }
            .nav-links a { font-size: 12px; }
            .nav-links .nav-cta { min-height: 42px; padding-inline: 14px; }
            .hero { padding: 58px 0 64px; }
            .hero h1 { max-width: 570px; margin-top: 17px; font-size: clamp(40px, 10vw, 56px); }
            .hero-copy > p { font-size: 16px; }
            .actions { margin-top: 24px; }
            .button { min-height: 50px; }
            .preview-balance { font-size: 32px; }
            .features { padding: 60px 0; }
            .section-intro { display: block; margin-bottom: 23px; }
            .section-intro > p { margin-top: 12px; }
            .feature-grid { grid-template-columns: 1fr; gap: 12px; }
            .feature { min-height: 0; padding: 21px; }
            .feature-icon { margin-bottom: 16px; }
            .privacy-callout { align-items: flex-start; flex-direction: column; padding: 21px; }
            .privacy-callout .button { width: 100%; }
            .footer-inner { flex-direction: column; }
        }
        @media (max-width: 380px) {
            .nav, .container { width: min(100% - 30px, 1160px); }
            .nav-links { gap: 9px; }
            .nav-links a { font-size: 11px; }
            .nav-links .nav-cta { padding-inline: 11px; }
            .hero h1 { font-size: 39px; }
            .actions { flex-direction: column; }
            .actions .button { width: 100%; }
            .preview-top, .preview-body { padding-inline: 15px; }
            .preview-metrics { gap: 8px; }
            .preview-metric { padding: 11px; }
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { transition-duration: .01ms !important; }
        }
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
