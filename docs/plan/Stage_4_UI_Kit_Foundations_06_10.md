# UI-kit 06–10 Implementation Plan

> **For agentic workers:** Use superpowers:executing-plans to implement this plan task by task. Steps use checkbox syntax for tracking.

**Goal:** Restore reference sections 06–10 of `/ui-kit` while preserving the public site and the existing production components.

**Architecture:** Twig renders static technical examples from the reference. Page-scoped styles live in the single website `app.css`; existing semantic tokens and icon components supply visual values. No new business logic, external font, icon CDN, or production interaction is introduced.

**Tech Stack:** Symfony/Twig, Tailwind v4 build, CSS tokens, PHPUnit, Playwright.

**Spec:** `design-system-inbox/Design System.dc.html`, sections 06–10; `docs/plan/UI_Kit_Reference_Audit_00_05.md`.

## Global Constraints

- Keep `/ui-kit` noindex, one H1, URL and SEO layout intact.
- Reference geometry and composition are authoritative; local Inter and Manrope remain the only fonts.
- Static amounts, parties and products are visibly demonstrational; no unverified company claim is published.
- No inline style or JavaScript in Twig; use canonical CSS variables in `site/assets/styles/website/app.css`.
- Do not alter ordinary public pages, production Navbar, `/ui-kit/sections`, Admin, data schema or deploy configuration.
- Preserve section IDs and order 06–10; do not mark 11–15 or 17–28 complete.

## Review Focus

- At 320 px, dense tables and 12-column grid must scroll inside their own region without widening the document.
- Static focus/disabled/loading examples must not masquerade as active production controls.
- Icon samples must render from published local SVGs; no external network dependency.
- Shadow and radius labels must correspond to canonical CSS tokens, not invented values.
- Existing website routes and assets must remain unaffected.

## Task 1: Baseline and reference data

- [x] Read exact HTML and source arrays for sections 06–10; record counts and labels in the verification report.
- [x] Capture baseline section screenshots at 320, 375, 768, 1024 and 1440 px with loaded font and asset diagnostics.
- [x] Add focused functional assertions for section structure and sample counts; run them to see the initial failure.

## Task 2: Radii, spacing and elevation

- [x] Render section 06 with seven radius examples, ten component mappings, nested radius example, chart edge example and six rules.
- [x] Render section 07 with ten spacing rows, six semantic roles, proximity example, 12-column grid and four breakpoint rows.
- [x] Render section 08 with four shadow levels, four dark surface levels and four rules.
- [x] Add only page-scoped CSS for these examples, using existing radius, shadow, ink and crimson tokens; check at 320 px.

## Task 3: Icons and states

- [x] Render section 09 with 12 local icon samples, size examples, social icon guidance and reference rules; keep icon source local.
- [x] Render section 10 with reference control state matrix and six behavior rules. Clearly label noninteractive demonstrations; use production component partials where the demonstration represents an actual control.
- [x] Check keyboard focus and accessible names; no false interactive affordance in static examples.

## Task 4: Verify and report

- [x] Run `make assets`, update the release asset hash, and run `make assets-check` and `make ci` in isolated Compose.
- [x] Capture comparable reference/current screenshots for each section on all five widths and inspect overflow, fonts and browser errors.
- [x] Smoke-check `/`, `/services`, `/ui-kit/sections`, and `/ui-kit`; update rows 06–10 in the audit and write a verification report.
- [x] Self-review the full diff and obtain independent Claude Code review; resolve justified blocker/high/medium findings.
- [x] Commit only stage files on `feature/ui-kit-reference-06-10`; do not deploy this next stage without separate instruction.
