# Responsive QA

## Implemented safeguards

Mobile rules cover the 320px baseline, safe-area top/bottom padding, fixed shell clearance, two-column action sheet, compact dashboard metrics, and single-column dashboard content. Existing responsive rules handle form grids, dialogs, tables, charts, and long content. Reduced-motion and dark theme styles are included.

## Validation status

Playwright visited 14 authenticated routes (dashboard, activity, create, accounts, budgets, goals, investments, bills, reports, OCR, shared workspace, settings, WhatsApp, categories) at 320, 375, 390, 430, 768, 1024, 1280, and 1440px and found no document-level horizontal overflow. The first pass exposed report preset overflow at 320px and expanded hidden OCR file inputs; both were fixed, and the full matrix passed afterward. The mobile composer flow also passed through to the expense form.

Representative 390px screenshots, captured with the seeded empty-state account, are in `screenshots/`. Dark/light states were reviewed in implementation but have not received contrast-tool certification. Keyboard overlap and screen-reader behavior are not covered by this viewport matrix.
