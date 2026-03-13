# AGENTS.md

## Project Mission

This website is a WordPress project built on **GeneratePress (parent theme)** with a **GeneratePress child theme**.

Agents must customize the site by extending the parent theme architecture, not bypassing it. Favor maintainability, update safety, and low-friction customization that remains compatible with future GeneratePress updates.

## Core Development Philosophy

- Prefer existing GeneratePress settings, options, layout controls, and hook-based extension points before writing custom code.
- Treat the child theme as an extension layer, not a dumping ground.
- Prefer hooks, Hook Elements, and theme-native mechanisms over template overrides.
- Use CSS/JS/PHP only when the goal cannot be achieved cleanly via settings/hooks.
- Use template overrides only as a last resort.
- Never edit parent theme files.
- Avoid brittle hacks, specificity wars, and unnecessary `!important`.
- Reuse and respect existing project architecture and naming.

## Decision Order / Implementation Hierarchy

Before making any change, follow this strict order:

1. Check whether the result can be achieved with existing GeneratePress settings/options.
2. If not, check hooks/Hook Elements/WordPress hooks.
3. If still not possible, implement scoped child-theme CSS/JS/PHP.
4. Only if unavoidable, use a selective template override or deeper customization.

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

## Tool Usage Policy

Available tools:

- Playwright
- Chrome browser / DevTools MCP
- Sentry

Use Playwright for:

- User-flow validation
- Page interaction checks
- Breakpoint/responsive checks
- Regression checks
- Visibility/placement verification
- Practical behavior validation after changes

Examples:

- Verify hooked content renders in the intended hook location.
- Validate nav/menu behavior across desktop/mobile.
- Check forms and key UI flows.

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

Use Sentry for:

- Production/runtime errors
- Intermittent/user-reported issues
- Release-linked regressions
- Slow transactions
- Bugs not reliably reproducible locally

Examples:

- Identify which deployed release introduced a frontend error.
- Find recurring backend exceptions by frequency/impact.
- Prioritize issues with real-user evidence.

## Standard Workflow for Agents

1. Inspect before coding.
2. Understand relevant GeneratePress structure, settings, hooks, and existing child-theme code.
3. Choose the least invasive implementation path.
4. Make scoped, minimal changes.
5. Validate in browser/tooling (Playwright and/or DevTools as appropriate).
6. Do not use Sentry for straightforward local visual/layout debugging that can be reproduced immediately in Playwright/DevTools. Use Sentry for inconsistent, production-only, or user-reported behavior. 

## Rules for Design / Layout Tasks

- First check if GeneratePress settings already support the requested visual result.
- Prefer layout/container/spacing/typography controls and hook placement before custom CSS.
- Inspect current DOM/CSS with DevTools before writing styles.
- Validate responsive behavior with Playwright across key viewports.

## Rules for Functional / Behavioral Tasks

- Before building custom features, check whether hooks or native WordPress/GeneratePress mechanisms already solve it.
- Keep JavaScript minimal and tightly scoped.
- Keep PHP modular, explicit, and easy to trace.
- Validate actual user behavior with Playwright, not just static code inspection.

## Rules for Debugging

- Reproduce with Playwright when possible.
- Use DevTools for frontend/runtime/layout diagnosis.
- Use Sentry first for production-only, intermittent, or user-reported issues.
- Do not guess when runtime evidence is available.

## Anti-Patterns / Things Agents Must Avoid

- Editing parent theme files
- Bypassing GeneratePress architecture without reason
- Adding aggressive CSS hacks before inspecting DOM and cascade
- Unnecessary template overrides
- Duplicating existing GeneratePress capabilities
- Broad unvalidated changes
- Changes that make parent-theme updates harder

## Definition of a Good Change

A good change:

- Works with GeneratePress instead of against it
- Is minimal and scoped
- Is maintainable and update-safe
- Is understandable later by another developer/agent
- Is validated with the right tool
- Avoids regressions

## Agent Behavior Expectations

Agents should:

- Think architecturally before coding
- Preserve parent-theme compatibility
- Prefer native extension points
- Justify deeper overrides only when necessary
- Keep code focused and scoped
- Verify outcomes, not just output files

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
```

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

## Repo-Specific Monitoring Guidance (Sentry)

Current Sentry architecture in this repo:

- PHP SDK in MU plugin scope via Composer
- Browser SDK loaded once from CDN in shared head-assets loader
- Error monitoring enabled first
- Tracing enabled with conservative sampling
- Session Replay intentionally disabled

Relevant files:

- `wp-content/mu-plugins/smpt-site.php`
- `wp-content/mu-plugins/smpt-site/inc/sentry.php`
- `wp-content/mu-plugins/smpt-site/inc/head-assets.php`
- `wp-content/mu-plugins/smpt-site/composer.json`
- `wp-content/mu-plugins/smpt-site/composer.lock`

Prerequisites:

- PHP 8.2+ (site runtime)
- Composer

Per-machine dependency install:

```bash
cd wp-content/mu-plugins/smpt-site
composer install --no-interaction --prefer-dist
```

Runtime config placeholders (`wp-config.php` or env):

- `SMPT_SENTRY_DSN` (fallback `SENTRY_DSN`)
- `SMPT_SENTRY_ENVIRONMENT`
- `SMPT_SENTRY_RELEASE` (fallback `SENTRY_RELEASE`)
- `SMPT_SENTRY_TRACES_SAMPLE_RATE`
- Optional: `SMPT_SENTRY_BROWSER_SDK_VERSION`

Place these definitions in `wp-config.php` (or export them as environment variables before PHP bootstraps) so Sentry activates.

Default trace sampling if unset:

- `production`: `0.05`
- non-production: `0.20`

## Repo-Specific GA4 Export Guidance

Use the GA4 exporter for repeatable data export into the mu-plugin directory. Do not rely on Playwright UI scraping for complete GA4 export because the Analytics UI is brittle and incomplete for this job.

Command from project root:

```bash
npm run ga4:export
```

Output location:

- `wp-content/mu-plugins/smpt-site/GA4_Analytics_<timestamp>/`

Required configuration:

- `GA4_PROPERTY_ID` or `GA4_PROPERTY_URL`
- Google credentials via `GOOGLE_APPLICATION_CREDENTIALS`
- Or inline/file credentials via `GA4_SERVICE_ACCOUNT_JSON`
- The service account must have access to the GA4 property (at least Viewer/Analyst-level read access)

Optional configuration:

- `GA4_START_DATE`
- `GA4_END_DATE`

Practical note:

- This exporter writes aggregated GA4 report data, metadata, custom dimension definitions, and custom metric definitions.
- If you need raw event-level GA4 export, use GA4 BigQuery export instead.

## First-Time Machine Bootstrap (No Guesswork)

From project root (`app/public`):

```bash
# 1) JavaScript test tooling
npm run test:smpt:setup

# 2) PHP Sentry SDK
cd wp-content/mu-plugins/smpt-site
composer install --no-interaction --prefer-dist
cd ../../../../
```

Then configure Sentry DSN/environment/release in `wp-config.php` (or env vars), and run:

```bash
npm run test:smpt
```
