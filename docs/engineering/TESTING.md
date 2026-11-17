# Testing

## Commands

- PHP: `vendor/bin/phpunit` (SQLite in-memory in `phpunit.xml`)
- PHP formatting: `vendor/bin/pint --test`
- JavaScript utilities: `npm test`
- JavaScript formatting: `npm run format:check`
- Production frontend: `npm run build`

Feature tests cover account/auth lifecycle, finance workflows, assets/goals/shared workspace, OCR, and WhatsApp. JS tests cover decimal/money/date/share/PDF utilities. There is no browser E2E suite or production-engine concurrency suite yet.

Verification for this pass: 69 PHP tests / 544 assertions passed on isolated SQLite; 20 JS tests passed; full-repository Pint and Prettier checks passed; Vite/PWA build passed to `/tmp/cashlog-build-final2`. CI was updated but has not run on GitHub.
