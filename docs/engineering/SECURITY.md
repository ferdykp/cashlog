# Security implementation notes

## Authentication and CSRF

- The first-party SPA uses Sanctum session cookies with Laravel CSRF middleware. Cookies are HttpOnly; Secure defaults on in production and SameSite remains Lax.
- Sessions remain valid for up to 30 days of inactivity by default (`SESSION_LIFETIME=43200`); the cookie does not expire when the browser closes (`SESSION_EXPIRE_ON_CLOSE=false`).
- The bearer-token `/api/login` flow remains for external API clients. The SPA does not store or send bearer tokens and clears legacy browser tokens on startup.
- Sanctum PATs expire after 7 days by default; deployments can shorten this through `SANCTUM_EXPIRATION`.
- Login routes are rate-limited. Protected application routes continue to require `auth:sanctum`.
- The SPA and API should be same-origin at `https://ledger.fdevsite.cloud`; credentialed CORS allows only that origin in production and adds localhost origins only in local mode.

## Webhooks

The Evolution webhook accepts provider-generated JWT auth via `EVOLUTION_WEBHOOK_JWT_SECRET` in production. JWT mode accepts HS256 only, verifies the signature and `iat`/`exp` (maximum 10-minute lifetime), and binds each token fingerprint to one provider message ID until expiry; retries with that same message ID remain idempotent, while a replay using a different ID is rejected. Unique provider-message IDs and row locking also protect ledger processing and replies. Shared-secret mode is disabled by default in production; set `EVOLUTION_WEBHOOK_ALLOW_SHARED_SECRET=true` only for a provider that cannot use JWT. Shared-secret requests are not timestamped, so a stolen secret remains replayable with a new ID. The provider JWT claims do not sign the request body, so protect the callback with TLS and a network allowlist where available.

## OCR data

Source documents remain on a private filesystem disk. A configurable retention period and owner-authorized deletion remove source files and sensitive OCR outputs; compact import status remains. Legacy source files are not assigned a guessed expiry, while their OCR outputs are scrubbed by age. The scheduler runs the cleanup command daily; confirm the scheduler is active in deployment.

## Deployment checks

Before deployment, verify the stateful domain, TLS, session storage, CSRF, and browser login/refresh/logout flows in staging. Keep production CORS origins HTTPS-only and confirm the edge supplies HSTS and an appropriate Content Security Policy.
