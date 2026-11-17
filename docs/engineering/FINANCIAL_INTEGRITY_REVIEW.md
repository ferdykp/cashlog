# Cashlog Financial Integrity Review

Audit date: 2026-11-17. No financial calculations or database records were changed. This document is based on code/schema review; tests were not run.

## Confirmed findings

### FI-01 — Direct account balance updates bypass transaction history

- **Severity:** High.
- **Location:** `app/Http/Requests/Account/UpdateAccountRequest.php:19-25`; `app/Http/Controllers/AccountController.php:47-55`; `app/Services/AccountService.php:34-38`.
- **Evidence:** Update accepts `balance`; service writes validated data directly to the account row without a matching transaction/adjustment record.
- **Why it matters:** The stored/displayed balance can no longer be explained by transaction history. Reports and reconciliation may use different sources of truth.
- **Recommended solution:** Route balance changes through explicit auditable adjustment entries or a well-defined opening-balance event, atomically updating the materialized balance and history. Preserve non-balance account editing. Define migration/reconciliation policy for current accounts first.
- **Modification risk:** High. Existing balances and workflows may depend on the current behavior; automated backfill can create misleading history.
- **Required regression tests:** Balance cannot be silently overwritten; adjustment and entry are atomic; all account transactions reconcile; concurrent adjustment; rollback; user ownership; historical report compatibility.

### FI-02 — Frontend aggregates use floating-point arithmetic

- **Severity:** Medium (presentation accuracy; server persistence remains decimal-based).
- **Location:** `resources/js/stores/goal.js:11-31`; `resources/js/stores/budget.js:11-35`; `resources/js/views/TransactionHistory.vue:127-154`; exact helper exists at `resources/js/utils/decimal.js`.
- **Evidence:** The cited aggregates parse monetary values into JavaScript `Number` and sum/subtract them. Other parts of the frontend already use exact decimal/BigInt helpers.
- **Why it matters:** Binary floating-point can produce fractional-cent rounding errors for some values and totals. This is primarily a displayed summary risk and is not evidence that server-stored amounts are corrupted.
- **Recommended solution:** Reuse the existing decimal helper for arithmetic and format only at the UI boundary. Avoid changing rounding policy without verifying the API's decimal contract.
- **Modification risk:** Low to medium. Different rounding/formatting may alter visible totals and tests/expectations even when underlying amounts are unchanged.
- **Required regression tests:** Decimal addition/subtraction with 0.1/0.2, large totals, negative/net values, zero/empty data, and parity with API totals.

### FI-03 — Idempotent transaction replay comparison omits persisted fields

- **Severity:** Medium.
- **Location:** `app/Services/TransactionService.php:60-70,92-102`.
- **Evidence:** Replay comparison checks core identity/amount/date fields but persistence also includes category/note; those are not all compared before returning an existing transaction.
- **Why it matters:** Reusing a key for a semantically changed payload may be treated as success and return a different transaction than the caller intended.
- **Recommended solution:** Canonicalize and fingerprint all semantically relevant request fields; decide explicitly which metadata fields do not affect identity.
- **Modification risk:** Medium. Existing clients may send equivalent optional values differently; conflict behavior could change.
- **Required regression tests:** Exact retry, normalized default retry, changed category/note, changed related account/goal, simultaneous duplicate requests.

## Controls and strengths observed

- DB amount columns use decimal types; `composer.json` includes BCMath and `app/Support/DecimalMath.php` provides exact decimal operations.
- `app/Services/TransactionService.php:57-105,260-284` uses database transactions and ordered account row locks for financial changes/transfers.
- Financial ecosystem migrations add unique/idempotency constraints (for example `database/migrations/2026_11_02_000001_create_financial_ecosystem_tables.php:63-67`).
- Transaction rules validate account/category ownership in application code (`app/Rules/TransactionRules.php:14-23`).
- Feature suites cover several finance workflows and ecosystem flows under `tests/Feature`.

## Suspected risks / verification gaps

- SQLite in-memory test configuration (`phpunit.xml`) does not demonstrate MySQL/PostgreSQL row-lock semantics or behavior under real concurrent requests.
- FKs prove referenced rows exist, but do not by themselves prove account/category/transaction rows belong to the same user. Existing request/service checks reduce risk; no bypass was confirmed. Add DB-level tenant-consistency constraints only after checking current data and migration compatibility.
- No current production ledger reconciliation report was available. Validate account balances against transaction history before introducing a canonical ledger.
- Asset valuation includes manual RDPU values/history; valuation policy and rounding should be reviewed with product/domain expectations before calculation changes.
