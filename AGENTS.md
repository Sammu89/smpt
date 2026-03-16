## AGENTS.md

## AGENT QUICK START (Read First)
1. Always follow the Decision Order (section below).
2. Never write CSS/JS/PHP until you have proved reuse is impossible.
3. Inspect first (DevTools + codebase search + logs).
4. Prefer hooks → existing classes/variables → minimal extension → new code (last resort).
5. Validate every change with Playwright before considering it done.

## Project Mission

This website is a WordPress project built on **GeneratePress (parent theme)** with **GeneratePress Child** as the active child theme.

Agents must customize the site by extending the parent theme architecture, not bypassing it. Favor maintainability, update safety, and low-friction customization that remains compatible with future GeneratePress updates.

The objective is not merely to make something work. The objective is to make it work in the most native, reusable, and least invasive way possible.

## Core Development Philosophy

- Prefer existing GeneratePress settings, options, layout controls, and hook-based extension points before writing custom code.
- Treat the child theme as an extension layer, not a dumping ground.
- Prefer hooks, Hook Elements, and theme-native mechanisms over template overrides.
- Use CSS/JS/PHP only when the goal cannot be achieved cleanly via settings, hooks, or existing project architecture.
- Use template overrides only as a last resort.
- Never edit parent theme files.
- Avoid brittle hacks, specificity wars, and unnecessary `!important`.
- Reuse and respect existing project architecture, naming, and styling conventions.
- Before writing any new CSS, inspect and reuse the existing child-theme styling system: variables, utility classes, component classes, shared patterns, and naming conventions.
- Do not create new CSS rules, classes, or tokens when an existing pattern can be reused, extended, or slightly adapted safely.
- Prefer extending existing components over creating parallel variants with overlapping responsibility.
- Avoid one-off styling unless the requirement is truly isolated and cannot be expressed through an existing pattern.

## Default Assumption for Styling Work

The default assumption is that the needed styling probably already exists in some reusable form.

Agents must try to prove reuse is insufficient before adding new CSS.

## Decision Order / Implementation Hierarchy

Before making any change, follow this strict order:

1. Check whether the result can be achieved with existing GeneratePress settings/options.
2. Check whether the result can be achieved by reusing existing child-theme code, including:
   - CSS variables/tokens
   - utility classes
   - component classes
   - existing selectors/patterns
   - existing PHP helpers/hooks
3. If needed, adjust placement using hooks/Hook Elements/WordPress hooks before introducing new styling or markup changes.
4. If still not possible, extend existing child-theme CSS/JS/PHP in the most minimal and scoped way.
5. Only create new CSS/JS/PHP when reuse or extension is not sufficient.
6. Only if unavoidable, use a selective template override or deeper customization.
### Example – “I want a red badge on the new post type”
1. Check GeneratePress Elements → no.
2. Search child theme for `.badge`, `.pill`, `.status` classes and `--color-accent` variable → found.
3. Reuse `.badge` + modifier class instead of new CSS.
4. If truly missing, extend the existing component, never create `new-badge-red`.


## Role of the Child Theme

Use the child theme for:

- Scoped styling
- Small behavior scripts
- Custom helper functions
- Hook callbacks
- Integration logic
- Minimal structural overrides only when required

Avoid in the child theme:

- Casual copying of parent templates
- Re-implementing parent behavior without clear need
- Large duplication of GeneratePress capabilities
- Creating parallel systems when an existing project pattern already solves the problem
- Dumping unrelated or one-off code without architectural justification

## Mandatory Pre-Change Inspection

Before writing code, agents must inspect the current implementation:

- Review relevant GeneratePress options and hook opportunities.
- Search the codebase for existing related patterns, classes, variables, selectors, helper functions, and components.
- Inspect the rendered DOM/CSS in DevTools before adding or changing styles.
- Determine whether the issue is:
  - already solvable with existing settings/classes
  - a cascade/specificity issue
  - a markup placement issue
  - a true gap requiring new code

Do not add new CSS or markup patterns until this inspection is done.

## Tool Usage Policy

Available tools:

- Playwright
- Chrome browser / DevTools MCP
- Local WordPress/PHP logs

Use Playwright for:

- User-flow validation
- Page interaction checks
- Breakpoint/responsive checks
- Regression checks
- Screenshots and visual captures
- Visibility/placement verification
- Practical behavior validation after changes

Examples:

- Verify hooked content renders in the intended hook location.
- Validate nav/menu behavior across desktop/mobile.
- Capture desktop/mobile screenshots and hover-state screenshots for review.
- Check forms and key UI flows.

