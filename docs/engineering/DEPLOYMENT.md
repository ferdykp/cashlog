# Deployment Notes

No production deployment, infrastructure change, or database migration was performed. CI was added and locally mirrored, but has not yet run on GitHub.

Before release, review and commit the branch; verify production DB engine and backup/rollback plan without exposing `.env`; deploy through the normal operator procedure; then verify login/logout, direct route reload, transaction retry, dashboard totals, budget/goal/report screens, and service-worker update on `https://ledger.fdevsite.cloud`. Compare authenticated cold/warm timings against the dated performance baseline.

For Nginx/PWA checks, see `docs/performance/DEPLOYMENT.md`. Do not use `apiledger.fdevsite.cloud` for production validation.
