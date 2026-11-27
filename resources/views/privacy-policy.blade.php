@extends('layouts.public')

@section('title', 'Kebijakan Privasi | Ledger')

@section('content')
<main class="policy">
    <article class="policy-card">
        <span class="eyebrow">Privasi dan data</span>
        <h1>Kebijakan Privasi Ledger</h1>
        <p class="updated">Terakhir diperbarui: 26 November 2026</p>

        <p>Kebijakan ini menjelaskan data yang diproses saat Anda menggunakan Ledger, alasan pemrosesannya, serta pilihan yang tersedia bagi Anda. <strong>Ledger adalah aplikasi pencatatan keuangan pribadi</strong> untuk mengelola transaksi, dompet, anggaran, tagihan, tujuan, dan aset.</p>

        <h2>1. Informasi yang kami proses</h2>
        <ul>
            <li><strong>Informasi akun:</strong> nama, alamat email, dan kata sandi jika Anda mendaftar manual. Kata sandi disimpan dalam bentuk hash, bukan sebagai teks yang dapat dibaca.</li>
            <li><strong>Informasi Google:</strong> jika Anda memilih login Google, Ledger menerima ID akun Google yang stabil, nama, alamat email, dan foto profil yang tersedia. Jika Anda secara terpisah menghubungkan Gmail untuk impor transaksi, Ledger menggunakan izin baca Gmail untuk memeriksa metadata email baru, mengenali notifikasi dari bank yang didukung, lalu membaca isi email yang cocok untuk menyiapkan transaksi.</li>
            <li><strong>Integrasi Gmail:</strong> Ledger menyimpan alamat Gmail yang terhubung, token akses yang dienkripsi, ID pesan Gmail untuk deduplikasi, serta hasil ekstraksi terbatas seperti bank, nominal, tanggal, dan arah transaksi. Ledger tidak meminta atau menyimpan nomor rekening maupun empat digit terakhirnya. Isi email lengkap dan lampiran tidak disimpan. Transaksi yang sudah dicatat tetap menjadi bagian dari riwayat keuangan meskipun koneksi email diputus; koneksi dan hasil impor yang belum diproses dihapus saat koneksi diputus.</li>
            <li><strong>Kode OTP:</strong> kode sekali pakai dikirim ke email untuk menyelesaikan verifikasi pertama atau pengaitan akun Google. Kode berlaku 10 menit, dibatasi percobaannya, dan disimpan sebagai hash untuk pemeriksaan.</li>
            <li><strong>Data keuangan yang Anda masukkan:</strong> catatan transaksi, dompet, kategori, anggaran, tagihan, tujuan, aset, dan informasi yang Anda pilih untuk dibagikan melalui ruang kerja bersama.</li>
            <li><strong>Berkas impor:</strong> jika Anda mengimpor struk, berkas sumber dan hasil ekstraksi OCR seperti teks, nominal, tanggal, atau nama merchant diproses untuk menyiapkan draft. Hasil OCR sensitif disimpan sementara, lalu dihapus bersama berkas setelah masa retensi layanan (default 30 hari bila penjadwal aktif). Anda juga dapat menghapus berkas sumber lebih awal; tindakan itu menghapus hasil OCR sensitifnya, sementara status ringkas impor tetap disimpan.</li>
            <li><strong>Informasi teknis:</strong> sesi login, token autentikasi sesuai metode login, alamat IP, jenis peramban, dan waktu aktivitas dapat diproses untuk menjalankan layanan, menjaga keamanan, dan menangani gangguan.</li>
        </ul>

        <h2>2. Cara kami menggunakan informasi Google</h2>
        <p>Informasi Google Sign-In digunakan untuk autentikasi dan pengaitan akun. Koneksi Gmail merupakan fitur berbeda dan opsional; email Gmail yang dihubungkan boleh berbeda dari email login Ledger. Untuk fitur ini, Ledger memeriksa metadata email baru dan hanya mengambil isi email dari domain pengirim bank yang telah dimasukkan ke daftar yang diizinkan Ledger untuk mencari detail transaksi. Isi email yang diambil diproses untuk ekstraksi dan tidak disimpan sebagai arsip. Semua hasil menunggu pemeriksaan dan konfirmasi pengguna sebelum menjadi transaksi.</p>
        <p>Informasi Gmail hanya digunakan untuk menyiapkan atau mencatat transaksi, mencegah duplikasi, dan menampilkan status sinkronisasi. Informasi itu tidak digunakan untuk iklan, dijual, atau dikirim ke penyedia AI/WhatsApp. Akses dapat dicabut dari Ledger atau dari pengaturan keamanan akun Google. Token dan hasil impor yang belum diproses dihapus dari Ledger saat koneksi dilepas; transaksi yang sudah dicatat tetap ada sampai Anda menghapusnya dari Ledger.</p>
        <p class="notice">Penggunaan informasi yang diterima melalui Google API mengikuti <a href="https://developers.google.com/terms/api-services-user-data-policy">Google API Services User Data Policy</a>, termasuk persyaratan Limited Use. Data Google hanya digunakan untuk fitur yang dijelaskan di halaman ini.</p>

        <h2>3. Fitur opsional dan penyedia layanan</h2>
        <ul>
            <li><strong>Pengiriman email:</strong> penyedia email yang dikonfigurasi Ledger memproses alamat tujuan dan isi email yang diperlukan untuk mengirim OTP atau tautan pemulihan kata sandi.</li>
            <li><strong>WhatsApp:</strong> jika Anda menghubungkan WhatsApp, nomor telepon dan pesan transaksi yang Anda kirim dapat diproses melalui penyedia integrasi WhatsApp yang digunakan Ledger, lalu disimpan untuk menjalankan fitur, konfirmasi transaksi, dan menangani pengiriman ulang.</li>
            <li><strong>Gmail:</strong> Google memproses autentikasi OAuth dan pemberitahuan perubahan kotak masuk. Ledger menggunakan Google Gmail API untuk mengambil metadata dan isi pesan yang diperlukan untuk ekstraksi notifikasi transaksi. Isi pesan lengkap tidak disimpan setelah diproses.</li>
            <li><strong>Pemrosesan AI:</strong> jika pengelola layanan mengaktifkan fitur AI dan fitur tersebut digunakan, teks pesan transaksi serta nama dompet atau kategori yang relevan dapat dikirim ke penyedia AI untuk membantu membaca transaksi. Fitur ini dapat dinonaktifkan dan tidak diperlukan untuk menggunakan pencatatan manual.</li>
            <li><strong>Ruang kerja bersama:</strong> informasi yang Anda bagikan di ruang kerja dapat dilihat oleh anggota yang Anda undang atau setujui.</li>
        </ul>
        <p>Penyedia tersebut memproses data yang diperlukan untuk memberikan fungsi terkait. Ledger tidak menjual data pribadi atau data keuangan pengguna.</p>

        <h2>4. Penyimpanan dan keamanan</h2>
        <p>Data akun dan catatan disimpan pada sistem Ledger. Kami menerapkan kontrol akses dan perlindungan yang sesuai dengan fungsi layanan, termasuk hash untuk kata sandi dan kode OTP serta enkripsi token Gmail yang disimpan. Isi email diproses sementara dan tidak disimpan sebagai arsip. Tidak ada metode penyimpanan atau pengiriman data yang dapat dijamin sepenuhnya bebas risiko.</p>
        <p>Data akun dan catatan disimpan selama diperlukan untuk menyediakan layanan. Jika Anda ingin meminta akses, perbaikan, atau penghapusan data, hubungi kami melalui alamat di bawah ini. Kami dapat meminta verifikasi bahwa permintaan berasal dari pemilik akun dan menyimpan informasi tertentu bila diperlukan untuk keamanan atau kewajiban yang berlaku.</p>

        <h2>5. Pilihan Anda</h2>
        <ul>
            <li>Anda dapat menggunakan pendaftaran email dan kata sandi tanpa menghubungkan Google.</li>
            <li>Pengaitan Google bersifat opsional dan dapat dimulai dari Pengaturan setelah Anda masuk ke akun Ledger.</li>
            <li>Koneksi Gmail bersifat opsional, dapat menggunakan alamat yang berbeda dari email login, dan dapat diputus dari Pengaturan. Anda juga dapat mencabut izin Ledger dari pengaturan keamanan akun Google.</li>
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
