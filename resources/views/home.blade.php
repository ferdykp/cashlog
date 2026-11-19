@extends('layouts.public')

@section('title', 'Ledger — Keuangan pribadi, lebih terarah')

@section('content')
<main>
    <section class="hero">
        <div class="container hero-inner">
            <span class="eyebrow">Ruang untuk keuangan Anda</span>
            <h1>Ledger membantu Anda mengelola keuangan dengan lebih tenang.</h1>
            <p>Ledger adalah aplikasi pencatatan keuangan pribadi untuk mencatat transaksi, memantau arus kas, mengatur anggaran, dan menyusun rencana keuangan dalam satu tempat.</p>
            <div class="actions">
                <a class="button button-primary" href="/register">Mulai gunakan Ledger</a>
                <a class="button button-secondary" href="/login">Masuk ke akun</a>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <div class="section-intro">
                <h2>Semua catatan keuangan lebih mudah dipahami.</h2>
                <p>Ledger membantu Anda menyimpan catatan dan melihat gambaran keuangan sehari-hari.</p>
            </div>
            <div class="feature-grid">
                <article class="feature">
                    <h3>Catat pemasukan dan pengeluaran</h3>
                    <p>Kelompokkan transaksi berdasarkan dompet dan kategori, lalu lihat riwayatnya kapan saja.</p>
                </article>
                <article class="feature">
                    <h3>Atur anggaran dan tujuan</h3>
                    <p>Pantau anggaran, tagihan, target menabung, dan aset yang Anda kelola.</p>
                </article>
                <article class="feature">
                    <h3>Pilih cara yang nyaman</h3>
                    <p>Gunakan login Google atau email dan kata sandi. Fitur impor struk dan WhatsApp tersedia secara opsional.</p>
                </article>
            </div>
            <div class="privacy-callout" style="margin-top:24px">
                <p>Pelajari data yang digunakan Ledger dan cara kami menjaganya.</p>
                <a class="button button-primary" href="{{ route('privacy-policy') }}">Baca Kebijakan Privasi</a>
            </div>
            <div class="feature" style="margin-top:16px">
                <h3>Login Google dan privasi</h3>
                <p>Jika Anda memilih Login dengan Google, Ledger menggunakan nama, email, foto profil, dan ID akun Google hanya untuk autentikasi dan pengaitan akun. Ledger tidak mengakses Gmail atau Drive, tidak menggunakan data Google untuk iklan, dan tidak memakai data Google untuk membuat gambar intim tanpa persetujuan. Ledger does not use Google user data or Google APIs to create, generate, or distribute AI-generated non-consensual intimate imagery.</p>
            </div>
        </div>
    </section>
</main>
@endsection
