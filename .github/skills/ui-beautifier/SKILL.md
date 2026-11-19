---
name: ui-beautifier
description: "Improve and polish web UI: modernize PHP/HTML pages, refine layout, colors, typography, responsive behavior, and interactions while preserving application behavior."
argument-hint: "Describe the page or interface you want improved"
user-invocable: true
---

# UI Beautifier

Improve web interfaces with clear visual hierarchy, consistent spacing, responsive layouts, and accessible interactions.

## When to Use

- Make a page or application look more modern or polished.
- Improve layout, typography, colors, spacing, responsiveness, or UI consistency.
- Refine a PHP/HTML interface without changing what the application does.

## Workflow

1. Inspect the target page, related stylesheets, shared components, and the project's existing styling approach before editing.
2. Identify the specific visual issues and make a small, coherent improvement plan.
3. Preserve PHP behavior, form fields, routes, database operations, permissions, and existing user flows. Avoid changing backend logic while adjusting markup or styles.
4. Follow the project's established styling conventions. Reuse its CSS files, variables, and components where possible; do not introduce Tailwind or another styling dependency unless the project already uses it or the user requests it.
5. Prefer semantic HTML, mobile-first layouts, readable typography, balanced whitespace, restrained shadows, and consistent border radii.
6. Include visible hover, focus, and disabled states where relevant. Keep keyboard navigation and accessible labels intact.
7. Make responsive behavior work at narrow and wide viewport sizes. Prefer Flexbox or Grid for layout where appropriate.
8. Run the smallest relevant checks and inspect the final diff to ensure the visual changes did not alter application behavior.

## Design Guidance

- Use the project's existing color palette and design tokens; add or adjust tokens only when they improve consistency.
- Establish hierarchy with headings, spacing, contrast, and restrained font weights.
- Keep actions visually distinct and easy to find, with clear focus states.
- Ensure tables, forms, navigation, dialogs, and cards remain usable on mobile.
- Avoid decorative elements that reduce contrast, crowd content, or interfere with the workflow.

## Output

Implement requested UI changes in the relevant project files. Summarize the main visual improvements and the checks performed; do not dump entire files unless the user asks for them.
