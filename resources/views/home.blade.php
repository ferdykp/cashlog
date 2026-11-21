@extends('layouts.public')

@section('title', 'Ledger — Keuangan pribadi, lebih terarah')

@section('content')
<main>
    <section class="hero">
        <div class="container hero-layout">
            <div class="hero-copy">
                <span class="eyebrow">Ruang untuk keuangan Anda</span>
                <h1>Keuangan lebih jelas, langkah terasa tenang.</h1>
                <p>Catat transaksi, pantau arus kas, dan susun rencana keuangan dalam satu tempat yang mudah dipahami.</p>
                <div class="actions">
                    <a class="button button-primary" href="/register">Mulai gunakan Ledger</a>
                    <a class="button button-secondary" href="/login">Masuk ke akun</a>
                </div>
                <div class="hero-note">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3 5 6v5c0 4.4 2.9 8.1 7 9.4 4.1-1.3 7-5 7-9.4V6l-7-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                        <path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Catatan keuangan Anda, tertata dalam satu ruang pribadi.
                </div>
            </div>

            <div class="preview-wrap" role="group" aria-label="Pratinjau ringkasan keuangan Ledger">
                <div class="preview-card">
                    <div class="preview-top">
                        <div>
                            <p class="preview-title">Ringkasan keuangan</p>
                            <p class="preview-caption">Gambaran bulanan Anda</p>
                        </div>
                        <span class="preview-period">Bulan ini</span>
                    </div>
                    <div class="preview-body">
                        <span class="preview-balance-label">Saldo bersih</span>
                        <strong class="preview-balance">Rp 3.250.000</strong>
                        <span class="preview-change">
                            <svg width="13" height="13" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m3 10 4-4 3 3 3-4M10 5h3v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Arus kas terpantau
                        </span>
                        <svg class="preview-chart" viewBox="0 0 430 112" role="img" aria-label="Grafik ilustrasi arus kas yang stabil">
                            <defs>
                                <linearGradient id="areaFill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#87c8b1" stop-opacity=".32"/><stop offset="1" stop-color="#87c8b1" stop-opacity="0"/></linearGradient>
                            </defs>
                            <path d="M0 91H430M0 57H430M0 23H430" stroke="#e9efea" stroke-width="1" stroke-dasharray="3 5"/>
                            <path d="M0 79C30 76 42 82 68 68S110 71 138 55 180 63 207 46 254 53 282 39 321 49 349 30 389 39 430 15V112H0Z" fill="url(#areaFill)"/>
                            <path d="M0 79C30 76 42 82 68 68S110 71 138 55 180 63 207 46 254 53 282 39 321 49 349 30 389 39 430 15" fill="none" stroke="#08796d" stroke-width="3" stroke-linecap="round"/>
                            <circle cx="430" cy="15" r="4.5" fill="#08796d" stroke="#fff" stroke-width="2"/>
                        </svg>
                        <div class="preview-metrics">
                            <div class="preview-metric">
                                <span class="preview-metric-label"><span class="metric-dot"></span>Pemasukan</span>
                                <strong>Rp 8.400.000</strong>
                            </div>
                            <div class="preview-metric">
                                <span class="preview-metric-label"><span class="metric-dot expense"></span>Pengeluaran</span>
                                <strong>Rp 5.150.000</strong>
                            </div>
                        </div>
                    </div>
                    <div class="preview-foot">Ilustrasi tampilan ringkasan Ledger</div>
                </div>
            </div>
        </div>
    </section>

    <section class="features" aria-labelledby="features-title">
        <div class="container">
            <div class="section-intro">
                <div>
                    <p class="section-kicker">Semua yang penting, dalam satu tempat</p>
                    <h2 id="features-title">Pahami kebiasaan. Rencanakan tujuan.</h2>
                </div>
                <p>Catatan harian menjadi lebih berguna saat Anda bisa melihat gambaran besarnya.</p>
            </div>
            <div class="feature-grid">
                <article class="feature">
                    <span class="feature-icon" aria-hidden="true">
                        <svg width="21" height="21" viewBox="0 0 24 24" fill="none"><path d="M4 5v14h16M8 15l3-3 3 2 5-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <h3>Catat setiap transaksi</h3>
                    <p>Kelompokkan pemasukan dan pengeluaran berdasarkan akun serta kategori.</p>
                </article>
                <article class="feature">
                    <span class="feature-icon" aria-hidden="true">
                        <svg width="21" height="21" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M6 4h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8 12h3m-3 4h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </span>
                    <h3>Atur anggaran dan tagihan</h3>
                    <p>Lihat batas pengeluaran dan jadwal tagihan supaya rencana tetap terjaga.</p>
                </article>
                <article class="feature">
                    <span class="feature-icon" aria-hidden="true">
                        <svg width="21" height="21" viewBox="0 0 24 24" fill="none"><path d="M12 3v18m6-13.5c0-1.4-1.8-2.5-4-2.5H10c-2.2 0-4 1.2-4 2.7S7.8 10.5 10 10.5h4c2.2 0 4 1.2 4 2.7S16.2 16 14 16h-4c-2.2 0-4-1.1-4-2.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <h3>Bangun tujuan finansial</h3>
                    <p>Pantau target menabung, aset, dan progres yang ingin Anda capai.</p>
                </article>
            </div>

            <aside class="privacy-callout" aria-label="Informasi privasi">
                <div class="privacy-copy">
                    <span class="privacy-mark" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 3 5 6v5c0 4.4 2.9 8.1 7 9.4 4.1-1.3 7-5 7-9.4V6l-7-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <div>
                        <h3>Privasi Anda tetap penting</h3>
                        <p>Pelajari data yang digunakan Ledger dan bagaimana data tersebut dikelola.</p>
                    </div>
                </div>
                <a class="button button-secondary" href="{{ route('privacy-policy') }}">Baca kebijakan privasi</a>
            </aside>
        </div>
    </section>
</main>
@endsection
