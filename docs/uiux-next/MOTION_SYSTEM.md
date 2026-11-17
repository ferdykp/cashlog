# Sistem motion

Gerak menyampaikan perubahan dan mengikuti input, bukan menjadi dekorasi. Transisi sheet memakai opacity/transform sekitar 180–260ms; toast memakai opacity/translate sekitar 180ms; meter anggaran/target berubah dengan easing 260ms melalui `transform` agar tidak memicu reflow. Perpindahan route tetap cepat agar tidak menunda pencatatan.

`prefers-reduced-motion: reduce` menghapus transisi non-esensial. Tidak ada animasi berulang atau chart berputar. Fokus pada form transaksi menyembunyikan tab bar ponsel supaya area kerja keyboard lebih lapang; kontrol tetap dapat diakses dari header/back. Modal menahan overscroll dan dapat ditutup dengan tombol Escape melalui directive dialog yang tersedia.