When screenshots are needed, use Playwright by default instead of Chrome DevTools MCP.

Use Chrome DevTools MCP for:

- DOM/CSS inspection
- Layout/spacing analysis
- CSS cascade/override conflicts
- Console/runtime JS errors
- Performance and network inspection

Examples:

- Find which parent rule overrides child CSS.
- Diagnose container/padding issues.
- Investigate broken script execution or slow render paths.
- Confirm whether an existing class, variable, or component already provides the needed styling.

Use local WordPress/PHP logs for:

- PHP warnings, notices, and fatals
- Runtime issues reproduced locally
- Custom application logs already written by the mu-plugin
- Quick verification of whether a backend path executed
- Local debugging without external services

Examples:

- Inspect `wp-content/debug.log` after reproducing a backend issue.
- Confirm a custom hook or access-control branch logged as expected.
- Correlate a browser action with a recent PHP warning or fatal.

## Standard Workflow for Agents

1. Inspect before coding.
2. Review relevant GeneratePress settings, hooks, and existing child-theme code.
3. Search for reusable existing patterns before proposing new code.
4. Choose the least invasive implementation path.
5. Reuse or extend existing code whenever possible.
6. Make scoped, minimal changes only where reuse is insufficient.
7. Validate in browser/tooling (Playwright and/or DevTools as appropriate).
8. For backend/runtime issues, check local logs before adding new instrumentation or guessing.

## CSS Reuse Rules

When working on styling or layout:

- Reuse existing design tokens, variables, spacing scales, radius values, and typography patterns.
- Reuse existing component classes and utility patterns before creating new selectors.
- Prefer modifying or extending an existing component pattern over introducing a duplicate variant.
- Do not introduce a new class or selector if an existing one already expresses the same role.
- Do not hardcode new visual values if equivalent project tokens already exist.
- Do not use `!important` unless there is a documented and unavoidable reason.
- Do not solve local layout issues with broad global overrides.
- Do not create new CSS just because it is faster than understanding the current architecture.
- Do not create a new component variant when the change is really a state, modifier, or small extension of an existing component.

## Practical Expectations Before Adding New CSS

Before adding new CSS, agents must:

- Search for similar components in the child theme.
- Search for existing CSS custom properties/variables used for similar styling.
- Search for spacing, card, button, badge, pill, form, grid, and container patterns already in use.
- Check whether the markup already includes reusable classes before adding new ones.
- Inspect whether the problem is caused by existing cascade, inheritance, layout structure, or hook placement rather than lack of styles.
- Confirm that extending an existing rule or component is not cleaner than introducing a new one.

## Rules for Design / Layout Tasks

- First check if GeneratePress settings already support the requested visual result.
- Then inspect whether the child theme already contains reusable styles, variables, utilities, or components for the requested result.
- Prefer layout/container/spacing/typography controls and hook placement before custom CSS.
- Inspect current DOM/CSS with DevTools before writing styles.
- Treat new CSS as the last styling option, not the default fallback.
- Validate responsive behavior with Playwright across key viewports.
- Prefer solving structural placement problems structurally rather than with cosmetic CSS hacks.

## Rules for Functional / Behavioral Tasks

- Before building custom features, check whether hooks or native WordPress/GeneratePress mechanisms already solve it.
- Reuse existing project helpers, patterns, and abstractions before adding new ones.
- Keep JavaScript minimal and tightly scoped.
- Keep PHP modular, explicit, and easy to trace.
- Validate actual user behavior with Playwright, not just static code inspection.
- Avoid introducing new runtime complexity when a native WordPress or GeneratePress mechanism already exists.

## Rules for Debugging

- Reproduce with Playwright when possible.
- Use DevTools for frontend/runtime/layout diagnosis.
- Check `wp-content/debug.log` and related PHP logs for backend/runtime issues.
- Do not guess when runtime evidence is available.
- Do not add instrumentation before checking whether existing logs, browser evidence, or current markup already explain the issue.
- For CSS/layout bugs, inspect DOM, computed styles, and cascade before writing new rules.

## Anti-Patterns / Things Agents Must Avoid

- Editing parent theme files
- Bypassing GeneratePress architecture without reason
- Adding aggressive CSS hacks before inspecting DOM and cascade
- Unnecessary template overrides
- Duplicating existing GeneratePress capabilities
- Broad unvalidated changes
- Changes that make parent-theme updates harder
- Creating new CSS classes when an existing utility/component already fits
- Duplicating spacing, color, radius, or typography rules already represented by existing tokens
- Adding CSS before inspecting the DOM, cascade, and existing child-theme styles
- Creating parallel component variants without a clear architectural reason
- Solving markup-placement problems with CSS hacks
- Re-implementing an existing project pattern under a different name
- Adding code because it feels easier than understanding the current system

