# Cashlog performance audit

Date: 2026-11-17 (Asia/Jakarta)

## Scope and evidence

This audit reviewed the Laravel/Vue monolith, Vite manifest, PWA service worker configuration, relevant dashboard and financial-summary services, migrations/indexes, one public production HTTP sample, and a local production build. The audit did not access authenticated user data, production environment secrets, SSH, database credentials, or live service configuration. The full baseline and measurement limits are in [BASELINE.md](BASELINE.md).

Stack observed in repository manifests/configuration: Laravel 13, PHP 8.3 or newer, Vue 3, Vite 8, Pinia, Vue Router, Axios, Tailwind CSS 4, ApexCharts, and Workbox PWA. Production server version information previously supplied by the operator indicates Nginx 1.24 and PHP-FPM 8.4, but this audit did not independently inspect that server.

## Findings

| Priority | Finding | Evidence | User impact | Action / status |
|---|---|---|---|---|
| P1 | PWA installation precached deferred chart code | Baseline Workbox precache contained 80 entries / 1,218.89 KiB. `chart-core` + `charts` are 583,050 raw bytes and are dynamic dashboard dependencies, yet both appeared in the install precache. | Adds avoidable install/update download and work before an offline user opens charts. | Fixed locally: exclude the two chart chunks from install precache and cache same-origin hashed assets at first use with CacheFirst/expiration. |
| P1 | Planning dashboard duplicated goal loading | `Dashboard.vue` called budgets, `/api/goals`, and `/api/goals/funding-summary`; the dashboard summary shown can be built from the latter response. | One redundant HTTP request and redundant goal work whenever planning section loads. | Fixed locally: dashboard now calls budgets and funding summary only; summary response includes `name` and `icon` for the goal card. |
| P2 | Goal funding summary has potential N+1 work | `GoalFinancialSummaryService::forGoal` performs account/allocation and asset lookups inside loops; `GoalFundingController::all` calls it once per goal. | Query count may grow with number of goals, accounts, or assets. | Candidate for a measured follow-up. Runtime query count and production query plans were unavailable; no speculative financial query rewrite was applied. |
| P2 | Net-worth summary loads related transaction history | `NetWorthService::summary()` eager-loads asset prices, transactions, and allocations, then creates snapshots. | Cost may grow with a user's asset transaction history. | Candidate for profiling and query-count measurement before changing behavior. |
| P2 | Full transaction report aggregates multiple periods/categories | `TransactionService::report()` computes current and previous summaries plus category and weekly aggregates. Dashboard uses the lighter dashboard-summary endpoint instead. | Full report can be more expensive than summary cards, especially as transaction history grows. | No change: full report may need its detailed data. Profile its API separately. |
| P2 | Public page spot-check TTFB was 0.651 s | One unauthenticated curl sample; the same path had 0.651555 s total time. | Indicates there may be origin/application latency, but does not locate it or characterize the dashboard. | Investigate with repeated authenticated timing and origin-side PHP/database telemetry. Do not infer a backend bottleneck from this one sample. |
| P3 | Static asset delivery was configured effectively at the edge | Production JS returned HTTP/2 200, JavaScript MIME type, Brotli, Cloudflare HIT, and immutable one-year cache. | No immediate static asset delivery issue found in this spot check. | No change. Verify same headers after release. |
| P3 | API responses should remain private and uncached in shared PWA caches | Service worker had no API runtime cache; response data is user-specific. | Public/shared cache could expose financial data or serve stale account state. | Kept API out of the new runtime cache. |

## Data and safety

No database schema, financial arithmetic, production records, Nginx configuration, PHP-FPM configuration, DNS, CDN rules, or environment secrets were changed. Migration indexes were reviewed but not benchmarked with production `EXPLAIN`; current index presence does not prove a query is optimal.

## Overall assessment

The repository already uses useful techniques: hashed Vite assets, code-split routes, deferred charts, progressive dashboard sections, and a small dashboard summary endpoint. The audit found one concrete PWA install payload issue and one redundant planning request, both fixed locally. No evidence supports a total rewrite. The largest unresolved question is authenticated dashboard/API latency under real production data; that requires measurements from the browser and server.

## Production validation — 2026-11-17

This follow-up used only `https://ledger.fdevsite.cloud`. Five public sequential HTML GETs measured warm-connection median TTFB 694.4 ms (588.4–719.3 ms) and median Cloudflare `cfOrigin` 575 ms (537–606 ms). A separate first request took 3,259.8 ms TTFB with `cfOrigin` 3,066 ms. The original baseline was one 650.972 ms HTML TTFB sample. Sample sizes and connection conditions differ, so they do not prove a before/after gain or regression. The outlier does prove origin latency is variable and sometimes high.

One authenticated browser navigation recorded FCP 896 ms and nine status-200 API GETs. Its API resource duration ranking is in [BASELINE.md](BASELINE.md). The slowest was `/api/goals/funding-summary` at 3,117.9 ms; `/api/goals` took 2,814 ms. Requests launched almost concurrently, and most duration occurred before response start. Cloudflare exposed only `cfExtPri: 0` on the API resource timings. This ranks endpoint responses by client wait; it does not rank SQL execution or PHP middleware time.

