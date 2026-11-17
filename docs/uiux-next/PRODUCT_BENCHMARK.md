# Benchmark produk Cashlog Next

Riset dilakukan 18 November 2026 melalui dokumentasi resmi produk. Pola diambil sebagai referensi prinsip, bukan tiruan layar.

| Produk | Kekuatan yang terverifikasi | Yang diadaptasi | Yang tidak diadopsi |
|---|---|---|---|
| Copilot Money | Menyatukan pengeluaran, anggaran, target, investasi, kekayaan bersih, dan grafik arus kas; detail transaksi dapat ditelusuri dari ringkasan arus kas. | Ringkasan keuangan berangkat dari angka dan memberi jalur menuju transaksi pendukung. | Integrasi bank dan kecerdasan otomatis yang tidak tersedia di Cashlog. |
| Monarch Money | Dashboard terdiri dari widget yang dapat diurutkan dan disembunyikan; target tabungan terhubung dengan transaksi. | Susunan widget relevan sebagai arah personalisasi produk, serta target perlu tampil dekat dengan arus kas. | Menampilkan kartu untuk semua modul sekaligus; personalisasi dashboard belum didukung API/persistensi Cashlog. |
| YNAB | Pengguna merekam transaksi selagi aktivitas terjadi dan menetapkan pekerjaan uang melalui budget/target. | Aksi Catat tetap terlihat di navigasi dan pengeluaran/pemasukan/transfer dibedakan jelas. | Model zero-based budgeting karena logika budget Cashlog berbeda. |
| Wallet by BudgetBakers | Menawarkan rekening, catatan manual/import, anggaran, grafik, dan laporan dalam satu produk. | Menjaga impor dan pencatatan manual sebagai cara masuk yang setara serta memastikan daftar transaksi bisa difilter. | Bank-sync sebagai asumsi; Cashlog mencatat data yang benar-benar dimasukkan pengguna. |

### Perbandingan pengalaman

- **Premium** lahir dari tipografi yang konsisten, perbedaan hierarki antarangka, ruang yang diprioritaskan, dan detail yang dapat ditelusuri—bukan dari dekorasi kartu atau efek kaca.
- **Navigasi harian** perlu menjaga akses ke beranda, riwayat, aksi catat, dan rencana tanpa menaruh seluruh modul pada satu baris.
- **Transaksi** adalah pekerjaan berulang. Nominal, jenis transaksi, akun, dan umpan balik simpan harus mudah ditemukan; detail sekunder tetap tersedia tanpa menghambat transaksi sederhana.
- **Visualisasi** berguna bila label periode dan nilai terbaca, dan pemilihan periode mengarah ke data transaksi pendukung. Grafik Cashlog saat ini sudah memakai endpoint arus kas dan laporan.
- **Batas penerapan:** fitur prakiraan, sinkronisasi rekening, insight AI, dan widget tersusun personal tidak boleh disimulasikan tanpa data atau dukungan backend.

### Sumber resmi

- [Copilot Money FAQ](https://www.copilot.money/faq), [panduan arus kas](https://help.copilot.money/en/articles/9682232-cash-flow-tab-overview), [panduan transaksi](https://help.copilot.money/en/articles/9554412-transactions-tab-overview)
- [Monarch: kustomisasi dashboard](https://help.monarch.com/hc/en-us/articles/360058127551-Customizing-Your-Dashboard), [memulai dengan Monarch](https://help.monarch.com/hc/en-us/articles/360048393272-Getting-Started-with-Monarch), [target](https://help.monarch.com/hc/en-us/articles/44373110771860-Goals-in-Monarch)
- [YNAB: fitur](https://www.ynab.com/features)
- [Wallet by BudgetBakers: fungsi layanan](https://budgetbakers.com/legal/pdfs/wallet-terms-full.pdf)

