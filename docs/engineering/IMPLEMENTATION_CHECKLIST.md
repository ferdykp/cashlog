# Cashlog Modernization Implementation Checklist

State: in progress. Source: the engineering audit documents in this directory, verified against the active monorepo before each change. Production data and infrastructure are out of scope.

## Phase A — Safeguards and behavior contracts

- [x] Record baseline test/build/format results; production DB engine remains unverified (never read live secrets).
- [x] Add regression tests for exact money aggregation and transaction idempotency.
- [ ] Add balance-adjustment regression contract (gated by automatic review; no production behavior changed).
- [x] Remove authenticated API response caching; every page load reads current financial data from the server.
- [x] Add CI for the existing PHP and JavaScript/build suites.
- [ ] Add Playwright browser coverage only with isolated services/accounts; Playwright is not configured in this repository yet.

## Phase B — Confirmed correctness findings

- [ ] Remove silent balance update from ordinary account edits; preserve account creation/opening-balance compatibility and existing history.
- [ ] Add an explicit auditable balance adjustment only if existing domain semantics and tests support a safe contract; do not backfill production data.
- [x] Use exact decimal arithmetic for confirmed frontend money aggregations reviewed in this pass.
- [x] Make transaction idempotency compare category and normalized note; changed values conflict.

## Phase C — Security and data policy

- [x] Add compatible Sanctum SPA session-cookie flow while retaining the PAT API contract; staging rollout validation remains required.
- [x] Keep authenticated API response cache page-lifetime only; preserve token scoping, mutation/logout invalidation, and no shared SW API cache.
- [x] Add configurable OCR source retention, owner deletion, metadata preservation, dry-run cleanup, and scheduled cleanup for new imports; legacy imports remain unexpired.
- [ ] Verify WhatsApp provider signature capabilities before changing webhook verification.

## Phase D — Architecture, quality, and verification

- [ ] Refactor cohesive large backend/frontend units with route/component coverage and unchanged API/business contracts.
- [ ] Add production-engine concurrency integration configuration isolated from production.
- [x] Add CI checks for PHP tests/Pint and JS tests/Prettier/build without weakening existing tests.
- [x] Remove verified empty placeholders and unreferenced starter/scaffold assets; consolidate duplicate project-review notes.
- [x] Update architecture, module boundary, financial invariants, auth, freshness, database, testing, performance, deployment, and results docs to match implementation.
- [x] PHP/JS tests, production build, full-repository Pint, and full-repository Prettier checks pass locally.

## Guardrails

- No production deploy, infrastructure change, destructive migration, financial-history rewrite, or production data access.
- No TypeScript or unnecessary architectural frameworks.
- Stop/adjust any code change whose required financial semantics cannot be established from the current code/tests.
