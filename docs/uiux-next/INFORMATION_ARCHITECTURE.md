# Arsitektur informasi

## Model terpilih

Model C berbasis perilaku Cashlog: navigasi desktop dikelompokkan menurut **Keuangan**, **Rencana**, dan **Lainnya**. Navigasi ponsel memakai **Beranda / Aktivitas / Catat / Rencana / Lainnya**. Tombol Catat membuka bottom sheet pilihan tindakan dan tidak memaksa pengguna pindah ke tab kosong. Model A akan membuat “Create” sebagai destinasi utama yang bukan halaman; model B memisahkan “Money” tanpa batas yang jelas dengan Aktivitas. Model C menjaga pencatatan yang berulang tetap satu sentuhan dan menyediakan ruang tumbuh untuk modul tambahan.

## Peta navigasi

```text
Beranda
├── saldo → Dompet & akun → detail/ubah
├── arus kas → Laporan → filter → transaksi pendukung
├── anggaran → penggunaan → kategori
├── target tabungan → rincian/kontribusi
└── investasi → emas/RDPU → kepemilikan/transaksi

Aktivitas → cari/filter → transaksi → edit/hapus/duplikasi
Catat → pengeluaran | pemasukan | transfer | pindai bukti
Rencana → anggaran | target | investasi | tagihan
Lainnya → workspace bersama | kategori | WhatsApp | pengaturan
```

Autentikasi tetap berada di luar shell. Detail akun saat ini memakai dialog/form dalam halaman akun; `AccountCreate.vue` adalah komponen, bukan route mandiri. `Goal.vue` bukan target route sebenarnya; `/goals` dan `/investasi` menggunakan `FinancialWorkspace.vue` dengan konteks tab berdasarkan path.

## URL dan konteks

Filter aktivitas dan laporan mengikuti query parameter/endpoints yang tersedia; jalur pembuatan transaksi menerima `type` untuk membuka jenis yang diminta. `/history`, `/budgets`, `/reports`, dan `/assets` adalah alias kompatibilitas. Tidak ada pengalih konteks workspace global di shell; akses tetap pada fitur workspace bersama yang sudah tersedia.

