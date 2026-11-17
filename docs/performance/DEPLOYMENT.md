# Deploy and verify performance changes

These changes are committed and pushed to GitHub `main`, but have not been deployed to production. This document describes the operator-run release path; it does not change or restart production services.

Validation on 2026-11-17 confirmed production still serves `main-rhgnFTDC.js` and a service worker that precaches chart chunks. The local build uses `main-CAU2u9BQ.js` and excludes chart chunks from precache. Do not treat the local 46.8% precache reduction as live until the release below is performed and rechecked.

## Release

On the server, after reviewing the pushed commit, run the project's existing deployment script:

```bash
cd /var/www/cashlog
./deploy.sh
```

The script should pull the selected branch and rebuild the Laravel/Vite production assets. Review its output and stop if a command fails. Do not run migrations for this performance patch; it adds no schema changes.

## Verify service worker and asset behavior

Check the deployed page and service worker:

```bash
curl -sSI https://ledger.fdevsite.cloud/
curl -sSI https://ledger.fdevsite.cloud/build/sw.js
```

The service worker should be served successfully with revalidation/no-cache headers and `Service-Worker-Allowed: /` if that header remains part of the current Nginx setup. Inspect generated precache entries on the server without opening `.env`:

```bash
cd /var/www/cashlog
rg -n 'chart-core-|charts-' public/build/sw.js
```

The chart chunk names should not appear as precache URLs in the generated `sw.js`. After logging in and opening the dashboard chart, requests for these hashed JS assets should succeed with a JavaScript MIME type. The service worker may cache them only after a successful request.

Confirm normal app behavior after deploy: login, dashboard, chart, budgets, goal card, report, and share-target flow. Clear/reload the service worker only if the browser continues using an old worker; users may need one successful online visit to acquire the deferred chart chunks.

For an authenticated browser validation, record the Network waterfall from a clean navigation, note the entry JS filename and request count, and check the service worker controller under DevTools Application. After opening Arus Kas once online, the chart chunks should appear in `cashlog-static-assets-v1`; they should not be listed as precache URLs. Compare repeated cold and warm runs on the same browser/device and preserve API bodies as private data. Do not export an unsanitized HAR or include cookies/authorization headers in the audit.

The currently observed production precache cache had 285 keys and included current and older chart chunks. After activating the new worker, confirm the new precache manifest is active and check whether any stale chart entries remain. If old assets remain in the precache cache after activation, investigate Workbox's cleanup behavior/versioned cache names before deleting anything manually.

## No production changes included

Do not change Nginx, PHP-FPM, database, Cloudflare, or DNS as part of this code release. API data remains uncached by the service worker. This patch makes no database migrations and does not reset or modify user data.
