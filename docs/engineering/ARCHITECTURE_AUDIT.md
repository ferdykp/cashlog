# Cashlog Engineering Architecture Audit

Audit date: 2026-11-17  
Scope: repository code and documentation review. No application code, database, or production infrastructure was changed. Tests/builds were not run as part of this read-only review.

## Executive assessment

Cashlog has a credible Laravel/Vue application structure: API routes are protected by Sanctum, request validation and API Resources are present, domain services handle important financial workflows, database amounts use decimal columns, and there are meaningful feature and utility tests. The repository is not yet at a level where financial correctness and browser security are fully demonstrated end to end. The most urgent confirmed design issue is that an account balance can be directly edited outside the transaction history. The most material security risk is long-lived bearer authentication and finance-response data stored in JavaScript-readable browser storage. Testing does not currently exercise production-like concurrency or browser journeys.

## Architecture and maturity by area

| Area | Evidence-based assessment |
|---|---|
| Backend | Good Laravel baseline: dependency-injected services, Form Requests, Resources, auth middleware, and ownership checks exist. Validation and business rules are not consistently separated across all controllers. Some service classes have broad responsibilities and are large. |
| Frontend | Vue Router lazy-loads pages; Pinia, shared Axios, and reusable utilities/components are used. Several views are very large, and cache/data-loading behavior is centralized in Axios with implicit app-wide effects that need stronger tests. |
| Security | Sanctum-protected routes, ownership validation, rate limiting, private OCR storage, and constant-time comparison for the WhatsApp webhook secret are present. Browser-accessible bearer token/cache storage creates an XSS blast-radius risk; active XSS was not confirmed in this review. Production headers were not verifiable from repository files alone. |
| Financial integrity | Strong foundations include BCMath helpers, decimal database columns, transactions, ordered account locks, and idempotency constraints. Direct balance editing, incomplete idempotency payload comparison, and frontend floating-point totals remain gaps. |
| Database | Foreign keys, unique constraints, decimal amounts, and indexes are used. Cross-user ownership relationships are primarily enforced in application code; SQLite test configuration cannot establish production locking behavior. |
| Performance | Lazy chunks, chart precache exclusion, and tab-scoped API response caching exist. The cache intentionally returns a recent cached response while revalidating it; this is visible to users through the “Menampilkan data sesi terakhir…” status. Repository review alone cannot establish current production latency. |
| Testing | Meaningful financial/API feature tests and JS utility tests exist. There is no demonstrated browser E2E suite, production-database concurrency suite, or CI workflow in the repository. |
| Developer experience | Composer/npm scripts, formatting, deployment documentation, and audit/performance notes exist. No repository CI, ESLint, PHP static analysis, or browser automation setup was found in the inspected tree. |
| UI/UX | A coherent responsive visual system and reduced-motion handling exist. Large page components and limited automated accessibility/browser coverage make consistency and regression detection harder. |

## Confirmed findings

### F-01 — Account balance is directly editable without a ledger adjustment

- **Severity:** High (financial integrity; address before treating balances as ledger-reconciled).
- **Location:** `app/Http/Requests/Account/UpdateAccountRequest.php:19-25`; `app/Http/Controllers/AccountController.php:47-55`; `app/Services/AccountService.php:34-38`.
- **Evidence:** Update validation accepts a numeric `balance`; the controller passes validated data to `AccountService`; the service updates the account row directly. No corresponding adjustment transaction is created in this path.
- **Why it matters:** The displayed account balance can change without a transaction record explaining the change, so transaction history, reports, and balance reconciliation may disagree.
- **Recommended solution:** Replace direct balance mutation with an explicit, auditable opening-balance or balance-adjustment operation. Preserve the existing account update contract for non-balance fields. Reconcile existing balances/history before deciding how legacy balances should be represented.
- **Modification risk:** High. Existing users may rely on editing balances; introducing adjustment entries changes history and can affect aggregates. Do not backfill or alter production records without a reviewed migration/reconciliation plan.
- **Required regression tests:** Reject direct balance updates; adjustment is atomic and attributable; history/report totals reconcile; owner isolation; concurrent adjustments serialize; rollback leaves both balance and history unchanged.

### F-02 — Authenticated API cache intentionally serves the last tab response during refresh

