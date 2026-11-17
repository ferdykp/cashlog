# Refactor Results

Date: 2026-11-17. No deployment or production data/infrastructure change.

## Implemented

- Added exact decimal subtraction/sum/percentage helpers and regression cases for fractional totals, large amounts, negatives, and zero denominator.
- Replaced floating-point money aggregation in goal/budget stores, dashboard savings/remaining totals, transaction daily totals, and report expense comparison.
- Preserved monetary values as strings in reviewed bill, quick-add transaction, and related input boundaries.
- Transaction idempotency now compares category and normalized note as well as existing transaction identity/amount/date/goal fields; changed values return HTTP 409.
- Authenticated API response caching is page-memory only; a full reload no longer reads prior financial payloads from `sessionStorage`, and a regression test covers expiry and clearing.
- Removed six empty frontend placeholder files, the unreferenced scaffold `HelloWorld.vue`, seven unreferenced frontend assets, and the unreferenced public SVG icon sprite after verifying no source/config references.
- Consolidated the unlinked, superseded `docs/project-review.md` priorities into `REFACTOR_ROADMAP.md`; retained referenced deployment, privacy, OCR, WhatsApp, financial-domain, and performance documentation.
- Added GitHub Actions CI jobs for PHP tests/Pint and JS tests/Prettier/build.
- Added architecture, boundaries, standards, financial invariants, authentication, freshness, database, testing, performance, deployment, and results documentation.

## Verification

- PHP: 69 tests, 544 assertions passed with isolated SQLite in-memory configuration.
- JavaScript: 20 tests passed after adding memory-cache lifecycle and eviction cases.
- Vite/PWA production build passed to `/tmp/cashlog-build-cleanup`, leaving `public/build` unchanged; precache is 79 entries / 658.52 KiB.
- Full-repository `vendor/bin/pint --test` and `npm run format:check` pass after applying mechanical formatting across the PHP/JS sources and tests.
- No live production browser or performance measurement was taken. No performance improvement is claimed.

## Gated or pending

- Direct account balance edits still bypass an adjustment event. The proposed schema/business-behavior change was rejected by automatic review because it lacked a verified passing regression suite. No workaround was attempted; this remains blocked until an independently verified financial contract can be reviewed safely.
- Cookie-session auth migration, automatic OCR source retention/deletion policy, webhook signing, production-engine concurrency tests, browser E2E, static analysis, and large service/view decomposition remain pending due deployment/provider/domain evidence or separate incremental validation requirements.
