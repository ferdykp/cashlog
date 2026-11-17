# Cashlog Refactor Roadmap

This roadmap is based on `ARCHITECTURE_AUDIT.md`, `SECURITY_REVIEW.md`, `FINANCIAL_INTEGRITY_REVIEW.md`, `FRONTEND_REVIEW.md`, and `TESTING_GAPS.md`. It is incremental; no refactor or production change is included in this audit.

## Phase 0 — Establish safeguards

1. **Add financial regression contracts.** Define account reconciliation and transaction idempotency semantics, then test them before touching services.
2. **Add browser coverage for data freshness.** Exercise cold/warm reload, offline, auth expiry, mutations, logout, user switch, and route entry across pages that display data.
3. **Add CI checks.** Run PHP tests/Pint and JS tests/build/format check in isolated CI. Do not point automated tests at production.

**Exit condition:** Baseline tests capture current contracts and CI is reproducible.

## Phase 1 — Correctness and data freshness

### P1.1 Replace silent balance editing

- Introduce an auditable balance adjustment/opening event; keep ordinary account profile updates separate.
- Establish how existing balances reconcile to transaction history before data migration.
- Roll out behind an explicit API/UI contract and monitor reconciliation discrepancies.

**Risk:** High; requires legacy data analysis and user workflow review.  
**Tests:** `TESTING_GAPS.md` T-01 and production-engine concurrency tests.

### P1.2 Define authenticated response cache policy

- Decide whether finance pages may render up to 30-minute-old tab data while refreshing.
- If stale-while-revalidate remains, replace the global amber banner with localized freshness indication and preserve an offline warning; if freshness is mandatory, render loading placeholders until server data arrives.
- Retain auth scoping, mutation invalidation, and service-worker exclusion for API responses.

**Risk:** Medium; network-first increases reload latency/API load, while hiding a banner without changing data behavior weakens transparency.  
**Tests:** FE-01 and T-03 scenarios.

### P1.3 Canonicalize idempotency payload

- Define meaningful transaction fields and compare/hash a normalized payload.
- Keep retries returning the first result only when the canonical payload matches.

**Risk:** Medium; client default representations may differ.  
**Tests:** F-03.

## Phase 2 — Browser security and retention

1. Evaluate migration from localStorage bearer tokens to first-party Sanctum cookie sessions. Plan CSRF, CORS, PWA, API-client compatibility, session revocation, and rollout before implementation.
2. Reassess client-side persistence of finance responses; minimize fields and TTL or use memory-only cache if speed requirements allow.
3. Define OCR document retention, deletion, and backup policy.
4. Use provider-supported webhook signature/timestamp validation if available; preserve dedupe and retry semantics.
5. Verify deployed CSP, HSTS, TLS, and storage permissions with a separate production configuration review.

**Risk:** High for authentication, medium for data retention/webhook changes.  
**Tests:** `SECURITY_REVIEW.md` findings and browser auth flows.

## Phase 3 — Monetary consistency and database defense in depth

1. Replace remaining frontend `Number` money aggregates with the existing exact decimal utility; confirm rounding/display semantics with API values.
2. Add production-engine tests for ordered row locks, transfers, duplicate submissions, and rollback.
3. Audit current ownership relationships and data before considering composite same-user DB constraints. Add constraints only after a non-destructive integrity report and migration plan.
4. Establish a recurring read-only balance reconciliation report before considering a canonical ledger migration.

**Risk:** Low-medium for presentation arithmetic; high for DB constraints or ledger migration.  
**Tests:** financial decimal fixtures, tenant ownership negatives, concurrency suite.

## Phase 4 — Maintainability and accessibility

1. Extract focused sections from `FinancialWorkspace.vue` and `TransactionCreate.vue`; preserve route contracts and request timing.
2. Split oversized services only around cohesive use cases with explicit transaction boundaries.
3. Add automated accessibility checks and targeted keyboard/screen-reader coverage for forms, dialogs, charts, and cache status.
4. Add request-count/performance assertions for data pages to prevent duplicate fetches and loading regressions.
5. Keep the primary transaction and navigation flows simple on small screens before adding more product features.

**Risk:** Medium; lifecycle/request timing may shift during extraction.  
**Tests:** browser/component regression suite and request-count checks.

## Prioritization

| Priority | Work | Reason |
|---|---|---|
| Highest | Balance change auditability and financial invariant tests | Directly affects whether stored balances are explainable by history. |
| High | Authenticated cache freshness decision and E2E coverage | Explains the visible “data sesi terakhir” message and prevents stale-data UX from being accidental. |
| High | Token/session storage threat-model decision | Limits impact of any same-origin script execution. |
| Medium | Idempotency payload completeness and concurrent DB tests | Improves safe retries and financial write behavior under load. |
| Medium | OCR retention and webhook signing | Reduces privacy and replay exposure. |
| Later | View/service decomposition and accessibility automation | Improves maintainability and regression detection after behavior contracts exist. |

## Guardrails

- No financial calculation changes before regression tests demonstrate existing rules and intended rounding.
- No destructive database operation or production infrastructure change as part of this roadmap.
- Preserve API contracts where possible; use reviewed migrations for any schema changes.
- Measure user-visible behavior and production metrics before claiming improvement.
