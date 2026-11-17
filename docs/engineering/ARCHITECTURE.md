# Cashlog Architecture

Cashlog is a Laravel modular monolith serving a Vue single-page app and JSON API from one deployment. Backend code uses conventional `app/Http`, `app/Services`, `app/Models`, `app/Rules`, and `app/Support` folders. Frontend code uses `resources/js/views`, `components`, `composables`, `stores`, `router`, `lib`, and `utils`.

API routes are defined in `routes/api.php`; private routes use `auth:sanctum`. Controllers coordinate HTTP, Form Requests validate selected workflows, Resources serialize selected responses, and services own multi-record financial workflows. Vue Router lazy-loads pages; Pinia stores share state and the Axios client centralizes API calls and cache/auth behavior.

Current code is not uniformly feature-folder organized. Large services and views remain; see `ARCHITECTURE_AUDIT.md` and `REFACTOR_RESULTS.md`. This document describes existing code, not a target architecture.
