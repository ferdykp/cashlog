# Cashlog product experience

## Direction

Cashlog uses a precision-finance visual language: electric blue for actions, emerald for inflows and success, red for outflows and serious errors, and cyan for selected data. Light surfaces use cool white and slate; dark surfaces use deep navy and raised navy panels. Existing Indonesian copy, financial semantics, authentication, stores, and API flows remain the source of truth.

## Mobile priorities

The mobile shell now provides five persistent destinations: Home, Aktivitas, Catat, Rencana, and Lainnya. Catat opens a four-choice composer for expenses, income, transfers, and receipt scans. Existing routes and transaction forms handle the work after selection. The app keeps its route-level lazy loading, lazy chart loading, PWA registration, and existing form semantics.

## Before and after

Before: the mobile bar exposed Home, Aktivitas, Laporan, and Menu, while quick entry was a direct link. The palette was muted green and the mobile header was a generic Ledger lockup.

After: navigation follows the requested five task areas, the composer is a contextual bottom sheet, branding says Cashlog, and the shared color, surface, focus, form, and dashboard hero treatments use high-contrast blue and navy tokens.

## Scope and limits

This change is a broad shell and shared visual redesign over the existing screens. It does not alter financial calculations or API contracts. It does not add inferred Available to Spend figures, synthetic insights, dashboard widget persistence, or new backend features. Those need validated formulas and product data contracts before they belong in the live financial UI.