The live page is still serving `main-rhgnFTDC.js`; the local optimized build has `main-CAU2u9BQ.js`. The active Workbox cache had 285 keys and included the chart-core and charts chunk URLs. Local optimized Workbox output is 78 entries / 648.52 KiB and has no chart URLs in its precache. The 46.8% reduction and duplicate request removal therefore remain local results; production has not yet received this version.

### Bottleneck ranking

| Priority | Production evidence | Likely layer | Confidence / next check |
|---|---|---|---|
| P1 | Origin duration ranges from 537–606 ms on four warm samples to 3,066 ms on one sample; same-origin APIs also wait 1.0–3.1 s. | Laravel/origin queue, database, or server load; client network is also included in API resource timing. | High confidence that latency is variable; no evidence to choose PHP-FPM vs DB vs origin networking. Collect request IDs, PHP-FPM slow log, DB slow query log, and per-route server timings. |
| P1 | `/api/goals/funding-summary` 3.12 s and redundant `/api/goals` 2.81 s; local patch removes the latter call. | Goal summary service plus duplicate client request. | Endpoint ranking is one real session; query attribution is not measured. Deploy the already-tested client request reduction, then compare same-session waterfall. |
| P1 | Live SW cache includes deferred chart files and 285 precache keys, versus local intended 78 / 648.52 KiB. | Release/version and Workbox precache. | Direct cache and live-page evidence. Confirm generated `sw.js` and active controller after release and remove stale entries via versioned precache cleanup if they persist. |
| P2 | Budget 2.79 s, cash-allocation summary 2.47 s, cash-flow 2.47 s, net-worth 2.28 s, dashboard summary 1.50 s. | Common origin delay dominates the client observation; endpoint-specific query cost may contribute. | One sample per endpoint. Obtain server-side spans before changing queries. |
| P2 | Account and user responses are 1.10 s and 1.02 s. | Auth/session lookup and shared origin latency. | One sample each, no database/middleware spans. |

### Laravel, DB, and middleware findings

- `bootstrap/app.php` adds no custom global/API middleware. Authenticated API routes use Laravel's `auth:sanctum` group. No server telemetry or database query log was available, so middleware execution time, query counts, lock waits, and query plans remain unmeasured.
- `GoalFinancialSummaryService::forGoal()` queries allocation history per account and loads allocated assets inside loops when goals/accounts/assets exist. That is a source-level N+1 risk for users with populated goals. The observed account had no goals; both `/api/goals` and `/api/goals/funding-summary` returned empty collections, so those loops did not run in the measured session. They cannot explain that session's 3.12 s wait. The latency appears to include a common origin-side delay; route-level server spans are required to distinguish it.
- The dashboard duplicate goals request was observed live. The local patch removes `GoalController::index()` from dashboard planning while preserving the separate goals page and the funding summary math.
- `NetWorthService` loads prices, transactions, and allocations for every user's asset before creating snapshots. The net-worth endpoint took 2.28 s, but no evidence isolates those eager-loaded histories as the cause.
- Budget index groups spend aggregation by distinct budget period, so code inspection does not show a query-per-category loop there. Its 2.79 s client wait is evidence to profile, not evidence for a rewrite.

### Vue, Pinia, charts, and navigation

- FCP was 896 ms in one responsive-emulation sample. The dashboard rendered its financial cards and ApexCharts graph. The page was already authenticated and service-worker-controlled; no clean cold browser run, LCP/TBT/INP, Vue devtools profile, long-task trace, or chart mount duration was collected.
- Nine initial API calls were visible. `/api/user` began around 800 ms and the eight dashboard calls around 816–834 ms, so auth lookup did not block the dashboard calls in that run. The live dashboard loaded both `/api/goals` and `/api/goals/funding-summary` for one goal card.
- Auth and account Pinia actions have no in-flight request deduplication. The captured initial dashboard only showed one `/api/user` and one `/api/accounts` request; there is no measured evidence of duplicate calls for those stores on initial entry. The goal endpoint pair is the proven duplicate.
- ApexCharts rendered, but its chunk transfer/render time was not isolated from API delay. Current live SW precaching the chart code explains why code splitting alone did not prevent chart bytes being installed.
- Router routes are lazy-imported and use SPA history, but menu-to-menu interaction latency and route-triggered request duplication were not measured. No changes were made based on guesses.

### Remaining issues and next priorities

1. Deploy the already-tested local frontend/SW patch through the operator's normal process; no deployment was performed here. Then verify the exact entry asset, SW manifest, and cache key count.
2. Add protected server-side timing by request ID for middleware, controller/service, DB query count and cumulative query time. Capture PHP-FPM slow requests and DB slow queries before altering financial read paths.
3. Profile `/api/goals/funding-summary` against realistic fixture counts. If its per-account/per-asset query loops dominate, batch reads while preserving current DecimalMath calculations and compare financial-output fixtures exactly.
4. Profile `/api/net-worth` asset-history loading and `/api/cash-allocations/accounts`; optimize only demonstrated expensive spans.
5. Repeat authenticated browser cold/warm traces on the same machine/account/viewport. Record FCP/LCP/long tasks, API waterfall, chart chunk transfer and render, then measure SPA route transitions.
6. Investigate occasional 3-second origin time with FPM pool saturation, CPU/memory, storage latency and database connection/query wait. The public endpoint alone cannot identify which resource is saturated.
