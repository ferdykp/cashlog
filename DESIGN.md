# Ledger interface direction

## Product

Ledger is a personal finance workspace for people in Indonesia. Its screens should help users answer three practical questions quickly: how much is available, which account holds it, and what changed recently.

## Identity

- **Personality:** calm, direct, dependable, quietly tactile.
- **Palette:** forest green `#08796D` for primary actions and current state; deep green `#075F52` for the focused account card; sage `#E1F1EB` for secondary account cards; paper `#FFFFFF`; canvas `#F5F7F4`; ink `#172E2B`; muted ink `#64766F`; income `#16754B`; expense `#B8403E`.
- **Typography:** Inter for labels, forms, and long reading; Space Grotesk only for high-value totals where its numeral shapes improve scanning.
- **Surfaces:** matte and mostly flat. Use a thin border to group information; reserve a small shadow for elevation that affects interaction.

## Composition

- **Mobile dashboard:** greeting and period; total balance; a horizontally swipeable, physically layered account deck; quick transaction actions; recent activity. Keep the deck as the focal object and show a narrow edge of the next accounts to make the swipe affordance visible.
- **Desktop dashboard:** retain the same account deck as the focal object, with cash-flow context and activity beside or below it according to available width.
- **Feature pages:** each page begins with a plain title and the action users need there. Forms stay in a single readable column; data lists favor aligned rows and dividers; summaries use cards only when they compare or group related values.
- **Navigation:** desktop groups all product areas by task. Mobile keeps the five most frequent destinations visible and puts secondary destinations in the desktop menu or a clearly named secondary area.
- **Account cards:** the front card uses Ledger green to identify the selected account; subsequent cards use progressively quieter sage and paper surfaces. The stack represents real accounts and balances, not decorative payment cards.
- **Reference use:** Pesse informs the offset account deck; ByteTown informs the mobile flow. Neither screen is copied as a whole, so the account stack remains recognizably Ledger.

## Purpose of the visual choices

- **Green:** preserves Ledger's identity and marks money actions, active navigation, and positive movement.
- **Layered account deck:** lets users preview that they have multiple accounts and interact with the currently selected account using touch, keyboard, or click.
- **Flat paper surfaces and fine borders:** keep dense financial information readable and prevent every section from competing with account balances.
- **Restrained motion:** motion is limited to user-triggered card changes, dialog transitions, and clear feedback.

## Dials

- **ENERGY 2: balanced:** clear focal values with restrained color and no decorative hero treatment.
- **RHYTHM 2: consistent with a few breaks:** forms and lists remain predictable; the account deck and dashboard use a stronger composition.
- **MOTION 1: calm:** no ambient loops or scroll choreography; animate only a meaningful state change.
