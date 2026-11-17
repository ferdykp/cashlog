# Accessibility

Navigation landmarks are labeled. Current destinations expose `aria-current`; sheets expose dialog semantics, titles, modal state, and expanded state from their triggers. Icon-only profile and close controls have accessible names. Shared focus indicators use cyan against both themes, and reduced motion is respected. Status colors are paired with existing labels/icons.

The browser QA verified the quick-action dialog opens, its actions remain visible, and expense selection reaches the labeled amount field. The enclosing overlay is the single modal dialog (the sheet itself no longer creates a nested dialog). Automated accessibility scanning, full keyboard traversal, screen-reader validation, and contrast-tool certification remain pending, especially for inherited component colors in dark mode.