- **Severity:** Medium (financial UX/data freshness; confirmed behavior).
- **Location:** `resources/js/lib/axios.js:15-26,188-259`; `resources/js/layouts/AppLayout.vue:22-34`.
- **Evidence:** GET responses under `/api/` are cached in `sessionStorage` for up to 30 minutes. On a cache hit, Axios returns cached data immediately and refreshes it in the background. AppLayout shows “Menampilkan data sesi terakhir sambil memperbarui N sumber data…” while refreshes are pending, and another message if refresh fails.
- **Why it matters:** Users may briefly see a previously fetched financial value before the server response arrives. The status banner explains the behavior, but can be surprising and does not itself make cached values fresh. Hiding only the message would remove transparency while retaining stale display.
- **Recommended solution:** Decide explicitly between network-authoritative rendering (show a skeleton until fresh data arrives) and stale-while-revalidate (retain fast cache display but mark freshness unobtrusively and show an offline/error state). For financial amounts, prefer a small “Memperbarui” indicator or skeleton at the affected data region rather than a global amber banner, and never label stale values as current. Document cache maximum age and invalidation guarantees.
- **Modification risk:** Medium. Removing cache-first responses can make reload visibly slower and increase API load; changing the UI alone has low data-path risk but can conceal stale state. Test the chosen policy across cold load, warm load, offline, 401, logout, user switch, mutations, and multi-request pages.
- **Required regression tests:** Cached response is visibly marked until successful revalidation; offline failure is accessible; mutation/logout/account change clears cache; cache hit cannot cross auth tokens; fresh server response replaces cached values; no stale state survives after all requests complete.

### F-03 — Financial total idempotency comparison omits some persisted fields

- **Severity:** Medium (financial workflow correctness).
- **Location:** `app/Services/TransactionService.php:60-70,92-102`.
- **Evidence:** Existing-key replay comparison checks core fields such as type/account/amount/date and selected related fields, while the new transaction persistence also includes fields such as category and note that are not part of that comparison.
- **Why it matters:** Reusing an idempotency key with a changed omitted field can silently return the earlier transaction rather than report that the payload differs. The unique constraint still prevents duplicate rows, but the replay contract is ambiguous.
- **Recommended solution:** Define which request fields are semantically part of the idempotency fingerprint and compare a normalized canonical payload (or persist a payload hash). Keep optional fields and defaults canonicalized consistently.
- **Modification risk:** Medium. Existing clients/retries may use different optional-field representations; changing conflict behavior can expose previously hidden client bugs.
- **Required regression tests:** Same key/same normalized payload returns original result; changes to each meaningful field conflict; equivalent omitted/default values behave consistently; concurrent duplicate submissions create one transaction.

### F-04 — Some domain services and views have broad scope and high change cost

- **Severity:** Low (maintainability opportunity).
- **Location:** `app/Services/AssetService.php` (~759 lines); `app/Services/SharedFinancialWorkspaceService.php` (~546); `app/Services/OcrService.php` (~504); `app/Services/WhatsAppMessageProcessor.php` (~486); `resources/js/views/FinancialWorkspace.vue` (~2,688); `resources/js/views/TransactionCreate.vue` (~1,016).
- **Evidence:** Line counts show sizeable classes/components; the workspace page combines several financial areas and workflows.
- **Why it matters:** Large units increase review burden and make local changes more likely to affect unrelated flows. Size alone does not prove a defect.
- **Recommended solution:** Refactor only around observed change boundaries after behavior contracts exist: extract cohesive Vue sections/composables and narrow service collaborators while preserving public API and financial invariants.
- **Modification risk:** Medium to high for financial flows; low for presentational component extraction. Avoid broad rewrites.
- **Required regression tests:** Existing feature/utility suites plus route-level component tests for affected loading, empty, error, and mutation states; financial invariants before/after extraction.

## Suspected risks and unverified areas

- Production CSP/security headers cannot be established from this repository audit; deployment Nginx configuration is external. Do not infer that headers are absent.
- No live production latency, query plans, database locking, or account-level reconciliation was measured in this read-only code review.
- The router guard starts stored-user verification without blocking protected-route rendering (`resources/js/router/index.js:159-178`); whether this causes real duplicate requests or flashes depends on store and request timing and requires browser profiling.
- The database schema generally enforces valid referenced IDs, while same-user ownership is usually validated in application code. No cross-user exploit was confirmed; composite ownership constraints are a defense-in-depth opportunity after data audit.

## Read-only scope and limitations

No application code was changed, no production environment or database was accessed, and no test/build commands were run. Findings are based on repository implementation and checked-in documentation only.
