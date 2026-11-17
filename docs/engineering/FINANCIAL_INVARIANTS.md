# Financial Invariants

## Current transaction rules

- Currency columns use `DECIMAL(15,2)`; Account and Transaction API Resources expose decimal strings.
- Income increases an account, expense decreases it, and transfer applies equal opposite effects to source and destination.
- Transaction create/update/delete use database transactions and lock affected account rows in ascending ID order.
- Transaction account/category references are validated against the authenticated user.
- An idempotency retry must match type, account(s), category, amount, date, goal obligation, and normalized note. Changed category/note now produces HTTP 409.
- Frontend aggregates touched in this pass use exact decimal string helpers.

## Unresolved account balance contract

Account creation accepts an initial balance, and ordinary account update still accepts a direct balance edit that has no financial adjustment event. This behavior remains unchanged. Before replacing it, define account-type semantics and a reconciliation equation, add passing feature tests, design a reviewed additive audit record/API/UI, and distinguish legacy opening snapshots. Do not infer or rewrite historical production transactions.

The prior automated review rejected an account balance/history schema and behavior patch because its regression coverage had not passed. That rejection was not bypassed. Keep this behavior unchanged until the adjustment contract and tests are reviewable and passing; no production balance migration or reconciliation has run.
