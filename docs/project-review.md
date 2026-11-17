# Catatan arsitektur dan prioritas

Cashlog sudah meliputi lebih dari MVP pencatatan transaksi: selain akun, transaksi, anggaran, dan laporan, ada tujuan, aset, tagihan otomatis, OCR, WhatsApp, dan ruang keuangan bersama. Perluasan ini menunjukkan potensi produk, tetapi menambah beban pemeliharaan dan menuntut fokus pada alur inti.

## Kekuatan yang dipertahankan

- Perubahan saldo transaksi dilakukan dalam transaksi database dan mengunci akun yang terdampak.
- Endpoint auth dibatasi lajunya; operasi transaksi mendukung idempotensi.
- Tes alur finansial dan regresi tersedia pada API serta utilitas frontend.
- Layar memuat empty state, indikator proses, dan label aksesibilitas pada banyak kontrol.

## Prioritas lanjutan

1. Tinjau autentikasi cookie HttpOnly Sanctum untuk SPA first-party, atau pertahankan bearer token dengan masa berlaku pendek dan kebijakan pencabutan yang jelas.
2. Sediakan pemberitahuan dan kontrol pengguna untuk layanan pemrosesan eksternal. Groq saat ini nonaktif sampai pengelola mengaktifkan `GROQ_ENABLED`.
3. Tetapkan retensi dan penghapusan untuk riwayat OCR, teks WhatsApp, dan akun pengguna.
4. Pecah layar besar berdasarkan domain. Logika pemuatan detail tujuan dan pagination kontribusi kini ada di `ledger-web/src/composables/useGoalDetails.js`; pemisahan bagian lain bisa mengikuti pola ini.
5. Sederhanakan navigasi dan validasi alur transaksi mobile sebelum menambah fitur finansial baru.
6. Lakukan audit aksesibilitas keyboard/kontras dan uji alur browser nyata sebelum rilis publik.

Catatan ini bersifat teknis dan statis; hasilnya bukan sertifikasi keamanan atau aksesibilitas.
