# Deployment satu origin

Aplikasi web dan API sekarang dapat disajikan dari satu host Laravel. Atur `VITE_API_BASE_URL` kosong agar frontend memanggil endpoint relatif `/api`.

## Nginx dan PWA

### MIME type untuk aset Vite

Nginx harus mengirim JavaScript dengan MIME `application/javascript` atau `text/javascript`, dan stylesheet dengan `text/css`. Jika file `.js` dan `.css` dikirim sebagai `application/octet-stream`, browser menolak module JavaScript dan Vue tidak ter-mount sehingga halaman tampak putih.

Pastikan file MIME bawaan Nginx dimuat di dalam blok `http { ... }` pada `nginx.conf`:

```nginx
http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    # server { ... }
}
```

Jangan mendefinisikan ulang `types` di level `server` hanya untuk menambahkan webmanifest. Pada konfigurasi seperti ini, blok `types` mengganti peta bawaan dari `mime.types`, sehingga `.js` dan `.css` jatuh ke `default_type` (seringnya `application/octet-stream`):

```nginx
# Hindari di dalam server {}:
types {
    application/manifest+json webmanifest;
}
```

Hapus blok tersebut dan atur tipe webmanifest hanya pada lokasinya. File JavaScript dan CSS akan memakai peta MIME bawaan:

```nginx
location = /build/manifest.webmanifest {
    default_type application/manifest+json;
    add_header Cache-Control "no-cache, no-store, must-revalidate" always;
    try_files $uri =404;
}
```

Uji konfigurasi lalu muat ulang Nginx:

```sh
sudo nginx -t
sudo systemctl reload nginx
```

Periksa header aset yang dirujuk halaman:

```sh
curl -I https://DOMAIN/build/assets/main-HASH.js
curl -I https://DOMAIN/build/assets/main-HASH.css
```

Pastikan JavaScript membalas `application/javascript` atau `text/javascript`, dan CSS membalas `text/css`. Jika Cloudflare berada di depan server, purge cache untuk URL aset build yang sama setelah memperbaiki origin karena aset Vite memakai cache immutable.

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
