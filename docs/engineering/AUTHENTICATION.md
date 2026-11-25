# Authentication

Private API routes use Sanctum. The first-party Vue SPA uses session-cookie login at `/api/session/login`, HttpOnly session cookies, CSRF bootstrap through `/sanctum/csrf-cookie`, and session logout at `/api/session/logout`. The `/api/login` PAT flow remains available for supported external clients. The SPA clears and does not reuse legacy localStorage bearer tokens.

JavaScript-readable legacy tokens increase the impact of same-origin script execution. Existing browser bearer tokens are discarded on app startup; users must authenticate with the session-cookie flow. Preserve the backend bearer flow for external API clients.

Never log tokens or cookies. Test logout, expiry/revocation, session rotation, account switching, CSRF for cookie writes, and cross-user authorization before rollout.

Password reset and authenticated password changes revoke Sanctum personal access tokens, database-backed sessions on other devices, and remembered-device tokens. Keep the session driver on the database backend when relying on cross-device session revocation.

## Cookie deployment settings

Use same-origin `https://ledger.fdevsite.cloud`; keep `SESSION_DOMAIN` unset unless a verified subdomain requirement exists. In production set `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`, and include the frontend host in `SANCTUM_STATEFUL_DOMAINS`. Keep credentialed CORS restricted to explicit trusted origins. The app and browser client have been updated, but verify these environment settings and login/refresh/mutation/logout in staging before production rollout.
