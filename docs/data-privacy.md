# Aliran data dan privasi

Dokumen ini menjelaskan perilaku teknis yang terlihat di kode. Ini bukan pengganti pemberitahuan privasi untuk pengguna atau penilaian hukum.

Dokumen sumber OCR disimpan pada private disk. Dokumen dan hasil ekstraksi sensitif dihapus setelah periode `OCR_RETENTION_DAYS` (default 30 hari) melalui scheduler. Pengguna dapat menghapus file bukti lebih awal; tindakan itu juga menghapus teks mentah, hasil parsing, dan confidence OCR. Baris lama tanpa tanggal kedaluwarsa memakai `created_at` untuk pembersihan hasil ekstraksi, sementara file sumbernya tidak diberi tanggal kedaluwarsa yang ditebak.

## Data yang diproses

- Data akun, kategori, transaksi, anggaran, tujuan, dan aset disimpan di database aplikasi.
- Gambar OCR disimpan pada disk privat selama periode retensi; teks mentah, hasil parsing, dan confidence disimpan sementara untuk membantu pemeriksaan draft dan kemudian dihapus. Ringkasan status impor tetap disimpan.
- Webhook WhatsApp menyimpan nomor telepon, isi pesan, status pemrosesan, dan balasan untuk deduplikasi serta retry. Webhook baru tidak lagi menyimpan payload mentah provider; payload yang tersimpan dari versi sebelumnya tetap ada sampai pengelola menetapkan prosedur penghapusannya.
- Parser Gemini dapat menerima teks pesan WhatsApp dan nama akun/kategori milik pengguna saat `DEVFLOW_AI_ENABLED` aktif dan API key tersedia. Pastikan pemberitahuan privasi menjelaskan pemrosesan oleh Google Gemini sebelum fitur ini digunakan oleh pengguna.

## Pengaturan yang harus dikonfirmasi sebelum penggunaan publik

1. Tampilkan pemberitahuan yang mudah ditemukan tentang data yang dikirim ke Evolution API dan, bila diaktifkan, Google Gemini.
2. Tetapkan dan jalankan scheduler untuk retensi hasil OCR. Riwayat pesan WhatsApp belum dihapus otomatis.
3. Tetapkan prosedur penghapusan akun dan ekspor data pengguna.
4. Batasi akses staf ke database dan backup; enkripsi backup dan gunakan HTTPS untuk semua integrasi.
5. Tinjau syarat dan lokasi pemrosesan penyedia eksternal sesuai kebutuhan pengguna dan wilayah operasi.

## Token akses

SPA menggunakan sesi Sanctum dalam cookie HttpOnly dan menghapus bearer token lama dari `localStorage`. Endpoint bearer Sanctum tetap tersedia bagi klien API eksternal; token API berumur 7 hari secara default (`SANCTUM_EXPIRATION=10080` menit), dan deployment dapat memilih masa berlaku yang lebih pendek.

## Siklus berkas OCR

Berkas gambar yang gagal diproses dibersihkan segera. Berkas berhasil diproses tetap pada disk privat sampai dihapus pengguna atau melewati `OCR_RETENTION_DAYS`; penghapusan juga menghapus teks OCR dan hasil parsing. Pastikan scheduler aktif dan direktori privat tidak dipublikasikan oleh web server.
