# Mobile navigation

The persistent navigation is Home, Aktivitas, Catat, Rencana, Lainnya. Active links expose `aria-current="page"`; Catat and Lainnya expose expanded state and open dialogs. The composer has expense, income, transfer, and scan choices; the transaction choices pass the existing `type` query string to `/transactions/create`. Route changes close open sheets. The bottom bar and top header account for safe-area insets and use the existing dialog focus/escape directive.

Desktop keeps the full sidebar. At 1024px and below the sidebar is replaced by the mobile shell. Route history and browser back are handled by Vue Router as before.
