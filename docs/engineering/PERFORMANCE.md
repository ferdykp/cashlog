# Performance

Use `docs/performance/BASELINE.md`, `AUDIT.md`, `OPTIMIZATION.md`, `RESULTS.md`, and `DEPLOYMENT.md` for dated measurement evidence. Do not treat local build output as a production speed improvement.

Current code keeps lazy Vue route chunks, defers chart chunks from the PWA install precache, and caches hashed static assets at runtime. Authenticated JSON uses a separate tab-scoped session cache and is not shared through the Service Worker.

This implementation pass measured no live production TTFB, API latency, browser waterfall, or interaction timing. A local production build passed; no production performance gain is claimed.
