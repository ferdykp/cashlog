# Module Boundaries

## Backend today

| Capability | HTTP layer | Domain layer |
|---|---|---|
| Accounts and transactions | `AccountController`, `TransactionController`, request classes | `AccountService`, `TransactionService`, `TransactionRules` |
| Budgets and goals | Budget/goal controllers and requests | goal/budget services and `DecimalMath` |
| Assets | `AssetController` | `AssetService` |
| Shared finances | `SharedFinancialWorkspaceController` | `SharedFinancialWorkspaceService` |
| OCR | `ImportController` | `OcrService` |
| WhatsApp | integration and webhook controllers | `WhatsAppMessageProcessor` and integration services |

Keep HTTP validation/serialization at the boundary, ownership checks explicit, and multi-record workflows inside transactional services. Split large classes only at cohesive use-case boundaries.

## Frontend today

Route pages live in `resources/js/views`; reusable UI, composables, Pinia stores, API client, and pure utilities are in shared conventional folders. Feature-oriented folders are not yet implemented. Move a workflow incrementally and preserve route names, request ownership, and API contracts.
