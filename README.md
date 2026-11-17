# Cashlog

Cashlog adalah aplikasi pencatatan keuangan pribadi berbasis Vue 3 dan Laravel. Aplikasi mencakup akun, transaksi dan transfer, anggaran, tagihan, tujuan keuangan, aset, impor struk, integrasi WhatsApp, serta ruang keuangan bersama.

## Struktur

- `ledger-web/`: Vue 3, Vite, Pinia, Vue Router, dan ApexCharts.
- `ledger-api/`: Laravel API, Sanctum, database, scheduler, OCR, dan integrasi layanan eksternal.
- Dokumen kebutuhan dan keputusan produk berada di berkas Markdown pada direktori ini.

## Menjalankan secara lokal

### API

1. Masuk ke `ledger-api/`, salin `.env.example` menjadi `.env`, lalu atur koneksi database dan `APP_URL`.
2. Jalankan `composer install`.
3. Jalankan `php artisan key:generate` dan `php artisan migrate`.
4. Jalankan `php artisan serve`.

Token API berlaku 30 hari secara default. Atur `SANCTUM_EXPIRATION` dalam menit untuk mengubahnya.

### Web

1. Masuk ke `ledger-web/` dan jalankan `npm install`.
2. Atur `VITE_API_BASE_URL` ke alamat API, misalnya `http://127.0.0.1:8000`.
3. Jalankan `npm run dev`.

## Integrasi opsional

- **OCR:** PHP GD, Tesseract, dan data bahasa Inggris diperlukan untuk membaca gambar struk. `TESSERACT_BINARY` dan `OCR_TIMEOUT` dapat diatur di `.env`.
- **WhatsApp:** atur URL, API key, instance, dan secret webhook Evolution API. Endpoint webhook harus hanya dapat dijangkau melalui HTTPS di deployment publik.
- **Parser Groq:** secara default nonaktif. Sebelum mengaktifkannya, beri tahu pengguna bahwa teks pesan WhatsApp dan nama akun/kategori akan dikirim ke Groq. Atur `GROQ_ENABLED=true`, `GROQ_API_KEY`, dan opsional `GROQ_MODEL`.

## Deployment

- Gunakan HTTPS dan domain frontend yang terdaftar di `ledger-api/config/cors.php`.
- Simpan secret di environment deployment, bukan di repositori.
- Jalankan `php artisan migrate --force` saat merilis migrasi.
- Jalankan `php artisan schedule:run` setiap menit melalui cron agar pembayaran otomatis tagihan diproses. Perintah dijadwalkan pukul 00:05 Asia/Jakarta.
- Untuk beberapa instance API, gunakan cache bersama seperti Redis atau database agar lock WhatsApp berlaku lintas instance.
- Atur filesystem privat, backup database dan berkas impor, serta kebijakan retensi berdasarkan [catatan aliran data dan privasi](docs/data-privacy.md).

## Pemeriksaan kualitas

Frontend: `cd ledger-web && npm test` dan `npm run build`.

Backend: `cd ledger-api && php artisan test`.

Lihat juga [catatan arsitektur dan batasan saat ini](docs/project-review.md).
