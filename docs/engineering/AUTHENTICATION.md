# Authentication

Private API routes use Sanctum. Login currently issues a personal access token; `resources/js/stores/auth.js` stores it in `localStorage` and the shared Axios client sends a Bearer header. Axios uses `withCredentials: false`. Logout attempts server revocation and always clears local browser state.

JavaScript-readable token storage increases the impact of same-origin script execution. A first-party HttpOnly cookie migration is not included: it must first validate production stateful-domain/session settings, CSRF behavior, PWA behavior, and external API clients. Preserve bearer compatibility during any staged migration; do not invalidate existing sessions/integrations unexpectedly.

Never log tokens or cookies. Test logout, expiry/revocation, session rotation, account switching, CSRF for cookie writes, and cross-user authorization before rollout.