## Definition of a Good Change

A good change:

- Works with GeneratePress instead of against it
- Reuses existing project architecture whenever possible
- Is minimal and scoped
- Is maintainable and update-safe
- Is understandable later by another developer/agent
- Is validated with the right tool
- Avoids regressions
- Does not introduce duplicate styling logic or parallel abstractions without reason

## Agent Behavior Expectations

Agents should:

- Think architecturally before coding
- Preserve parent-theme compatibility
- Prefer native extension points
- Justify deeper overrides only when necessary
- Keep code focused and scoped
- Verify outcomes, not just output files
- Search for and reuse existing project patterns before inventing new ones
- Treat every new selector, helper, or override as something that must be justified, not assumed
- In every response or commit message, explicitly state which step of the Decision Order you followed and why deeper options were not chosen.

## Repo-Specific Testing Guidance (Playwright)

Test source and config:

- Specs: `playwright-tests/*.spec.ts`
- Config: `playwright.config.ts`
- Scripts: `package.json`

Generated outputs (do not commit):

- `playwright-report/`
- `playwright-results/`

Prerequisites:

- Node.js 20+
- npm

Portable setup on each machine from project root:

```bash
npm run test:smpt:setup
````

Run tests:

```bash
npm run test:smpt
```

Useful commands:

```bash
npm run test:smpt:headed
npm run test:smpt:ui
npm run test:smpt:report
npm run test:smpt:codegen -- --output playwright-tests/your-test-name.spec.ts
```

Base URL override:

```bash
BASE_URL=https://your-local-url npm run test:smpt
```

PowerShell:

```powershell
$env:BASE_URL="https://your-local-url"; npm run test:smpt
```

## Repo-Specific Monitoring Guidance (Local Logging)

Current logging architecture in this repo:

* WordPress debug logging is enabled in `wp-config.php`
* PHP errors are written to `wp-content/debug.log`
* The mu-plugin already writes custom runtime entries such as `[smpt-access]`
* `WP_DEBUG_DISPLAY` is disabled so errors stay out of rendered pages

Relevant files:

* `wp-config.php`
* `wp-content/debug.log`
* `wp-content/mu-plugins/smpt-site/inc/access-control.php`

Defaults in this repo:

* `WP_DEBUG`: `true`
* `WP_DEBUG_LOG`: `true`
* `WP_DEBUG_DISPLAY`: `false`

Workflow:

```bash
# Tail the latest log lines from project root
Get-Content wp-content/debug.log -Tail 100
```

Practical notes:

* Reproduce the issue first, then inspect the newest log lines immediately.
* Treat `wp-content/debug.log` as local machine state: useful for debugging, not for committing.
* Prefer adding narrowly scoped `error_log()` calls only when existing logs are insufficient.

## Repo-Specific Database / WP-CLI Access

Very important:

* Direct database access is available at `localhost:10011`
* Database name: `local`
* Username: `root`
* Password: `root`
* `wp-cli` is available and should be used when appropriate for WordPress inspection, content updates, metadata changes, and other native WordPress operations

Practical note:

* Prefer `wp-cli` first for WordPress-aware tasks before dropping to raw SQL.

## Repo-Specific GA4 Export Guidance

Use the GA4 exporter for repeatable data export into the mu-plugin directory. Do not rely on Playwright UI scraping for complete GA4 export because the Analytics UI is brittle and incomplete for this job.

Command from project root:

```bash
npm run ga4:export
```

Output location:

* `wp-content/mu-plugins/smpt-site/GA4_Analytics_<timestamp>/`

Required configuration:

* `GA4_PROPERTY_ID` or `GA4_PROPERTY_URL`
* Google credentials via `GOOGLE_APPLICATION_CREDENTIALS`
* Or inline/file credentials via `GA4_SERVICE_ACCOUNT_JSON`
* The service account must have access to the GA4 property (at least Viewer/Analyst-level read access)

Optional configuration:

* `GA4_START_DATE`
* `GA4_END_DATE`

Practical note:

* This exporter writes aggregated GA4 report data, metadata, custom dimension definitions, and custom metric definitions.
* If you need raw event-level GA4 export, use GA4 BigQuery export instead.

## First-Time Machine Bootstrap (No Guesswork)

From project root (`app/public`):

```bash
# 1) JavaScript test tooling
npm run test:smpt:setup
```

Then run:

```bash
npm run test:smpt
```