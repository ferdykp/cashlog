# Inventaris layar aktual

Route diambil dari `resources/js/router/index.js`; semua route aplikasi mengikuti guard auth yang berlaku. Status “sistem diterapkan” berarti token/kontras/kontrol global berlaku dan alur fitur lama tetap ada; “struktur baru” menandakan komposisi khusus yang ditulis ulang pada tugas ini.

| Route | Nama / komponen | Fokus desain dan status |
|---|---|---|
| `/login` | login / `Login.vue` | autentikasi, autofill; sistem visual diterapkan |
| `/register` | register / `Register.vue` | pendaftaran dan validasi; sistem diterapkan |
| `/forgot-password` | forgot-password / `ForgotPassword.vue` | pemulihan; sistem diterapkan |
| `/reset-password` | reset-password / `ResetPassword.vue` | ubah kredensial; sistem diterapkan |
| `/dashboard` | dashboard / `Dashboard.vue` | saldo, briefing aktual, aksi, anggaran, target, aset, cashflow, aktivitas; struktur baru |
| `/accounts` | accounts / `Account.vue` | daftar, saldo, CRUD akun; sistem diterapkan |
| `/transactions` | transactions / `TransactionHistory.vue` | pencarian, filter, feed; sistem diterapkan |
| `/transactions/create` | transactions.create / `TransactionCreate.vue` | expense/income/transfer; sistem diterapkan |
| `/transactions/:id/edit` | transactions.edit / `TransactionCreate.vue` | koreksi transaksi; sistem diterapkan |
| `/scan` | scan / `SmartImport.vue` | capture, review, konfirmasi OCR; sistem diterapkan |
| `/bills` | bills / `Bills.vue` | jadwal, status, pembayaran; sistem diterapkan |
| `/settings/whatsapp` | settings.whatsapp / `WhatsAppIntegration.vue` | koneksi integrasi; sistem diterapkan |
| `/categories` | categories.index / `Category.vue` | kategori; sistem diterapkan |
| `/budget` | budget.index / `Budget.vue` | rencana dan pemakaian budget; sistem diterapkan |
| `/goals` | goals.index / `FinancialWorkspace.vue` | sasaran, pendanaan, kontribusi; sistem diterapkan |
| `/investasi` | assets.index / `FinancialWorkspace.vue` | emas/RDPU, posisi dan transaksi; sistem diterapkan |
| `/kolaborasi` | collaboration.index / `SharedWorkspace.vue` | konteks dan akses bersama; sistem diterapkan |
| `/report` | report.index / `Report.vue` | filter, tren, rekap; sistem diterapkan |
| `/settings` | settings.index / `Settings.vue` | profil dan preferensi; sistem diterapkan |
| `/history` | redirect `/transactions` | alias kompatibilitas |
| `/budgets` | redirect `/budget` | alias kompatibilitas |
| `/reports` | redirect `/report` | alias kompatibilitas |
| `/assets` | redirect `/investasi` | alias kompatibilitas |
| `/` | redirect `/dashboard` | redirect shell |

`AccountCreate.vue` adalah modal yang dipakai halaman akun, bukan route terpisah. `Goal.vue` tersedia tetapi route goals menggunakan `FinancialWorkspace.vue`.

