# Cashlog API

API Laravel untuk aplikasi Cashlog. Panduan setup, konfigurasi integrasi, scheduler, dan deployment ada di [README utama](../README.md).

## Perintah lokal

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Integrasi dan catatan teknis

- OCR: [docs/ocr.md](docs/ocr.md)
- WhatsApp: [docs/whatsapp.md](docs/whatsapp.md)
- Arsitektur finansial: [docs/financial-ecosystem-architecture.md](docs/financial-ecosystem-architecture.md)
- Privasi dan retensi: [../docs/data-privacy.md](../docs/data-privacy.md)
- Audit perbaikan: [docs/audit-fixes.md](docs/audit-fixes.md)
