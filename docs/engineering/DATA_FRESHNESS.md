# Authenticated Data Freshness

The shared Axios client caches authenticated JSON GET responses under `/api/` in page-lifetime JavaScript memory, keyed by a one-way hash of token and request. TTL is 30 minutes, with a 50-entry cap and oldest-entry eviction. On a cache hit during in-app navigation it returns cached data immediately and revalidates in the background. A full page reload clears the cache and fetches from the server. Logout/session clear, any non-GET request, and `ledger:data-changed` clear the cache. Authenticated API responses are not written to browser storage or Service Worker Cache Storage.

`AppLayout.vue` shows “Menampilkan data sesi terakhir sambil memperbarui N sumber data…” only while revalidating an in-memory response during in-app navigation. It should not appear after a hard reload because no API responses persist across reloads. During in-app navigation, cached values can be briefly stale, so the warning remains until refresh finishes.

This policy avoids persistent browser storage of financial payloads while preserving fast in-session navigation. Validate cold/warm, offline, 401, account switch, mutation, and multi-page behavior in a browser before deployment. On a cold page load, data remains network-authoritative; no production timing claim is made.
