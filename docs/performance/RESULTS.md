# Performance audit results

Date: 2026-11-17

## Local production build comparison

| Measure | Baseline build | Optimized build | Result |
|---|---:|---:|---|
| Workbox install precache | 80 entries / 1,218.89 KiB | 78 entries / 648.52 KiB | 570.37 KiB less precache payload (46.8% reduction by Workbox's reported size) |
| Chart chunks in install precache | Present: chart-core + charts, 583,050 raw bytes | Excluded; deferred runtime-cached assets | Large chart code is no longer an install/update prerequisite |
| Chart chunks themselves | 583,050 raw / 163,548 gzip bytes | 583,050 raw / 165,640 gzip bytes | Code was not removed; only its loading/cache timing changed. Gzip value differs slightly with the current build output. |
| Planning section GETs | 3: budgets, goals, funding summary | 2: budgets, funding summary | One fewer request when that section is loaded |

Build output: Vite 8.2.1, 2,157 client modules transformed; Workbox injectManifest output 78 entries / 648.52 KiB. The chart chunk gzip size in the optimized Vite build was 127.17 KiB for chart-core and 38.47 KiB for charts (combined 165.64 KiB). Build size and compression are local measurements and do not establish actual browser speedup.

## Verification

- `npm test`: 15 tests passed, 0 failed.
- `php artisan test`: 68 tests passed, 537 assertions.
- `npm run build`: passed; PWA service worker generated.
- `git diff --check`: passed before final documentation edits; rerun after final edits.

## Browser and production result limits

At the original baseline, no authenticated browser trace or Lighthouse metrics were captured. The pre-release follow-up captured one authenticated Chrome navigation and API waterfall, but not Lighthouse or LCP/CLS/TBT/INP metrics. A post-deploy Chrome sample is now available below, but it is not a controlled comparison: device/cache state and the baseline run count differ. The original public homepage sample was unauthenticated and is not Laravel-only processing time.

A controlled post-deploy comparison should use the same browser/device/network, authenticated account, route, cache state, and test steps; collect several runs and compare medians and p75/p95. The smaller precache and one fewer planning request are verified in the deployed build. User-perceived speed improvement is still unproven.

## Production validation and current before/after

Validation date: 2026-11-17. Host: `https://ledger.fdevsite.cloud`. This section records the **pre-release** production state, before commits `40d3860` and `bfb0c34` were deployed.

| Metric | Earlier baseline | Current production observation | Conclusion |
|---|---:|---:|---|
| Public HTML TTFB | 650.972 ms, one sample | 694.4 ms median over four warm-connection samples (range 588.4–719.3 ms); one cold-connection sample 3,259.8 ms | Similar order of magnitude on warm connections, with a severe variable outlier. Sample counts/connection conditions differ; no valid before/after gain can be claimed. |
| Cloudflare origin duration | Not recorded | Warm median 575 ms (range 537–606 ms); one cold sample 3,066 ms | Origin is a measurable portion of public HTML delay and occasionally spikes. This is Cloudflare `cfOrigin`, not Laravel-only execution. |
| Authenticated dashboard FCP | Not measured | 896 ms, one Chrome sample | Initial content paints before most dashboard API responses complete. No comparable baseline. |
| Authenticated dashboard API | Not measured | 9 GETs; 1,024–3,118 ms per request | Current live sample, not a post-optimization comparison. |
| Workbox precache | Earlier local build: 80 / 1,218.89 KiB | Browser cache currently has 285 keys; chart-core/charts URLs are present | The new local SW is not active in production. Cache key count and manifest entry count are different measures. |
| Dashboard planning request count | Baseline source: 3 GETs | Current live dashboard still calls `/api/goals` and `/api/goals/funding-summary` separately | Duplicate request remains live; local optimized source reduces it to 2. |

### Authenticated API bottleneck ranking

Chrome Resource Timing from one authenticated dashboard load, sorted by observed duration:

| Rank | Endpoint | Duration | Status |
|---:|---|---:|---:|
| 1 | `/api/goals/funding-summary` | 3,117.9 ms | 200 |
| 2 | `/api/goals` | 2,814.0 ms | 200 |
| 3 | `/api/budgets` | 2,794.4 ms | 200 |
| 4 | `/api/cash-allocations/accounts` | 2,470.2 ms | 200 |
| 5 | `/api/reports/cash-flow` | 2,467.7 ms | 200 |
| 6 | `/api/reports/net-worth` | 2,276.4 ms | 200 |
| 7 | `/api/reports/dashboard-summary` | 1,503.2 ms | 200 |
| 8 | `/api/accounts` | 1,102.6 ms | 200 |
| 9 | `/api/user` | 1,024.3 ms | 200 |

This is one client sample per endpoint. Durations include browser/network/origin time. The requests started within about 35 ms of one another, and most time was spent awaiting the response. Cloudflare's timing entry for these API responses did not expose Laravel or DB spans.

### Laravel and database profiling

- `bootstrap/app.php` defines no custom global/API middleware. The API routes are protected by `auth:sanctum`. No production query log, Laravel Telescope/Debugbar, request trace, FPM slow log, or database `EXPLAIN` was available.
- `GoalFinancialSummaryService::forGoal()` has per-account allocation queries and per-asset relation loads inside loops when goals and allocations exist. The measured account had no goals: both goal endpoints returned empty collections, so those loops did not run during this sample. The source risk remains for populated accounts but does not explain the measured 3.12 s. Server-side spans are still required.
- `NetWorthService` eager-loads all asset transaction history for each user's assets. This may scale with history size; the 2.28 s endpoint sample alone does not prove that is its bottleneck.
- Budget index groups spend aggregates by period; source review does not show query-per-category behavior. Its 2.79 s wait likely warrants profiling at the server layer before a query rewrite.

### Vue, Pinia, charts, and navigation

- One dashboard navigation recorded response start 765.9 ms, DOMContentLoaded 804.5 ms, load event 826.4 ms, and FCP 896 ms. The viewport was Chrome responsive emulation at 652 × 748 with no throttle and existing cache/session state. LCP, TBT, INP, long tasks and Vue component render durations were not collected.
- Dashboard and chart content were visibly rendered. ApexCharts chunk transfer and component mount duration were not isolated from API waits.
- The initial load made one `/api/user` request and one `/api/accounts` request. No duplicate calls for those stores appeared in this sample. The duplicate goals endpoint pair is directly measured.
- Pinia auth/account fetch actions do not have in-flight deduplication, so concurrent components/routes could duplicate requests; this is a source-level risk, not an observed initial-load duplicate. Budget fetch tracks `hasFetched` for loading state but does not short-circuit repeated calls.
- SPA route transitions and per-menu requests were not separately timed. Router components are lazy imports and the route guard initiates saved-user validation without blocking the protected page render.

### Service worker results

The live page is controlled by `/build/sw.js`. Its active Workbox precache cache had 285 keys and included `chart-core-BhbnLxCR.js`, `charts-D6Llkrig.js`, and an older `charts-BHYRbbJG.js`. The local optimized SW build has 78 entries / 648.52 KiB and no chart chunk URLs in its precache manifest. Production still serves main bundle `main-rhgnFTDC.js`, while the local optimized build entry is `main-CAU2u9BQ.js`. The previous optimization has not been deployed to this browser. The active cache should be checked again after the release; 285 current keys may include stale entries.

### Remaining priorities

1. Release the already-tested local request reduction and SW precache fix through the normal deployment process, then compare the same authenticated browser flow.
2. Add protected server-side traces for PHP wall time, middleware/controller spans, SQL count and cumulative SQL time; collect FPM and database saturation data during a slow sample.
3. Profile goal funding with realistic fixtures. Batch reads only if the per-account/per-asset loops materially contribute, and assert exact financial-summary equality in tests.
4. Profile net-worth and cash-allocation endpoints under realistic asset and transaction counts.
5. Capture repeat cold/warm browser traces and route-transition timings on the same machine and viewport; include LCP, long tasks, chart transfer and render duration.

No new code optimization was applied in this follow-up: the remaining measured latency cannot yet be safely attributed to a code path. The first optimization patch is committed as `40d3860` and deployed, but the single post-deploy sample below does not establish a production speedup.

## Post-deploy validation

The server pulled `main` through `bfb0c34` and completed `./deploy.sh` successfully. Composer reported no dependency changes, Laravel reported `Nothing to migrate`, Vite completed, and Laravel caches were rebuilt. The operator verified `public/build/sw.js` does not contain `chart-core-` or `charts-` precache URLs. No production data migration or reset occurred.

The deployed build reported 78 Workbox precache entries / 648.55 KiB, versus the previous server build's 80 entries / 1,218.89 KiB: 570.34 KiB less reported precache payload (46.8%). The authenticated Chrome page loaded the new assets `main-DOTJMEt0.js`, `Dashboard-CcHX1i5B.js`, `charts-D6Llkrig.js`, and `chart-core-BhbnLxCR.js`; it was controlled by `https://ledger.fdevsite.cloud/build/sw.js`. The chart files were fetched as runtime assets and absent from the generated precache list.

### One authenticated Chrome session sample

Chrome used a 652 × 748 responsive viewport with no network throttling. This is a single session sample with pre-existing login/browser state, not a clean cold-device run.

| Measure | Sample 1 | Warm reload sample | Notes |
|---|---:|---:|---|
| Navigation response start | 901.9 ms | 685 ms | Browser Navigation Timing; not Laravel-only TTFB |
| DOMContentLoaded | 1,616.6 ms | 774 ms | Warm reload's document event precedes completion of async dashboard data |
| Load event | 1,619 ms | 778 ms | Not equivalent to dashboard-ready time |
| Separate `/api/goals` request | Not observed | Not observed | `/api/goals/funding-summary` was present in the first sample; consistent with one-request reduction |

First sample API Resource Timing, sorted by observed duration: `/api/reports/cash-flow` 2,939 ms; `/api/budgets` 2,876 ms; `/api/goals/funding-summary` 2,822 ms; `/api/cash-allocations/accounts` 2,621 ms; `/api/reports/dashboard-summary` 1,812 ms; `/api/accounts` 1,802 ms; `/api/reports/net-worth` 1,801 ms; `/api/user` 1,168 ms. These durations include browser/network/origin wait and come from one page sample.

The warm reload sample was captured before all deferred requests had completed. At capture, it included `/api/cash-allocations/accounts` 2,005 ms, `/api/reports/net-worth` 1,745 ms, `/api/reports/dashboard-summary` 1,504 ms, `/api/accounts` 1,425 ms, and `/api/user` 1,345 ms. The dashboard still showed loading placeholders during this interval, so the faster document events do not establish faster dashboard-ready time.

### Conclusion and remaining issues

- Deployment and the 46.8% build-level precache reduction are verified on the server.
- The separate `/api/goals` request was absent in the authenticated sample, consistent with the intended reduction from three planning requests to two.
- The dashboard is not yet proven faster for users. One sample still showed key API requests taking about 1.8–2.9 seconds, and deferred content remained in a loading state after the document load event.
- No controlled cold/warm series, LCP/INP/long-task profile, Laravel request span, SQL timing, or post-deploy navigation median is available. Do not attribute the remaining API latency to a specific query or claim a production speedup from these samples.

Next priority is protected server-side timing for the slow endpoints (`cash-flow`, `budgets`, and `goals/funding-summary`): record request wall time, SQL query count, and cumulative SQL time without logging financial payloads. Then capture repeat authenticated browser runs with identical viewport, cache state, and network conditions.

## Cross-page loading follow-up (local, not deployed)

Code review found and addressed two avoidable query patterns that affect more than the dashboard: asset snapshots were reloading already eager-loaded relations per asset (including a separate audit-history query), and cash-allocation summaries loaded allocation rows per account. The asset list, net-worth, and goal-funding paths now batch relation reads; net-worth and goal-funding use a summary-only snapshot without nested transaction detail. Financial values and formulas are unchanged.

The Financial Workspace now renders its active primary list as soon as that endpoint responds instead of waiting for six responses before assigning any result. Other data screens preserve existing results while requests refresh, and independent form data requests run concurrently. These are local source changes on 2026-11-17. `npm run build` passed and `php -l` passed for all changed PHP files. No application test suite or controlled browser timing run was performed for this follow-up.

The current local Workbox build reports 79 precache entries / 657.34 KiB. This is build output only; no production asset version, before/after request duration, dashboard-ready time, LCP, or page-transition timing was captured. Production remains on the previously observed asset until deployed.

## Cross-page session cache follow-up (local, not deployed)

Authenticated JSON GET requests to `/api/` now use a best-effort tab-scoped response cache in `sessionStorage`, keyed by a one-way token hash and normalized URL/parameters. On a reload in the same tab, any unexpired matching response (30-minute maximum age) is returned immediately and updated from the network in the background. This applies globally through the shared Axios client, so it covers all data pages that use that client, not just Dashboard. Large entries are skipped, total storage is capped, and logout, non-GET requests, and `ledger:data-changed` clear the cache. The app signals when cached values are being refreshed or a refresh failed. Service Worker API behavior remains unchanged.

This is a perceived wait-time strategy only; it does not reduce origin/API latency and does not help first-ever visits or a new tab without matching tab storage. No browser validation, production deployment, or before/after timing has yet been captured for this change. Treat freshness/invalidation behavior as requiring browser verification before release; no production performance improvement is claimed.
