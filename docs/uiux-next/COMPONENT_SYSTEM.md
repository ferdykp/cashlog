# Sistem komponen

Navigasi desktop dan ponsel berbagi peta route, tetapi bentuknya adaptif. Sheet Catat berisi empat pilihan yang langsung menuju alur yang benar. Row transaksi, saldo akun, batas belanja, target, dan aset mempunyai struktur semantik masing-masing. `MoneyInput`, `AnimatedMoney`, `ToastContainer`, komponen chart lazy, dan dialog directive dipertahankan agar aturan angka, feedback, dan pemuatan fitur tetap konsisten.

Semua aksi memakai elemen button atau link native. Tombol ikon memiliki nama aksesibel, status terpilih menggunakan `aria-current`/`aria-selected`, progres memiliki nilai numerik dan label, serta skeleton tidak menggantikan pesan status untuk pembaca layar. Pola panel menggunakan garis kontras dan sudut tajam sedang; hanya saldo utama mendapat bidang ink kontras tinggi.

Komponen baru tidak dibuat untuk abstraksi yang hanya dipakai sekali. `Dashboard.vue` memakai data/store yang sudah ada; tidak ada API atau angka contoh baru.

