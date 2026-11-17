# Sistem desain

## Token utama

| Token | Terang | Gelap | Pemakaian |
|---|---|---|---|
| Tinta / `--color-ink-900` | `#F1F5F9` | `#F1F5F9` | judul dan angka utama |
| Latar / `--color-base-50` | `#0B0F17` | `#0B0F17` | kanvas midnight |
| Permukaan / `--glass-surface` | `rgba(21,27,40,.70)` | `rgba(21,27,40,.70)` | panel kaca |
| Garis / `--glass-line` | `rgba(255,255,255,.11)` | `rgba(255,255,255,.11)` | rim dan pemisah |
| Emerald / `--color-primary-600` | `#0DF2A6` | `#0DF2A6` | aksi dan sinyal positif |
| Masuk / `--color-income-600` | `#4AFFB5` | `#4AFFB5` | pemasukan/status positif |
| Keluar / `--color-expense-600` | `#FF8292` | `#FF8292` | pengeluaran/kesalahan |
| Cyan fokus / `--cashlog-cyan` | `#38D9F5` | `#38D9F5` | fokus dan data sekunder |

## Tipografi dan tata letak

Space Grotesk untuk judul display dan saldo utama, Inter untuk isi dan kontrol; rupiah memakai angka tabular. Skala dasar 12/14/16/20/30/48px. Ritme 4/8px; kartu memakai radius 16–24px sesuai peran. Shell mengadaptasi 320–1023px sebagai pengalaman ponsel/tablet sempit dan desktop mulai 1024px. Safe area diterapkan pada header/tab bar.

## Komponen

- Baris transaksi: ikon kategori, deskripsi, tanggal, nominal bertanda semantik.
- Akun: identitas dan saldo milik rekening, tidak memakai gaya target.
- Anggaran: limit, sisa, progres berlabel numerik; warna bukan satu-satunya penanda.
- Target: nama, persentase, kontribusi dan timeline di halaman workspace.
- Aset: tipe dan kuantitas/valuasi sesuai data yang ada.
- Bottom sheet: aksi singkat; halaman khusus untuk edit yang banyak field.
- Form kontrol: label terlihat, angka memakai inputmode, error tetap dekat field.
- Toast: ikon vektor, teks status, tombol tutup berlabel, live region.

Tailwind v4 `@theme` dan token CSS global adalah sumber tema; style spesifik Cashlog diletakkan di `precision.css` yang telah terhubung. Hindari memperkenalkan layer global lain atau warna per halaman.

## Liquid glass

Kanvas midnight memakai cahaya ambient emerald dan cyan; panel, navigasi, sheet, toast, dialog, autentikasi, tabel, form, dan kartu ringkasan memakai permukaan kaca arang tembus pandang dengan blur/saturasi dan garis tepi spekular. Hero saldo memakai tint emerald/cyan yang lebih terang. Aksi utama memakai emerald, data sekunder cyan, dan kehilangan memakai koral. Pola visual dari dua contoh Stitch diterapkan pada seluruh route menggunakan sistem token bersama; isi, nilai, dan alur tetap berasal dari aplikasi Cashlog.

`--glass-surface`, `--glass-raised`, `--glass-line`, dan `--glass-blur` mengatur sistem permukaan. Ketika browser tidak mendukung `backdrop-filter`, atau pengguna meminta pengurangan transparansi, permukaan kembali ke warna solid. Tidak ada informasi yang disampaikan hanya melalui blur, transparansi, atau warna.

