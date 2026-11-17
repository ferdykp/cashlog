# Cashlog performance baseline

Date: 2026-11-17 (Asia/Jakarta)
Baseline source revision: `67d518b` (`Load dashboard sections progressively`)
Environment: production HTTP observations plus a local production-mode Vite build. These are separate measurements and must not be compared as if they came from the same browser session.

## Production observations

One unauthenticated request was made to the public home page and one to a built JavaScript asset. This is a spot check, not a statistically meaningful benchmark.

| Request | Result | Timing / transfer | Notes |
|---|---|---|---|
| `GET https://ledger.fdevsite.cloud/` | HTTP 200 | DNS 0.002459 s; connect 0.020613 s; TLS 0.049737 s; TTFB 0.650972 s; total 0.651555 s; 1,622 bytes | One public, unauthenticated response. TTFB includes the full network path and server; it does not isolate Laravel/database time. |
| `GET /build/assets/main-rhgnFTDC.js` | HTTP/2 200 | TTFB 0.157407 s; total 0.174379 s; 43,183 encoded bytes | `application/javascript; charset=utf-8`, Brotli, Cloudflare `HIT`, `Age: 40`, immutable one-year cache. Single request. |

The asset URL referenced by the live page matched the local build entry asset at baseline. This suggests the current frontend build was present, but does not prove a specific deployed Git commit.

No authenticated dashboard session, API timings, browser performance trace, Lighthouse run, or repeated sample set was available. FCP, LCP, CLS, TBT, INP, API p50/p95, server CPU/RAM, PHP-FPM saturation, database query plans, and cache hit rates are therefore **not measured**. The production database, PHP OPcache, FPM worker settings, and origin resource limits were not inspected. Production secrets were not read.

## Local frontend baseline

A local production build of the baseline revision was inspected from its Vite manifest and Workbox output.

| Measure | Baseline |
|---|---:|
| Workbox precache | 80 entries, 1,218.89 KiB |
| Initial dashboard JS/CSS graph | 23 files; 333,753 raw bytes; 102,762 bytes deterministic gzip (gzip level 9) |
| Main entry JS | 111,103 raw bytes; 42,111 gzip bytes |
| Main CSS | 132,408 raw bytes; 23,954 gzip bytes |
| Deferred chart chunks | `chart-core`: 450,780 raw / 125,566 gzip bytes; `charts`: 132,270 raw / 37,982 gzip bytes |
| Total chart chunks included in precache | 583,050 raw bytes; 163,548 gzip bytes |

The initial graph is calculated from manifest static imports for the entry and Dashboard route. It excludes dynamically imported route/chunk dependencies. The gzip figures are local estimates, not the exact Brotli transfer sizes delivered to browsers.

## Known dashboard loading behavior at baseline

The current dashboard already loads key summary endpoints first and defers cash flow, budgets/goals, and recent transactions until their sections approach the viewport. Chart code is dynamically imported. It does not set up a periodic dashboard refresh timer. Planning data issued three GETs when loaded: budgets, goals, and goal funding summary. The goals and funding endpoints both fetch goal-related information.

## Production validation — 2026-11-17

Validated against the exact requested host, `https://ledger.fdevsite.cloud`. No request was sent to `apiledger.fdevsite.cloud`.

### Public HTML request samples

Five sequential GETs shared one HTTPS connection. The first request includes the connection setup; later samples reuse the connection. Cloudflare returned `DYNAMIC` for all samples.

| Sample | TTFB | Total | Cloudflare `cfOrigin` | Interpretation |
|---|---:|---:|---:|---|
| 1, connection setup | 3,259.8 ms | 3,260.1 ms | 3,066 ms | Severe origin-side outlier on this sample; connection setup is included in client timing. |
| 2 | 694.4 ms | 694.6 ms | 567 ms | Reused connection |
| 3 | 719.3 ms | 719.4 ms | 606 ms | Reused connection |
| 4 | 588.4 ms | 588.5 ms | 537 ms | Reused connection |
| 5 | 632.1 ms | 632.3 ms | 575 ms | Reused connection |

Warm-connection median: 694.4 ms TTFB (range 588.4–719.3 ms); `cfOrigin` median 575 ms (range 537–606 ms). An earlier five-request run had 638–704 ms warm TTFB and one 1,778 ms first request. The original baseline was one 650.972 ms TTFB sample. That single baseline and these variable samples do not establish a production regression or improvement. `cfOrigin` is Cloudflare's reported origin duration, not a Laravel-only timer.

### One authenticated browser navigation

Chrome was already logged in to the dashboard. One browser performance sample recorded navigation response start 765.9 ms, DOMContentLoaded 804.5 ms, load event 826.4 ms, and First Contentful Paint 896 ms. The browser was using a 652 × 748 responsive-emulation viewport, no CPU/network throttling, and an existing service-worker-controlled session. This is one sample, not a cold/warm pair or a comparison with the old baseline. LCP, TBT, INP, and a long-task profile were not captured.

The initial dashboard issued nine authenticated GET requests, all status 200. Client resource timings (one sample each) were:

| Rank | Endpoint | Duration |
|---:|---|---:|
| 1 | `/api/goals/funding-summary` | 3,117.9 ms |
| 2 | `/api/goals` | 2,814.0 ms |
| 3 | `/api/budgets` | 2,794.4 ms |
| 4 | `/api/cash-allocations/accounts` | 2,470.2 ms |
| 5 | `/api/reports/cash-flow` | 2,467.7 ms |
| 6 | `/api/reports/net-worth` | 2,276.4 ms |
| 7 | `/api/reports/dashboard-summary` | 1,503.2 ms |
| 8 | `/api/accounts` | 1,102.6 ms |
| 9 | `/api/user` | 1,024.3 ms |

The requests began within roughly 35 ms of one another, about 800–835 ms after navigation start. Most measured duration is before `responseStart`; API responses exposed a Cloudflare `cfExtPri` timing entry with zero duration, not Laravel/database timings. Small response bodies and a nearly shared slow duration across unrelated endpoints point to a common origin/network wait. They do not identify the query or middleware responsible. The maximum observed response time from navigation start was about 3.95 s.

### Live service worker and build identity

The dashboard was controlled by `https://ledger.fdevsite.cloud/build/sw.js`. The current browser Workbox precache cache contained 285 request keys and included `chart-core-BhbnLxCR.js`, `charts-D6Llkrig.js`, and an older `charts-BHYRbbJG.js` entry. The live SW response was 22,944 bytes, last modified 2026-11-17 14:31 UTC, and returned no-cache/BYPASS headers. The live page referenced `main-rhgnFTDC.js`; the local optimized build entry is `main-CAU2u9BQ.js`. The production service worker also contains the two current chart chunk URLs, while the local optimized service worker contains no chart chunk URL in its precache manifest. This confirms the local optimization is not the build currently served to the browser. The reason for the 285 cached keys and stale chart key needs a post-release cache verification.

No clean browser cold-cache/warm-cache comparison was captured. The HTTP connection-reuse samples above are the available cold/warm distinction; they are not PWA cache timings. No post-release browser comparison exists because the local changes have not been deployed.
