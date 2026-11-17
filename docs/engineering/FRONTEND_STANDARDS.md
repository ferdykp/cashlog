# Frontend Standards

- Use Vue 3 Composition API and JavaScript; this project does not use TypeScript.
- Keep route pages focused on composition, stores on shared state, and composables on reusable stateful workflows.
- Use the shared Axios client for API requests so authentication, invalidation, and error behavior stay consistent.
- Preserve API money values as decimal strings. Use `resources/js/utils/decimal.js` for calculations and `formatRupiah` only for display.
- Never put authenticated finance responses in shared Service Worker caches. Browser response caches must be auth-scoped, invalidated on mutation/logout/session change, and make stale values distinguishable.
- Cancel or supersede obsolete requests when filters change or a page is disposed.
- Preserve keyboard access, focus behavior, responsive layout, and reduced-motion support.
- Avoid wide formatting-only diffs during feature changes.
