# Cashlog Testing Gaps

Audit date: 2026-11-17. Inventory is based on files/configuration; no tests were executed.

## Existing coverage observed

- PHPUnit feature suites include account lifecycle, audit regression, finance workflow/ecosystem, OCR import, shared workspace, and WhatsApp workflows under `tests/Feature`.
- A unit example exists under `tests/Unit`.
- JavaScript utility tests cover decimal/money/share-target/general utilities under `tests/js`.
- `phpunit.xml` configures SQLite in-memory tests; package scripts include JS test/build/format checks; Composer includes test and Pint tooling.

## Gaps requiring priority

### T-01 — No demonstrated reconciliation test for direct balance edits

- **Severity:** High.
- **Location:** `app/Services/AccountService.php:34-38`; `app/Http/Requests/Account/UpdateAccountRequest.php:19-25`; relevant tests under `tests/Feature/AccountLifecycleTest.php` and finance suites.
- **Evidence:** Code supports direct balance changes; no inspected test establishes that every balance delta has an auditable ledger entry.
- **Why it matters:** Current feature tests can pass while account balance and transaction history diverge.
- **Recommended solution:** Before changing the balance flow, add invariant tests for starting balance + signed transactions + adjustments = current balance, including rollback and ownership.
- **Modification risk:** High if fixtures encode current direct-edit semantics.
- **Required regression tests:** Direct update rejection; adjustment atomically writes history; concurrent updates; transfer rollback; report parity.

### T-02 — No production-like database concurrency coverage

- **Severity:** High for financial writes.
- **Location:** `phpunit.xml` SQLite in-memory configuration; financial lock paths in `app/Services/TransactionService.php`.
- **Evidence:** SQLite in-memory tests do not prove row-level locking behavior on the production database engine.
- **Why it matters:** Lost updates, deadlocks, lock ordering, and idempotency races can differ by engine and deployment isolation settings.
- **Recommended solution:** Add a separate integration-test job using the same database engine/version as production, isolated test database, and concurrent request/process scenarios. Keep ordinary unit tests fast.
- **Modification risk:** Medium operational complexity; test DB must never point at production.
- **Required regression tests:** Two simultaneous debits, transfers in opposite directions, duplicate idempotency key, concurrent goal contribution, rollback on downstream failure.

### T-03 — No browser E2E validation of session cache and all data routes

- **Severity:** High for production UX reliability; medium for security.
- **Location:** `resources/js/lib/axios.js:188-259`; `resources/js/layouts/AppLayout.vue:22-34`; `resources/js/router/index.js`.
- **Evidence:** No Playwright/Cypress/Dusk setup was found. Cache freshness, the “Menampilkan data sesi terakhir…” banner, account switching, and direct route reload are lifecycle/browser behaviors.
- **Why it matters:** Unit tests do not validate actual browser storage, service worker control, refresh races, history navigation, or accessible status messaging.
- **Recommended solution:** Add a small critical-path E2E suite after deciding cache policy: login, reload a data page, warm cache refresh, offline/error, logout/account switch, create/edit transaction and verify refreshed totals.
- **Modification risk:** Low to medium; flaky network dependencies should be avoided with controlled test responses.
- **Required regression tests:** The scenarios listed above across dashboard, transactions, wallets, reports, budgets, goals, assets, bills, and shared workspace routes.

### T-04 — Financial UI money aggregation lacks exact-decimal parity tests

- **Severity:** Medium.
- **Location:** `resources/js/stores/goal.js:11-31`; `resources/js/stores/budget.js:11-35`; `resources/js/views/TransactionHistory.vue:127-154`; `resources/js/utils/decimal.js`.
- **Evidence:** Number-based sum/subtract paths coexist with exact decimal helpers; JS tests cover decimal utilities but not all these store/view aggregates.
- **Why it matters:** Tests for the helper alone cannot protect components that bypass it.
- **Recommended solution:** Add store/composable tests for monetary totals and ensure UI totals match API fixtures.
- **Modification risk:** Low; tests may expose rounding expectation mismatches that need product confirmation.
- **Required regression tests:** Fractional cent edge cases, large values, net negative, empty lists, filtering/date boundaries.

### T-05 — Security negative cases need systematic coverage

- **Severity:** Medium.
- **Location:** `routes/api.php`; account/transaction request validators; WhatsApp webhook; OCR import endpoints.
- **Evidence:** Some ownership and workflow tests exist, but no single demonstrated matrix covers cross-user access, expired/revoked auth, webhook replay, file ownership/retention, and rate limiting.
- **Why it matters:** Happy-path coverage cannot establish tenant boundaries or abuse controls.
- **Recommended solution:** Maintain a route/security matrix and add negative tests for sensitive API groups as features change.
- **Modification risk:** Low; test fixture and authentication setup effort.
- **Required regression tests:** User A cannot read/update/delete User B records/files; missing/invalid/expired auth; bad/replayed webhook; upload size/type/path validation; throttle limits.

### T-06 — No repository CI/static-analysis workflow was found

- **Severity:** Medium (release reliability).
- **Location:** no `.github/workflows` files were found in the inspected tree; `package.json`, `composer.json` scripts.
- **Evidence:** Local test/format scripts exist but no checked-in CI workflow was found. No ESLint/PHPStan/Larastan configuration was found.
- **Why it matters:** Build, formatting, and test regressions can reach the main branch without consistent automated checks.
- **Recommended solution:** Add staged CI for dependency install, JS tests/build/format check and PHP tests/Pint; introduce static analysis incrementally after resolving current baseline findings.
- **Modification risk:** Low to medium; CI may reveal existing failures and require agreed baseline handling.
- **Required regression tests:** CI workflow itself should use isolated services and fail on test/build/format errors; dependency/security scans can be a separate non-blocking initial stage.

## Suggested order

1. Add financial invariants and idempotency tests before behavior changes.
2. Add production-engine concurrency integration tests.
3. Add browser cache/auth/reload E2E tests across data-bearing routes.
4. Cover money aggregation stores using exact decimal fixtures.
5. Add CI checks and gradually introduce static analysis/accessibility automation.
