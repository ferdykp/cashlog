# Backend Standards

- Keep private APIs behind `auth:sanctum` and scope records to the current user or authorized shared workspace.
- Use Form Requests for HTTP validation/authorization, Resources for stable response shape, and controllers for coordination.
- Use injected services for cohesive business workflows; avoid splitting solely by line count.
- Wrap multi-record financial writes in `DB::transaction`; lock affected accounts in deterministic ID order as `TransactionService` does.
- Use decimal columns and `DecimalMath`/BCMath; do not calculate money with PHP floats.
- Keep errors actionable without logging secrets, tokens, OCR document content, or financial payloads.
- Add a regression test before changing financial rules or API contracts.

Existing code does not yet apply every practice uniformly; see the audit and results documents.
