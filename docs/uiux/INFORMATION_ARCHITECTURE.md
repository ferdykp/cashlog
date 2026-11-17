# Information architecture

| Destination | Role | Routes |
| --- | --- | --- |
| Home | Personal financial overview | `/dashboard` |
| Aktivitas | Searchable transaction history and edits | `/transactions`, `/transactions/:id/edit` |
| Catat | Start a financial entry | `/transactions/create`, `/scan` |
| Rencana | Budgets, goals, investments, bills | `/budget`, `/goals`, `/investasi`, `/bills` |
| Lainnya | Accounts, reports, shared workspace, categories, integrations, settings | `/accounts`, `/report`, `/kolaborasi`, `/categories`, `/settings/whatsapp`, `/settings` |

The desktop sidebar retains a complete route map. Mobile uses a bottom bar for the five top-level destinations and a sheet for the secondary route map. `/history`, `/budgets`, `/reports`, and `/assets` remain compatibility redirects. Authentication and recovery routes remain outside the authenticated shell.
