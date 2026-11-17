# Testing

## Coverage and known gaps

PHP feature tests use isolated SQLite in-memory state; JS unit tests use Node's built-in test runner. OCR retention tests cover ownership, metadata retention, expired versus legacy/unexpired documents, and dry-run. Account lifecycle tests cover the legacy PAT API and first-party Sanctum session login/logout. Playwright browser E2E starts Laravel and Vite locally against a unique temporary SQLite database, then deletes the database and temporary Vite hot file when it exits.

Production-engine concurrency tests are not yet implemented. Do not interpret SQLite tests as evidence for MySQL/PostgreSQL row-lock or deadlock behavior. Confirm the production engine without exposing secrets, then provision an isolated matching CI service. Browser E2E uses the provided isolated local test server and never production data.

## Commands

- PHP: `vendor/bin/phpunit` (SQLite in-memory in `phpunit.xml`)
- PHP formatting: `vendor/bin/pint --test`
- JavaScript utilities: `npm test`
- Browser E2E (Chromium): `npm run test:e2e` (requires `npx playwright install chromium`)
- JavaScript formatting: `npm run format:check`
- Production frontend: `npm run build`

Feature tests cover account/auth lifecycle, finance workflows, assets/goals/shared workspace, OCR, and WhatsApp. JS tests cover decimal/money/date/share/PDF utilities. Browser E2E covers cookie login, HttpOnly session persistence across a deep-link reload, unauthenticated deep-link redirects, and logout.

Verification for this pass: 75 PHP tests / 585 assertions passed on isolated SQLite; 20 JS tests passed; 3 Playwright Chromium E2E checks passed on temporary SQLite; full-repository Pint and Prettier checks passed; Vite/PWA build passed to `/tmp/cashlog-release-check`. CI was updated but has not run on GitHub.
