# Cashlog

Aplikasi pencatatan keuangan pribadi dengan Laravel dan Vue 3 dalam satu proyek. Laravel menyediakan API dan menyajikan SPA Vue melalui Vite.

## Struktur

- `app/`, `routes/`, `database/`: backend Laravel dan API.
- `resources/js/`: aplikasi Vue, router, stores, komponen, dan PWA service worker.
- `resources/views/app.blade.php`: entry point HTML yang memuat aplikasi Vue.
- `public/`: front controller Laravel serta ikon dan aset publik PWA.

## Menjalankan secara lokal

1. Instal dependensi PHP dan JavaScript: `composer install` dan `npm install`.
2. Salin `.env.example` ke `.env`, lalu atur database, `APP_URL`, integrasi opsional, dan API URL bila API tidak berada pada origin yang sama.
3. Jalankan `php artisan key:generate` dan `php artisan migrate`.
4. Jalankan `composer run dev` untuk server Laravel, Vite, queue, dan scheduler lokal.

Frontend memakai URL relatif `/api` secara default, sehingga Laravel frontend dan API dapat disajikan dari origin yang sama. Atur `VITE_API_BASE_URL` hanya bila API berada pada domain berbeda.

## Integrasi opsional

- OCR: PHP GD, Tesseract, dan data bahasa Inggris diperlukan untuk membaca gambar struk. Atur `TESSERACT_BINARY` dan `OCR_TIMEOUT` di `.env`.
- WhatsApp: atur URL, API key, instance, dan secret webhook Evolution API.
- Parser Groq nonaktif secara default. Sebelum mengaktifkannya, beri tahu pengguna bahwa teks pesan WhatsApp dan nama akun/kategori akan dikirim ke Groq.

## Deployment

Gunakan HTTPS. Simpan `.env`, database, dan berkas unggahan di penyimpanan persisten. Jalankan `php artisan migrate --force` saat merilis migrasi; jangan gunakan `migrate:fresh` atau `db:wipe` di production. Jalankan scheduler setiap menit untuk pembayaran tagihan otomatis. Untuk konfigurasi Nginx PWA dan header service worker, lihat `docs/deployment-monolith.md`.

## Dokumentasi

- [Privasi dan retensi](docs/data-privacy.md)
- [Catatan arsitektur dan batasan](docs/project-review.md)
- [OCR](docs/ocr.md)
- [WhatsApp](docs/whatsapp.md)
- [Arsitektur finansial](docs/financial-ecosystem-architecture.md)
