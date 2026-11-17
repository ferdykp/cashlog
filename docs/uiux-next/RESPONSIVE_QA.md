# QA responsif

| Lebar | Perlakuan layout yang ditetapkan |
|---|---|
| 320–359 | satu kolom untuk target/budget/aset; nominal boleh membungkus; padding diperkecil |
| 360–480 | ringkasan bertumpuk; aksi 4 kolom dengan label ringkas; rencana dua kolom |
| 481–767 | konten mobile terpusat; panel cashflow penuh; dua kolom rencana |
| 768–1023 | area konten melebar; shell mobile tetap mempertahankan navigasi |
| 1024–1279 | sidebar dan komposisi desktop mulai aktif |
| 1280–1440+ | grid tiga area rencana dan analisis berdampingan |

Header dan tab bar memakai safe-area insets; footer konten menyiapkan ruang navigasi. Chart memakai lebar kontainer; label rupiah tidak mengandalkan lebar tetap. Skenario perangkat yang perlu diverifikasi browser: 320, 375, 390, 430, 768, 1024, 1280, 1440px, landscape, zoom, keyboard, dark mode, dan reduced motion. Hasil screenshot/browser dicatat terpisah pada `VISUAL_COMPARISON.md` setelah QA.

