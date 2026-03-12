# Playwright E2E Testing (Portable Setup)

This project keeps Playwright tests in the WordPress root so every machine can run the same suite with the same commands.

## Test location

- Test specs: `tests/e2e/*.spec.ts`
- Playwright config: `playwright.config.ts`
- NPM scripts: `package.json`
- HTML report output (generated): `tests/e2e-report/`
- Test artifacts output (generated): `tests/e2e-results/`

Generated output folders are not committed to Git. Test source files are committed.

## Requirements (each computer)

1. Node.js 20+ (LTS recommended)
2. npm (comes with Node.js)
3. Local WordPress environment running and reachable (default URL used by tests: `https://smpt.local`)

## First-time setup on a new computer

Run these commands from the WordPress root folder (`app/public`):

```bash
npm run test:e2e:setup
```

What this does:
- Installs exact JS dependencies from `package-lock.json` (`npm ci`)
- Installs Playwright Chromium browser used by the tests

## Run tests

From WordPress root (`app/public`):

```bash
npm run test:e2e
```

Useful variants:

```bash
npm run test:e2e:headed
npm run test:e2e:ui
npm run test:e2e:report
```

## Base URL portability

Default base URL is set in `playwright.config.ts`:
- `https://smpt.local`

To run against another URL without editing files:

```bash
BASE_URL=https://your-local-url npm run test:e2e
```

Windows PowerShell:

```powershell
$env:BASE_URL="https://your-local-url"; npm run test:e2e
```

## Adding new tests

Create new files under:

```text
tests/e2e/
```

Optional codegen helper:

```bash
npm run test:e2e:codegen -- --output tests/e2e/your-test-name.spec.ts
```

## Git portability rules

- Commit these files:
  - `package.json`
  - `package-lock.json`
  - `playwright.config.ts`
  - `tests/e2e/**/*.spec.ts`
- Do not commit generated outputs:
  - `tests/e2e-report/`
  - `tests/e2e-results/`
  - `node_modules/`
