# Deployment Notes

The production browser URL is `https://ledger.fdevsite.cloud`, with same-origin `/api` endpoints. For cookie auth, set `SESSION_SECURE_COOKIE=true`, retain `SESSION_HTTP_ONLY=true` and `SESSION_SAME_SITE=lax`, keep `SESSION_DOMAIN` host-only unless verified otherwise, and include the exact SPA host in `SANCTUM_STATEFUL_DOMAINS`. Keep CORS credentials enabled only for explicit origins. Validate login, direct refresh, a protected mutation (CSRF), expiry, and logout in staging with a non-production account. The legacy bearer endpoint stays available during rollout.

The OCR expiry cleanup requires the standard Laravel scheduler (`schedule:run` once per minute or an equivalent managed scheduler). Preview with `php artisan ocr:cleanup-expired --dry-run`. No production deployment or production-data purge was performed for these changes.

No production deployment, infrastructure change, or database migration was performed. CI was added and locally mirrored, but has not yet run on GitHub.

Before release, review and commit the branch; verify production DB engine and backup/rollback plan without exposing `.env`; deploy through the normal operator procedure; then verify login/logout, direct route reload, transaction retry, dashboard totals, budget/goal/report screens, and service-worker update on `https://ledger.fdevsite.cloud`. Compare authenticated cold/warm timings against the dated performance baseline.

For Nginx/PWA checks, see `docs/performance/DEPLOYMENT.md`. Do not use `apiledger.fdevsite.cloud` for production validation.
