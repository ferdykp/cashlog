# Component system

The current shared component system consists of the app shell, desktop sidebar, mobile bottom navigation, quick-action sheet, menu sheet, brand mark, reusable form primitives, and existing financial components. `precision.css` applies shared visual tokens and states without replacing business-specific view components. The mobile quick-action sheet is keyboard dismissible through the existing `v-dialog` directive, exposes dialog labels, closes on route change, and keeps each action connected to its current route.

Further consolidation into components such as `AmountInput`, `TransactionRow`, and `GoalCard` should follow actual duplication across views; the redesign does not move financial logic into generic abstractions.
