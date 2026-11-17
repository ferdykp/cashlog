# Refactor Results

Date: 2026-11-17. No deployment or production data/infrastructure change.

## Implemented

- Added first-party Sanctum cookie authentication for the Vue SPA: stateful API middleware, CSRF bootstrap/XSRF Axios credentials, session-ID regeneration on login, session invalidation on logout, session-aware route guards, and successful-login removal of legacy browser bearer tokens. Kept `/api/login` PAT support for existing external clients.
- Added additive OCR source retention: configurable 30-day default expiry for new imports, owner-checked source deletion, a dry-run-capable cleanup command scheduled daily, and metadata retention. Legacy imports with no expiry remain untouched.
- Added regression coverage for session/PAT auth and OCR ownership, cleanup selection, metadata preservation, and dry-run behavior.
- Added/update security, authentication, deployment, testing, and privacy documentation, including staging rollout requirements.
- Removed the “Menampilkan data sesi terakhir…” and offline stale-cache banner from the app shell while retaining background cache refresh and request-error handling.
- Added opt-in Evolution HS256 JWT verification with a 10-minute claim window and one-message-ID replay binding, while preserving the existing shared-secret mode and idempotent retries.

- Added exact decimal subtraction/sum/percentage helpers and regression cases for fractional totals, large amounts, negatives, and zero denominator.
- Replaced floating-point money aggregation in goal/budget stores, dashboard savings/remaining totals, transaction daily totals, and report expense comparison.
- Preserved monetary values as strings in reviewed bill, quick-add transaction, and related input boundaries.
- Transaction idempotency now compares category and normalized note as well as existing transaction identity/amount/date/goal fields; changed values return HTTP 409.
- Authenticated API response caching is page-memory only; a full reload no longer reads prior financial payloads from `sessionStorage`, and a regression test covers expiry and clearing.
- Removed six empty frontend placeholder files, the unreferenced scaffold `HelloWorld.vue`, seven unreferenced frontend assets, and the unreferenced public SVG icon sprite after verifying no source/config references.
- Consolidated the unlinked, superseded `docs/project-review.md` priorities into `REFACTOR_ROADMAP.md`; retained referenced deployment, privacy, OCR, WhatsApp, financial-domain, and performance documentation.
- Added GitHub Actions CI jobs for PHP tests/Pint and JS tests/Prettier/build.
- Added Playwright Chromium E2E to CI for SPA session login, direct navigation/reload, unauthenticated deep-link redirects, and logout. The local runner provisions unique temporary SQLite state, pins API requests to localhost, and cleans temporary database/Vite hot files on exit.
- Added architecture, boundaries, standards, financial invariants, authentication, freshness, database, testing, performance, deployment, and results documentation.

## Verification

- PHP: 75 tests / 585 assertions passed with isolated SQLite in-memory configuration, including session-auth, OCR retention, webhook JWT, expiry, and replay coverage.
- JavaScript: 20 tests passed after adding memory-cache lifecycle and eviction cases.
- Final Vite/PWA build passed to `/tmp/cashlog-release-check`, leaving `public/build` unchanged; precache is 79 entries / 658.03 KiB.
- Full-repository `vendor/bin/pint --test` and `npm run format:check` pass after applying mechanical formatting across the PHP/JS sources and tests.
- No live production browser or performance measurement was taken. No performance improvement is claimed.

## Remaining work and gates

- Direct account balance edits still lack a verified auditable adjustment contract and regression suite. Automatic review rejected the earlier behavior/schema patch on this basis. No override or workaround was used; no production balances/history were changed.
- Cookie auth code is implemented, but production rollout remains pending staging verification of CSRF, secure cookie settings, session storage, PWA lifecycle, and refresh/logout. Existing PAT endpoint remains for integrations.
- Webhook supports the current shared-secret flow and optional Evolution HS256 JWT verification with an exp/iat window and token-to-message-ID replay binding. Production JWT mode is not enabled automatically; it requires matching provider configuration. Shared-secret fallback has no timestamp window.
- Database-engine concurrency integration tests, static analysis, and the large domain service/page decomposition remain incomplete. Production DB engine has not been confirmed from safe repository configuration.
- Dead-code cleanup completed earlier for verified unreferenced placeholders/assets/docs. No additional files were deleted in this continuation without a reference check.

## Completion matrix

| Task                                   | Status              | Files Changed                                                                                                                                | Tests                                                    | Remaining Blocker                                                                                            |
| -------------------------------------- | ------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| Auditable balance adjustment           | BLOCKED             | None for behavior                                                                                                                            | Existing suite only; requested new tests not added       | Automatic review requires verified financial invariants/regressions before changing balance/history behavior |
| Sanctum SPA cookie auth                | PARTIALLY COMPLETED | `bootstrap/app.php`, `config/sanctum.php`, `routes/api.php`, `AuthController.php`, Axios/auth store/router, lifecycle test, auth/deploy docs | 75 PHP tests passed; browser CSRF/refresh not exercised  | Staging environment settings and browser CSRF/refresh validation                                             |
| OCR retention/deletion                 | PARTIALLY COMPLETED | Migration, model/controller/service/command, scheduler, OCR tests/docs, `.env.example`                                                       | 75 PHP tests passed, including retention cases           | Scheduler must be activated by deployment process; legacy imports intentionally have no expiry               |
| Webhook signature/replay security      | PARTIALLY COMPLETED | Webhook controller, services config, `.env.example`, feature tests, security docs                                                            | WhatsApp tests cover JWT expiry/replay and shared secret | JWT mode needs coordinated provider configuration; shared-secret fallback has no timestamp window            |
| Production-engine concurrency tests    | NOT STARTED         | Docs only                                                                                                                                    | None                                                     | Confirm production DB engine, add isolated CI service and race tests                                         |
| Browser E2E                            | IMPLEMENTED         | Playwright config, isolated local server/seeder, auth/navigation specs, CI job, testing docs                                                 | 3 Chromium checks passed locally on temporary SQLite    | GitHub Actions run and production staging verification                                                        |
| Backend service decomposition          | PARTIALLY COMPLETED | OCR retention extracted as its own service                                                                                                   | OCR tests passed                                         | Major Asset/Workspace/OCR/WhatsApp responsibilities still need use-case extractions with regression coverage |
| Vue feature decomposition              | NOT STARTED         | Auth state-flow adjustment only                                                                                                              | JS suite pending                                         | Large page containers still require incremental component/composable extraction                              |
| Security/financial regression tests    | PARTIALLY COMPLETED | Lifecycle, OCR, and webhook regression tests                                                                                                 | 75 PHP tests / 585 assertions; 20 JS tests passed        | Account adjustment and browser authorization cases                                                           |
| Documentation/deployment compatibility | PARTIALLY COMPLETED | Required engineering docs updated/added                                                                                                      | Prettier and build checks passed                         | Staging rollout evidence and production DB/provider confirmation remain                                      |
| Remove stale-cache notification        | COMPLETED           | `resources/js/layouts/AppLayout.vue`, `resources/js/lib/axios.js`, `resources/js/premium.css`                                                | Text absent, 20 JS tests pass, production build passes   | None for source change; production visibility requires normal deployment                                     |
