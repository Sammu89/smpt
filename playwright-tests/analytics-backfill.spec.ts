import { execFileSync } from 'node:child_process';
import path from 'node:path';

import { expect, test } from '@playwright/test';

const rootDir = path.resolve(__dirname, '..');
const phpBin = process.env.PHP_BIN || 'php';
const nodeBin = process.env.NODE_BIN || process.execPath;
const shouldExport = process.env.RUN_GA4_EXPORT === '1';
const shouldReset = process.env.GA4_IMPORT_RESET === '1';
const exportDir = process.env.GA4_IMPORT_DIR || '';
const wpUser = process.env.WP_ADMIN_USER || '';
const wpPassword = process.env.WP_ADMIN_PASSWORD || '';

test.describe('GA4 Backfill Automation', () => {
  test.skip(!wpUser || !wpPassword, 'Requires WP_ADMIN_USER and WP_ADMIN_PASSWORD.');

  test('exports, imports, and verifies merged analytics in wp-admin', async ({ page }) => {
    if (shouldExport) {
      execFileSync(nodeBin, ['wp-content/mu-plugins/smpt-site/tools/export-ga4.js'], {
        cwd: rootDir,
        env: process.env,
        stdio: 'inherit',
      });
    }

    const importArgs = ['wp-content/mu-plugins/smpt-site/tools/import-ga4-history.php'];
    if (exportDir) {
      importArgs.push(`--input-dir=${exportDir}`);
    }
    if (shouldReset) {
      importArgs.push('--reset');
    }

    execFileSync(phpBin, importArgs, {
      cwd: rootDir,
      env: process.env,
      stdio: 'inherit',
    });

    await page.goto('/wp-login.php');
    await page.locator('#user_login').fill(wpUser);
    await page.locator('#user_pass').fill(wpPassword);
    await page.locator('#wp-submit').click();

    await page.goto('/wp-admin/index.php');
    await expect(page.locator('#smpt-analytics-root')).toBeVisible();

    await page.getByRole('button', { name: 'All Time' }).click();

    await expect
      .poll(async () => {
        return page.evaluate(async () => {
          const dashboard = (window as typeof window & {
            smptDashboard?: { rest_url: string; nonce: string };
          }).smptDashboard;

          if (!dashboard) {
            return null;
          }

          const response = await fetch(`${dashboard.rest_url}?period=all`, {
            headers: { 'X-WP-Nonce': dashboard.nonce },
          });

          return response.json();
        });
      })
      .toMatchObject({
        kpis: {
          streams: expect.any(Number),
          downloads: expect.any(Number),
        },
      });

    const allTimeStats = await page.evaluate(async () => {
      const dashboard = (window as typeof window & {
        smptDashboard?: { rest_url: string; nonce: string };
      }).smptDashboard;

      if (!dashboard) {
        throw new Error('smptDashboard is not available on the page.');
      }

      const response = await fetch(`${dashboard.rest_url}?period=all`, {
        headers: { 'X-WP-Nonce': dashboard.nonce },
      });

      return response.json();
    });

    expect(allTimeStats.kpis.streams).toBeGreaterThan(0);
    expect(allTimeStats.kpis.downloads).toBeGreaterThan(0);
    expect(allTimeStats.top_streams.length).toBeGreaterThan(0);
    expect(allTimeStats.top_downloads.length).toBeGreaterThan(0);
  });
});
