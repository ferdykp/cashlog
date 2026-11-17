# Alur utama

| Alur | Urutan dan validasi | Selesai / pemulihan |
|---|---|---|
| Pengeluaran | Catat → Pengeluaran → nominal, kategori, akun, tanggal, catatan opsional. Akun/kategori mengikuti data tersedia; nominal harus lolos validasi server. | Simpan menampilkan notifikasi, menjaga idempotensi, lalu kembali ke aktivitas; gagal mempertahankan form dan menampilkan kesalahan. |
| Pemasukan | Catat → Pemasukan → nominal, kategori pemasukan, akun, tanggal. | Tersimpan sebagai pemasukan, tidak diperlakukan sebagai belanja. |
| Transfer | Catat → Transfer → akun sumber, akun tujuan berbeda, nominal, tanggal/catatan opsional. | Ditampilkan sebagai perpindahan internal, bukan pendapatan/pengeluaran. Kesalahan relasi akun disampaikan dekat field. |
| Akun | Dompet & akun → tambah / menu akun → ubah atau hapus → konfirmasi. | Saldo serta aksi mengikuti store/API; penolakan backend mempertahankan dialog. |
| Anggaran | Anggaran → periode/kategori/limit → simpan → tinjau terpakai/tersisa dan kategori. | Ringkasan diperbarui; jika belum ada data tampilkan ajakan membuat anggaran, bukan angka contoh. |
| Target | Target → buat nama/tanggal dan item opsional → simpan → detail. | Syarat rinci progresif; kontribusi menampilkan nominal, tanggal, dan catatan; sumber dana mengikuti aturan API. |
| Aset | Investasi → pilih emas/RDPU → tambah kepemilikan atau transaksi → tinjau nilai/riwayat. | Harga, biaya, kuantitas, status, dan pembulatan tetap memakai logika domain yang ada. Transaksi pending tidak ditampilkan sebagai aset selesai. |
| Tagihan | Tagihan → tambah/edit nama, jumlah, tenggat, frekuensi, reminder; auto bayar menyebut akun dan dampaknya. | Tandai lunas berbeda dari pencatatan transaksi; kegagalan menyimpan isi form. Tidak ada klaim debit bank eksternal. |
| OCR | Pindai → unggah/capture → tunggu ekstraksi → koreksi field → konfirmasi. | Data hasil OCR tidak langsung menjadi transaksi sebelum konfirmasi. Error unggah/proses menyediakan coba lagi; sumber dokumen mengikuti masa simpan yang berlaku. |
| Workspace bersama | Kolaborasi → pilih ruang → lihat anggota, kontribusi/aset/target → lakukan aksi bila berwenang. | API otorisasi menjadi sumber keputusan; kegagalan akses tidak ditampilkan sebagai data kosong. |
| Laporan | Beranda/Laporan → periode atau rentang → ringkasan/tren → buka transaksi pendukung. | Query berlaku pada URL bila tersedia; pembatalan request lama menjaga filter terbaru. |

Komponen formulir memakai loading, inline error, dan notifikasi konsisten. Modal dapat ditutup lewat tombol/batal dan menahan scroll latar. Perubahan finansial disegarkan melalui event/store invalidation yang telah tersedia.

