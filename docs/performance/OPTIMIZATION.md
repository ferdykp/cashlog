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

## Not changed

No speculative DB query rewrites or financial calculations were changed. Query performance for goal funding and net worth must be measured with realistic, non-production test fixtures or protected server telemetry first. Production nginx/cache/database settings are not part of this local code patch.

## Production validation status

The browser is still loading the previous frontend entry (`main-rhgnFTDC.js`). The current local build entry is `main-CAU2u9BQ.js`. Production's active Workbox cache contains 285 keys and chart chunk URLs; local optimized output contains 78 precache entries / 648.52 KiB and no chart URLs in the precache manifest. The live browser therefore has not received the local request reduction and chart-precache change.

The authenticated live dashboard's slowest request was `/api/goals/funding-summary` at 3,117.9 ms; the duplicate `/api/goals` request took 2,814 ms. The code safely removes that redundant dashboard request, but the observed timing does not show how much time is spent in Laravel, SQL, queueing, or the network. No query rewrite is justified until protected server-side profiling shows the cost.

The public HTML origin timing is variable: four warm-connection samples had `cfOrigin` 537–606 ms and one sample had 3,066 ms. This indicates a production origin investigation is still needed. It does not provide evidence for changing PHP-FPM, Nginx, DB, or CDN settings, and none were changed.
