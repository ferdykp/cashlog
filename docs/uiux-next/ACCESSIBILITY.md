# Aksesibilitas

Target WCAG 2.2 AA untuk kontras teks normal minimal 4.5:1 dan kontrol fokus terlihat. Skip link sudah ada pada shell. Fokus form memakai `:focus-visible`; label terlihat dipakai untuk field, password manager/autofill diizinkan, paste tidak dicegah. Kontrol angka memakai inputmode sesuai konteks dan format Rupiah menggunakan `Intl` helper.

Navigasi memakai link, aksi memakai tombol; menu sheet menyediakan jalur tap dan keyboard. Ikon dekoratif disembunyikan dari pohon aksesibilitas atau ditemani teks; tombol close toast sekarang memiliki label dan notifikasi memakai `aria-live`. Indikator budget/target menyertakan peran progressbar dan nilai aria. Grafik arus kas memberi ringkasan daftar bulan yang dapat dibaca pembaca layar, dan nilainya ikut tersamarkan saat nominal disembunyikan. Warna masuk/keluar disertai label/arah dan tanda plus/minus.

CSS menghormati reduced motion. Perlu verifikasi manual screen reader, zoom/ukuran teks, keyboard, landscape, dan kontras kedua tema pada browser sebelum klaim konformansi penuh.

