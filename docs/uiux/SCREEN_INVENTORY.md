# Screen inventory

All screens continue to use their existing view components and business actions. Shared layout, tokens, and responsive treatments are applied centrally.

| Screen / route | Purpose | Change and functional status |
| --- | --- | --- |
| Dashboard `/dashboard` | Financial overview | Navy balance hero, high-contrast metrics/actions; existing data and charts retained. |
| Transactions `/transactions` | Search, filter, browse, edit | Shared typography, surfaces, controls, mobile content spacing; existing filters and pagination retained. |
| Entry `/transactions/create` | Expense, income, transfer | Composer now preselects existing supported `type`; form/save semantics retained. |
| Accounts `/accounts` | Wallet and account management | Shared responsive shell and surface/control tokens; existing account workflows retained. |
| Budgets `/budget` | Spending limits and health | Shared route styling; existing budget logic retained. |
| Goals `/goals` | Savings goals and funding | Shared route styling; goal workflow/data retained. |
| Investments `/investasi` | Gold and mutual-fund assets | Shared styling; existing asset workflow retained. |
| Bills `/bills` | Obligations | Shared styling and mobile form controls; existing bill logic retained. |
| Reports `/report` | Period analysis and charts | Shared styling; chart behavior/data retained. |
| OCR `/scan` | Receipt import/review | Shared styling; upload/OCR workflow retained. |
| Shared `/kolaborasi` | Shared workspace | Shared styling; invitation and workspace actions retained. |
| Settings `/settings` | Account preferences | Shared styling; existing settings retained. |
| Integrations `/settings/whatsapp` | WhatsApp connection | Shared styling; existing connection flow retained. |
| Categories `/categories` | Category management | Shared styling; existing actions retained. |
| Auth `/login`, `/register`, `/forgot-password`, `/reset-password` | Account access | Existing independent auth layouts preserved. |

Aliases `/history`, `/budgets`, `/reports`, `/assets` continue redirecting. Screen-by-screen browser validation has not yet been performed; see `RESPONSIVE_QA.md`.
