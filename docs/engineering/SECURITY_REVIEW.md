# Cashlog Security Review

Review date: 2026-11-25. Repository review only; production configuration and data were not inspected.

## Changes made

- The browser SPA now uses Sanctum session cookies and CSRF protection only. It clears old browser bearer tokens and no longer persists or sends them. The bearer endpoint remains available for external API clients.
- Production session cookies default to Secure, and the cleartext production site is no longer a credentialed CORS origin.
- Password reset and password change revoke Sanctum tokens, database-backed sessions on other devices, and remembered-device tokens.
- NPM and Composer lockfiles were refreshed to patched dependency versions based on the supplied server audit. `npm audit` and `composer audit` reported no remaining advisories after the updates.
- Share Target receipt files older than 24 hours are pruned on app navigation or service-worker activation, and all pending share files are removed when the user logs out.
- OCR source deletion now also deletes raw OCR text, parsed financial details, and confidence. Scheduled cleanup scrubs old legacy results by age while preserving compact import status.
- Shared-secret WhatsApp webhook authentication is disabled by default in production. Provider JWT authentication is required unless the operator explicitly enables the legacy shared-secret mode.

## Controls observed

- Private API routes require Sanctum authentication; owner and membership checks scope financial records and shared workspaces.
- Cookie-authenticated writes use Laravel CSRF middleware. Login, password reset, OTP, import, and webhook endpoints have rate limits.
- Uploaded OCR images are size- and type-validated, processed without shell interpolation, and stored on a private disk.
- CSV export escapes spreadsheet formula prefixes. The Vue source has no direct `v-html`, `innerHTML`, `eval`, or `new Function` sink in the reviewed code.

## Deployment checks still required

- Set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, a valid `APP_KEY`, and a private session/cache store. Confirm HTTPS and HSTS at the edge.
- Keep CORS limited to the HTTPS application origin and configure the matching Sanctum stateful domain.
- Configure Evolution JWT on both provider and application. Avoid enabling `EVOLUTION_WEBHOOK_ALLOW_SHARED_SECRET` unless provider limitations require it. The JWT does not sign the body, so protect the callback with TLS and an IP allowlist where available.
- Confirm the scheduler is active so OCR file and result retention runs. Review database, log, and backup retention separately.
- Confirm CSP, web-server file access rules, database network policy, secret rotation, and backup encryption in deployment infrastructure.

No production `.env` values or user records were inspected. Browser and integration tests were not run as part of this review.
