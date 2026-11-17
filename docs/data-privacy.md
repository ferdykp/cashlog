# Aliran data dan privasi

Dokumen ini menjelaskan perilaku teknis yang terlihat di kode. Ini bukan pengganti pemberitahuan privasi untuk pengguna atau penilaian hukum.

Dokumen sumber OCR disimpan pada private disk. Dokumen baru dihapus setelah periode `OCR_RETENTION_DAYS` (default 30 hari) melalui scheduler, sedangkan metadata hasil ekstraksi tetap disimpan. Pengguna dapat menghapus file bukti sendiri tanpa menghapus metadata. Baris lama tanpa tanggal kedaluwarsa tidak dihapus otomatis.

## Data yang diproses

- Data akun, kategori, transaksi, anggaran, tujuan, dan aset disimpan di database aplikasi.
- Gambar OCR disimpan pada disk privat selama dan setelah pemrosesan berhasil; hasil teks dan hasil parsing disimpan sebagai riwayat impor.
- Webhook WhatsApp menyimpan nomor telepon, isi pesan, status pemrosesan, dan balasan untuk deduplikasi serta retry. Webhook baru tidak lagi menyimpan payload mentah provider; payload yang tersimpan dari versi sebelumnya tetap ada sampai pengelola menetapkan prosedur penghapusannya.
- Parser Groq dapat menerima teks pesan WhatsApp dan nama akun/kategori milik pengguna. Integrasi ini nonaktif secara default; pengelola deployment harus mengaktifkannya secara sadar.

## Pengaturan yang harus dikonfirmasi sebelum penggunaan publik

1. Tampilkan pemberitahuan yang mudah ditemukan tentang data yang dikirim ke Evolution API dan, bila diaktifkan, Groq.
2. Tentukan retensi untuk teks pesan WhatsApp dan hasil OCR. Saat ini aplikasi tidak menghapus riwayat tersebut secara otomatis.
3. Tetapkan prosedur penghapusan akun dan ekspor data pengguna.
4. Batasi akses staf ke database dan backup; enkripsi backup dan gunakan HTTPS untuk semua integrasi.
5. Tinjau syarat dan lokasi pemrosesan penyedia eksternal sesuai kebutuhan pengguna dan wilayah operasi.

## Token akses

Token Sanctum disimpan frontend di `localStorage`. Konfigurasi API membatasi masa berlaku token menjadi 30 hari secara default (`SANCTUM_EXPIRATION=43200` menit). Deployment dapat memilih masa berlaku lebih pendek. Karena token tersedia bagi JavaScript halaman, pencegahan XSS tetap penting; migrasi ke autentikasi cookie HttpOnly merupakan pilihan arsitektur lanjutan bila frontend dan API dapat dikelola sebagai SPA first-party Sanctum.

## Siklus berkas OCR

Berkas gambar dibersihkan setelah pemrosesan OCR gagal. Setelah berhasil, berkas sumber disimpan pada disk privat dan belum memiliki penghapusan otomatis. Pastikan direktori privat tidak dipublikasikan oleh web server dan tetapkan retensi yang sesuai.
