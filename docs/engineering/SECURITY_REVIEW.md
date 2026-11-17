# Cashlog Security Review

Audit date: 2026-11-17. Repository-only review; no production secrets, infrastructure, or database were inspected. No active XSS exploit was confirmed.

## Confirmed findings

### S-01 — Bearer token is persisted in JavaScript-readable localStorage

- **Severity:** High design risk (conditional on same-origin script execution; not a confirmed XSS vulnerability).
- **Location:** `resources/js/stores/auth.js:11,33-40,79-83`; `resources/js/lib/axios.js:277-285`; `config/sanctum.php:53-56`.
- **Evidence:** The frontend stores/reads a bearer token in localStorage and attaches it to API requests. Sanctum token expiration is configured with a 43,200-minute default.
- **Why it matters:** Any successful same-origin script injection can read and exfiltrate the token; long-lived tokens increase the exposure window. A source search did not find `v-html`, `innerHTML`, `eval`, or `new Function`, but this is not proof that all XSS paths are absent.
- **Recommended solution:** Prefer Sanctum's first-party SPA cookie/session authentication with HttpOnly, Secure, SameSite cookies and CSRF protection if deployment topology supports it. If bearer tokens remain, shorten/revoke tokens where practical, enforce strong CSP at the edge, keep untrusted content escaped, and document the threat model.
- **Modification risk:** High. Auth strategy changes affect login, CSRF, CORS, PWA behavior, API clients, and existing sessions.
- **Required regression tests:** Login/logout/revocation; expired/invalid token; CSRF on state-changing requests if cookie auth; session fixation/rotation; cross-user isolation; browser storage contains no bearer token after migration.

### S-02 — Cached authenticated finance responses are readable by page JavaScript

- **Severity:** Medium design risk (same-origin script access; no confirmed unauthorized cross-user cache leak).
- **Location:** `resources/js/lib/axios.js:15-27,88-131,160-175,188-259`.
- **Evidence:** Authenticated `/api/` GET JSON is stored in `sessionStorage`, keyed by a one-way hash of token/request, with a 30-minute TTL and logout/mutation invalidation. The one-way key hash does not encrypt response content; same-origin scripts can read sessionStorage.
- **Why it matters:** Financial response data is exposed to any script running in the origin and can remain available in the tab after navigation until invalidated/expired. The cache improves reload presentation but expands client-side sensitive-data retention.
- **Recommended solution:** Reassess whether the cache's speed benefit is worth storing financial data. Prefer memory-only cache or server-authoritative fetch if the UX/performance target permits. If retaining sessionStorage, minimize cached fields/TTL and test all auth/account-switch invalidation paths; never move this data into a shared service-worker cache.
- **Modification risk:** Medium. Removing persistence can slow reloads and increase API calls; altering key/invalidation logic can cause stale or cross-session display.
- **Required regression tests:** User/token switch isolation, logout purge, mutation invalidation, TTL expiry, storage unavailable behavior, no API response in Cache Storage, and error/offline behavior.

**Implementation status (2026-11-17): mitigated.** `resources/js/lib/axios.js` now keeps these responses only in a page-lifetime memory cache. A full page reload clears the payloads; the Service Worker continues to cache only static build assets. Unit tests cover TTL expiry, explicit clearing, and rejection of invalid cache values. Browser-level user-switch/offline/cache-storage checks remain pending.

### S-03 — Uploaded OCR source documents have no automatic deletion policy

- **Severity:** Medium (privacy/retention).
- **Location:** `app/Http/Controllers/ImportController.php:39-63,89-92`; `docs/data-privacy.md:8,15,20-26`.
- **Evidence:** Successful source documents are stored on the private disk. The privacy document states OCR files are retained and are not automatically deleted.
- **Why it matters:** Receipts and financial documents may include personal or account data. Indefinite retention increases impact from storage/backup exposure and may not match user expectations.
- **Recommended solution:** Define retention and deletion behavior; delete source documents after extraction where product needs allow, or provide clear retention controls. Confirm private disk, backup access, and owner checks.
- **Modification risk:** Medium. Deleting files can affect reprocessing/support; retention changes should not remove records needed by an active workflow.
- **Required regression tests:** Owner cannot access another user's file; successful import follows policy; failed/pending import preserves only as long as needed; deletion handles missing files and database rollback safely.

### S-04 — WhatsApp webhook uses a static shared-secret header

- **Severity:** Medium hardening opportunity; no secret compromise or unauthorized call was demonstrated.
- **Location:** `app/Http/Controllers/Webhooks/WhatsAppWebhookController.php:24-38`; `routes/api.php` webhook route and throttle (around line 29).
- **Evidence:** The controller compares a supplied static secret using `hash_equals`; route throttling is present. No request timestamp/signature or replay window was found in the inspected handler.
- **Why it matters:** A leaked static secret can be replayed; rate limiting and message-ID deduplication reduce but do not eliminate the impact.
- **Recommended solution:** Use provider-supported HMAC signatures with timestamp/replay protection, or rotateable secret credentials with a documented rotation process. Keep idempotent message processing.
- **Modification risk:** Medium. Provider callback compatibility and retry semantics must be preserved.
- **Required regression tests:** Valid signature, invalid signature, expired timestamp/replay, duplicate provider event, throttling, and secret rotation.

## Positive controls observed

- API routes use Sanctum authentication (`routes/api.php`).
- User ownership checks appear in request/service flows, and a source search did not find common raw HTML/script sinks in Vue.
- The webhook comparison is constant-time (`hash_equals`) and the route is throttled.
- OCR files use a private storage disk rather than public web storage.
- CORS is configured with explicit origins rather than wildcard origin; Axios currently uses bearer auth with `withCredentials: false`.
- Service worker code does not install a runtime cache for authenticated `/api/` responses (`resources/js/sw.js`).

## Not verifiable from repository alone

Production CSP, HSTS, TLS policy, Nginx access controls, Cloudflare rules, PHP-FPM permissions, database network policy, secret rotation, backup retention, and actual token revocation operations require deployment evidence. No `.env` secret values were inspected.
