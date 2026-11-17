# Pengalaman produk

Cashlog adalah ledger manual untuk memahami posisi kas, merekam pergerakan, menyusun batas belanja, dan mengikuti tujuan serta aset. Tampilan tidak menyiratkan akses untuk memindahkan uang di bank.

## Pertanyaan inti

1. **Berapa kas operasional yang tersedia?** Beranda menampilkan saldo tersedia sekali dan memberi jalan ke akun.
2. **Apa perubahan bulan ini?** Perbandingan pemasukan, pengeluaran, dan selisihnya memakai data aktual dari laporan.
3. **Apa yang perlu saya lakukan?** Anggaran dan target tampil sebagai ringkasan tindakan, bukan prediksi.
4. **Dari mana angka ini berasal?** Arus kas membuka laporan dan aktivitas membuka transaksi pendukung.
5. **Bagaimana merekam pergerakan?** Aksi Catat membuka pilihan pengeluaran, pemasukan, transfer, atau pindai.

## Art direction terpilih: Midnight Liquid Ledger

Fondasi midnight `#0B0F17` dengan permukaan kaca arang, blur optik, rim highlight, dan ambient emerald/cyan. Emerald menandai aksi dan arus masuk; cyan menandai fokus/data sekunder; koral menandai arus keluar/peringatan. Space Grotesk memegang angka saldo utama, Inter menjaga data ledger rapat dan terbaca, semuanya memakai digit tabular. Kartu, navigasi, input, daftar, dialog, dan feedback berbagi tingkat material kaca yang konsisten.

Art direction sekarang mengikuti dua layar referensi Stitch yang diberikan pengguna; struktur, nilai, dan alur tetap memanfaatkan API Cashlog.

## Prinsip produk

- Bedakan kas, pemasukan, pengeluaran, transfer, alokasi, dan aset sesuai semantik backend.
- Jangan menampilkan angka contoh pada produk. Empty state mengarahkan pengguna ke tindakan pertama.
- Sembunyikan nominal hanya atas tindakan pengguna dan pertahankan akses ke kontrol tersebut.
- Detail kompleks tersedia dalam halaman khusus; transaksi harian mulai dari jenis dan nominal.
- Setiap tindakan menyebut hasilnya dan memberi jalan pemulihan ketika gagal.

