# Security implementation notes

## Authentication and CSRF

- The first-party SPA uses Sanctum session cookies with Laravel CSRF middleware. Cookies are HttpOnly; production must set Secure and SameSite=Lax.
- The bearer-token `/api/login` flow remains for existing API clients. The SPA's successful cookie login clears its legacy localStorage token.
- Login routes are rate-limited. Protected application routes continue to require `auth:sanctum`.
- The SPA and API should be same-origin at `https://ledger.fdevsite.cloud`; credentialed CORS must use explicit allowed origins.

## Webhooks

The Evolution webhook supports either the existing provider-compatible `X-Ledger-Webhook-Secret` or optional provider-generated JWT auth via `EVOLUTION_WEBHOOK_JWT_SECRET`. JWT mode accepts HS256 only, verifies the signature and `iat`/`exp` (maximum 10-minute lifetime), and binds each token fingerprint to one provider message ID until expiry; retries with that same message ID remain idempotent, while a replay using a different ID is rejected. Unique provider-message IDs and row locking also protect ledger processing and replies. Shared-secret mode remains available for existing providers and is not timestamped, so a stolen shared secret remains replayable with a new ID. Configure JWT mode only after setting the same key in Evolution and Cashlog; never log or commit it. Rotate both sides together. The provider JWT claims do not sign the request body, so protect the callback with TLS and a network allowlist where available.

## OCR data

Source documents remain on a private filesystem disk. A configurable retention period and owner-authorized deletion remove only source files while preserving import metadata. Legacy files are not assigned a guessed expiry. Cleanup has a dry-run mode and must be enabled through the scheduler intentionally.

## Deployment checks

Do not deploy cookie auth until the stateful domain, TLS, session storage, CSRF, and browser login/refresh/logout checks pass in staging. Do not perform production balance reconciliation or delete user data from this code change.
