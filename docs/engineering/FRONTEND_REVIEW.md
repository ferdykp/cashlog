# Cashlog Frontend Review

Audit date: 2026-11-17. Source review only; no UI code was changed and no build/browser tests were run.

## Confirmed findings

### FE-01 — The global cache banner reveals stale-while-revalidate behavior

- **Severity:** Medium (financial data freshness and user trust).
- **Location:** `resources/js/lib/axios.js:15-26,188-259`; `resources/js/layouts/AppLayout.vue:22-34`; styling `resources/js/premium.css:390-402`.
- **Evidence:** A cached `/api/` GET response from the current tab (up to 30 minutes old) is returned immediately, then revalidated asynchronously. AppLayout globally displays “Menampilkan data sesi terakhir sambil memperbarui N sumber data…” whenever cached data exists and one or more refreshes are pending. Failed refresh may leave the last values visible with an offline notice.
- **Why it matters:** On reload, finance values can appear before current server data. The global message can look like an unexpected loading warning, and it counts requests rather than explaining which value is stale. Hiding the banner alone would conceal the freshness state without changing cached-first behavior.
- **Recommended solution:** Choose and document the data freshness contract. For finance screens, either render a skeleton until the server response is fresh, or retain the fast cached display with a compact per-region “Memperbarui”/offline marker and visible last-updated time. Do not globally say “sesi terakhir” if the product expectation is server-current data. Do not remove the banner until an alternative stale/error indication is in place.
- **Modification risk:** Medium. Network-first can make reload slower and increase API traffic; changing only presentation can reduce transparency.
- **Required regression tests:** Cold/warm navigation, cache hit and refresh success/failure, offline reload, stale data age threshold, logout/account switch, mutation invalidation, screen-reader announcement, and no stale banner after pending requests settle.

**Implementation status (2026-11-17): partially resolved.** API payloads now remain in page memory only, so a hard reload cannot present the previous tab's finance data or show the session-cache banner. In-app navigation still uses stale-while-revalidate and retains the freshness indicator. Browser verification of offline, account switch, and announcement behavior remains pending.

### FE-02 — Several views have unusually broad responsibilities

- **Severity:** Low (maintainability opportunity).
- **Location:** `resources/js/views/FinancialWorkspace.vue` (~2,688 lines); `TransactionCreate.vue` (~1,016); `Dashboard.vue` (~792); `TransactionHistory.vue` (~598).
- **Evidence:** File sizes and mixed workspace behavior show high component scope. Size alone is not a functional defect.
- **Why it matters:** Harder to isolate loading/error states, reuse sections, and catch regressions in one financial area while changing another.
- **Recommended solution:** Extract cohesive subviews/composables incrementally after documenting props/events and behavior. Keep domain calculations in tested utilities/stores, not presentational components.
- **Modification risk:** Medium. Component extraction can change lifecycle timing and request counts even when markup is unchanged.
- **Required regression tests:** Route/component tests for loading, empty, error, success, filters, forms, and navigation; request count assertions for affected screens.

### FE-03 — Pinia summary stores have inconsistent money arithmetic

- **Severity:** Medium for financial display consistency.
- **Location:** `resources/js/stores/goal.js:11-31`; `resources/js/stores/budget.js:11-35`; exact helper at `resources/js/utils/decimal.js`.
- **Evidence:** Goal and budget totals use `parseFloat`/Number while account calculations use exact decimal helpers.
- **Why it matters:** Rounding can vary between pages and differ from exact API values.
- **Recommended solution:** Use one shared decimal arithmetic/formatting utility at every monetary aggregation boundary.
- **Modification risk:** Low to medium; visible rounding may change.
- **Required regression tests:** Exact decimal aggregate fixtures and comparison with API summaries.

## Positive implementation evidence

- Pages are lazy-loaded through `resources/js/router/index.js:5-23,25-143`.
- Axios is shared across the app and authentication interceptor reads the token from localStorage (`resources/js/lib/axios.js:277-285`).
- Pinia stores and reusable money/decimal utilities exist.
- PWA worker excludes `/api/` responses from shared runtime caching; hashed static assets are cached at runtime (`resources/js/sw.js:12-26`).
- Reduced-motion styles are present in `resources/js/premium.css`.

## Verification gaps

- No browser E2E suite was found for reload, direct route entry, login expiry, offline behavior, or navigation data freshness.
- No automated accessibility scan/keyboard flow was found. Check focus restoration, accessible names, chart alternatives, contrast, and live-region behavior.
- Router auth guard verification is asynchronous (`resources/js/router/index.js:159-178`); whether it triggers real request races requires a browser network trace.
- Current production experience was not measured in this repository-only audit. Existing performance notes must be read with their recorded deployment dates and build versions.
