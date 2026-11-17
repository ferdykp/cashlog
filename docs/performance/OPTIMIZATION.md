# Local optimizations

Date: 2026-11-17

## 1. Defer chart chunks from PWA installation

`vite.config.js` now excludes `chart-core-*.js` and `charts-*.js` from the Workbox install precache. These are only used by the deferred dashboard chart. `resources/js/sw.js` registers a same-origin `/build/assets/` CacheFirst route with at most 100 entries and a one-year expiration. The chunks are cached after their first successful use. User-specific `/api/` responses are not cached.

Effect: the browser does not need to download chart libraries just to install/update the PWA or load the initial shell. The chart will be available offline after it has been loaded once while online. It is not guaranteed offline on a fresh install before the user visits that feature.

## 2. Remove duplicate goal request from dashboard

`Dashboard.vue` no longer fetches the full goal store separately when loading the planning section. It uses the existing goal funding summary endpoint, whose rows now also contain the goal `name` and `icon` from `GoalFundingController`. This removes one dashboard GET and avoids fetching goal data twice for the same card. It does not alter goal funding calculations or persisted records.

The card now displays an explicit error state if funding summaries fail, and preserves any previously loaded summary during a refresh failure.

## 3. Test harness compatibility

`tests/js/share-target.test.js` removes all top-level Workbox imports from the service-worker source before evaluating the share-target handler in its isolated VM, and stubs the Workbox APIs. The previous harness only removed the first import, so it failed to parse as soon as the service worker had more than one Workbox import. This keeps its existing share-target scenarios exercising the same handler.

## 4. Batch finance summary reads and render page data progressively

Source review found repeated per-asset reads in `AssetService::snapshot()`: the asset list eagerly loaded relations, then each snapshot discarded and loaded them again, and loaded audit history separately. Summary endpoints also called the full-detail snapshot even though they only need valuation fields. Asset list, net worth, and goal funding summaries now eager-load the relations they use once and use a lightweight snapshot mode when transaction details are not needed. The financial calculations remain in the existing snapshot logic.

`GoalCashAllocationService::summary()` previously queried allocation rows and goal relations separately for each cash account. It now fetches the user's account allocation rows in one batch and groups them in memory; decimal summation and response fields are unchanged.

On the client, the Financial Workspace previously waited for six independent GETs and assigned all results only after the slowest one completed. It now applies each response as it arrives and reveals the active goals/assets page once its primary list is ready. Switching between goals and investments reuses the same loaded workspace. Account, category, goal, budget, report, transaction, bills, and shared-workspace screens keep already-rendered data visible while refreshes run; independent account/category requests start concurrently.

These changes target code-level query and render barriers found during source review. No new browser/server timing comparison is available yet, so this is not evidence of a production speedup.

## Not changed

Financial formulas and persisted records were not changed. A tab-scoped `sessionStorage` cache for authenticated JSON GET requests under `/api/` is now added in `resources/js/lib/axios.js`. It uses a 30-minute freshness window, scopes entries by a one-way hash of the current bearer token and normalized request, skips large responses, and is cleared on logout, any non-GET request, or the existing `ledger:data-changed` event. It is not a service-worker/shared cache. On reload, a matching response is shown immediately and revalidated in the background; the app displays a status while that happens and indicates when refresh failed. Cache use is best-effort when browser storage is unavailable. Production nginx/cache/database settings are not part of this local code patch.

This client cache reduces the wait before previously loaded values reappear; it does not make the Laravel request itself faster and it cannot show data that has never been loaded in the current tab session. Verify storage behavior, logout clearing, mutation invalidation, and stale/offline messaging in a browser before release. Do not claim production speed improvement until the new build is deployed and cold/warm authenticated page timings are compared.

## Production validation status

The browser is still loading the previous frontend entry (`main-rhgnFTDC.js`). The current local build entry is `main-CAU2u9BQ.js`. Production's active Workbox cache contains 285 keys and chart chunk URLs; local optimized output contains 78 precache entries / 648.52 KiB and no chart URLs in the precache manifest. The live browser therefore has not received the local request reduction and chart-precache change.

The authenticated live dashboard's slowest request was `/api/goals/funding-summary` at 3,117.9 ms; the duplicate `/api/goals` request took 2,814 ms. The code safely removes that redundant dashboard request, but the observed timing does not show how much time is spent in Laravel, SQL, queueing, or the network. No query rewrite is justified until protected server-side profiling shows the cost.

The public HTML origin timing is variable: four warm-connection samples had `cfOrigin` 537–606 ms and one sample had 3,066 ms. This indicates a production origin investigation is still needed. It does not provide evidence for changing PHP-FPM, Nginx, DB, or CDN settings, and none were changed.
