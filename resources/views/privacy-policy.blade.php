@extends('layouts.public')

@section('title', 'Kebijakan Privasi — Ledger')

@section('content')
<main class="policy">
    <article class="policy-card">
        <span class="eyebrow">Privasi dan data</span>
        <h1>Kebijakan Privasi Ledger</h1>
        <p class="updated">Terakhir diperbarui: 19 November 2026</p>

        <p>Kebijakan ini menjelaskan data yang diproses saat Anda menggunakan Ledger, alasan pemrosesannya, serta pilihan yang tersedia bagi Anda. <strong>Ledger adalah aplikasi pencatatan keuangan pribadi</strong> untuk mengelola transaksi, dompet, anggaran, tagihan, tujuan, dan aset.</p>

        <h2>1. Informasi yang kami proses</h2>
        <ul>
            <li><strong>Informasi akun:</strong> nama, alamat email, dan kata sandi jika Anda mendaftar manual. Kata sandi disimpan dalam bentuk hash, bukan sebagai teks yang dapat dibaca.</li>
            <li><strong>Informasi Google:</strong> jika Anda memilih login atau menghubungkan akun Google, Ledger menerima ID akun Google yang stabil, nama, alamat email, dan foto profil yang tersedia. Kami menggunakan izin dasar <code>openid</code>, <code>email</code>, dan <code>profile</code> untuk autentikasi dan pengaitan akun. Ledger tidak meminta akses ke isi Gmail, Google Drive, kontak, atau berkas Google Anda.</li>
            <li><strong>Kode OTP:</strong> kode sekali pakai dikirim ke email untuk menyelesaikan verifikasi pertama atau pengaitan akun Google. Kode berlaku 10 menit, dibatasi percobaannya, dan disimpan sebagai hash untuk pemeriksaan.</li>
            <li><strong>Data keuangan yang Anda masukkan:</strong> catatan transaksi, dompet, kategori, anggaran, tagihan, tujuan, aset, dan informasi yang Anda pilih untuk dibagikan melalui ruang kerja bersama.</li>
            <li><strong>Berkas impor:</strong> jika Anda mengimpor struk, berkas sumber dan hasil ekstraksi OCR seperti teks, nominal, tanggal, atau nama merchant dapat diproses dan disimpan bersama riwayat impor. Anda dapat menghapus berkas sumber melalui aplikasi. Masa retensi otomatis berkas baru mengikuti konfigurasi layanan (default 30 hari bila penjadwal pembersihan aktif); hasil ekstraksi dan metadata impor tidak otomatis terhapus bersama berkasnya.</li>
            <li><strong>Informasi teknis:</strong> sesi login, token autentikasi sesuai metode login, alamat IP, jenis peramban, dan waktu aktivitas dapat diproses untuk menjalankan layanan, menjaga keamanan, dan menangani gangguan.</li>
        </ul>

        <h2>2. Cara kami menggunakan informasi Google</h2>
        <p>Ledger menerima ID akun Google yang stabil, nama, alamat email, dan foto profil untuk mengenali akun, membuat akun Ledger setelah OTP diverifikasi, membantu login berikutnya, dan menghubungkan Google ke akun Ledger yang sudah ada. Pengaitan hanya berhasil jika email Google terverifikasi sama dengan email akun Ledger. Data tersebut disimpan pada sistem Ledger selama akun digunakan dan hanya diproses untuk autentikasi, pengaitan akun, keamanan, serta dukungan terkait akun.</p>
        <p>Data Google tidak dikirim ke penyedia AI atau WhatsApp Ledger, tidak dijual, tidak digunakan untuk iklan, dan tidak digunakan untuk membuat gambar atau konten intim tanpa persetujuan. Ledger tidak menggunakan Google APIs atau data Google untuk membuat maupun memfasilitasi AI-generated non-consensual intimate imagery. Ledger does not use Google user data or Google APIs to create, generate, or distribute AI-generated non-consensual intimate imagery. Kami tidak membaca Gmail, Drive, kontak, atau isi layanan Google lainnya.</p>
        <p class="notice">Penggunaan informasi yang diterima melalui Google Sign-In mengikuti <a href="https://developers.google.com/terms/api-services-user-data-policy">Google API Services User Data Policy</a>, termasuk persyaratan Limited Use. Data Google hanya dipakai untuk fitur autentikasi dan pengaitan akun yang terlihat di Ledger.</p>

        <h2>3. Fitur opsional dan penyedia layanan</h2>
        <ul>
            <li><strong>Pengiriman email:</strong> penyedia email yang dikonfigurasi Ledger memproses alamat tujuan dan isi email yang diperlukan untuk mengirim OTP atau tautan pemulihan kata sandi.</li>
            <li><strong>WhatsApp:</strong> jika Anda menghubungkan WhatsApp, nomor telepon dan pesan transaksi yang Anda kirim dapat diproses melalui penyedia integrasi WhatsApp yang digunakan Ledger, lalu disimpan untuk menjalankan fitur, konfirmasi transaksi, dan menangani pengiriman ulang.</li>
            <li><strong>Pemrosesan AI:</strong> jika pengelola layanan mengaktifkan fitur AI dan fitur tersebut digunakan, teks pesan transaksi serta nama dompet atau kategori yang relevan dapat dikirim ke penyedia AI untuk membantu membaca transaksi. Fitur ini dapat dinonaktifkan dan tidak diperlukan untuk menggunakan pencatatan manual.</li>
            <li><strong>Ruang kerja bersama:</strong> informasi yang Anda bagikan di ruang kerja dapat dilihat oleh anggota yang Anda undang atau setujui.</li>
        </ul>
        <p>Penyedia tersebut memproses data yang diperlukan untuk memberikan fungsi terkait. Ledger tidak menjual data pribadi atau data keuangan pengguna.</p>

        <h2>4. Penyimpanan dan keamanan</h2>
        <p>Data akun dan catatan disimpan pada sistem Ledger. Kami menerapkan kontrol akses dan perlindungan yang sesuai dengan fungsi layanan, termasuk hash untuk kata sandi dan kode OTP. Tidak ada metode penyimpanan atau pengiriman data yang dapat dijamin sepenuhnya bebas risiko.</p>
        <p>Data akun dan catatan disimpan selama diperlukan untuk menyediakan layanan. Jika Anda ingin meminta akses, perbaikan, atau penghapusan data, hubungi kami melalui alamat di bawah ini. Kami dapat meminta verifikasi bahwa permintaan berasal dari pemilik akun dan menyimpan informasi tertentu bila diperlukan untuk keamanan atau kewajiban yang berlaku.</p>

        <h2>5. Pilihan Anda</h2>
        <ul>
            <li>Anda dapat menggunakan pendaftaran email dan kata sandi tanpa menghubungkan Google.</li>
            <li>Pengaitan Google bersifat opsional dan dapat dimulai dari Pengaturan setelah Anda masuk ke akun Ledger.</li>
            <li>Anda dapat memutuskan koneksi WhatsApp dan menghapus berkas sumber impor dari fitur yang tersedia.</li>
            <li>Anda dapat meminta akses, koreksi, atau penghapusan data dengan menghubungi dukungan Ledger.</li>
            <li>Anda dapat mencabut izin aplikasi Ledger dari pengaturan keamanan akun Google. Pencabutan izin Google tidak otomatis menghapus catatan Ledger; hubungi dukungan jika Anda juga ingin meminta penghapusan akun atau datanya.</li>
        </ul>

        <h2>6. Perubahan kebijakan</h2>
        <p>Kami dapat memperbarui kebijakan ini jika fitur atau cara pemrosesan data berubah. Versi terbaru akan dipublikasikan di halaman ini beserta tanggal pembaruannya.</p>

        <h2>7. Kontak</h2>
        <p>Untuk pertanyaan atau permintaan terkait privasi, hubungi <a href="mailto:kpferdy@gmail.com">kpferdy@gmail.com</a>.</p>
    </article>
</main>
@endsection
