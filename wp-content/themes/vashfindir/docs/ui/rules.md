# UI rules

## Source of truth
- `main.css` is the single source for tokens, section title/subtitle typography, and utilities (`.vf-section-pad-lg`).
- `landing.css` only defines section layout; it must not carry section title/subtitle typography.

## Sections
- Root class: `.vf-<section>` (example: `.vf-hero`).
- BEM elements: `.vf-<section>__*` (example: `.vf-hero__title`).

## Section typography
- Always use `.section-title` and `.section-subtitle` for section headings.
- Forbidden in `landing.css`: `font-size`, `font-weight`, `color` on `__title`/`__subtitle`.

## Components and utilities
- `.vf-section-pad-lg` for large vertical section padding.

## Usage examples
- FAQ: `.vf-faq` + `.section-title`/`.section-subtitle`.
- Hero: `.vf-hero` + `.section-title`/`.section-subtitle`, use `.vf-section-pad-lg` on the section.
- Control: `.vf-control` on controls or panels.
- Lead: `.vf-lead` + `.section-title`/`.section-subtitle`, avoid typography overrides in `landing.css`.
