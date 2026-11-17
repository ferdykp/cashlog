# Deployment satu origin

Aplikasi web dan API sekarang dapat disajikan dari satu host Laravel. Atur `VITE_API_BASE_URL` kosong agar frontend memanggil endpoint relatif `/api`.

## Nginx dan PWA

Vite membangun service worker ke `public/build/sw.js`, tetapi service worker perlu cakupan root. Tambahkan header berikut pada lokasi yang melayani file itu di konfigurasi Nginx, lalu reload Nginx setelah validasi konfigurasi:

```nginx
location = /build/sw.js {
    add_header Service-Worker-Allowed "/" always;
    try_files $uri =404;
}
```

Pertahankan root document Nginx ke direktori `public/` Laravel dan pastikan request aplikasi selain file statis diteruskan ke `public/index.php`.

## Data production

Saat memindahkan deployment, arahkan `.env` ke database production yang sama dan pertahankan storage unggahan. Buat backup database dan storage terlebih dahulu. Deploy ke direktori baru, verifikasi, lalu alihkan konfigurasi web server; simpan direktori lama sampai versi baru stabil.
