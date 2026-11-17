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

At the original baseline, no authenticated browser trace or Lighthouse metrics were captured. This follow-up captured one authenticated Chrome navigation and API waterfall, but not Lighthouse or LCP/CLS/TBT/INP metrics. There is no comparable post-release sample because this patch has not been deployed. The original public homepage sample was unauthenticated and is not Laravel-only processing time.

A post-deploy comparison should use the same browser/device/network, authenticated account, route, cache state, and test steps; collect several runs and compare medians and p75/p95. The first build's smaller local install precache and one fewer planning request are verified code/build changes, but they are not yet verified production speed gains.

## Production validation and current before/after

Validation date: 2026-11-17. Host: `https://ledger.fdevsite.cloud`. The production app has **not** been deployed from this worktree.

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

No new code optimization was applied in this follow-up: the remaining measured latency cannot yet be safely attributed to a code path. Existing local optimizations remain uncommitted and undeployed.
