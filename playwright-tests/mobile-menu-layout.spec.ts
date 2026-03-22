import { expect, test } from '@playwright/test';

test.describe('mobile menu layout', () => {
  test('starts with search and keeps the close button in the bottom-right footer', async ({ page }) => {
    await page.setViewportSize({ width: 900, height: 900 });
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(150);

    await page.locator('#site-navigation .menu-toggle').click();

    const panel = page.locator('#smpt-mobile-menu .smpt-mobile-menu__panel');
    const search = panel.locator('.smpt-mobile-menu__search');
    const section = panel.locator('.smpt-mobile-menu__section');
    const footer = panel.locator('.smpt-mobile-menu__footer');
    const close = footer.locator('.smpt-mobile-menu__close');

    await expect(panel).toBeVisible();
    await expect(search).toBeVisible();
    await expect(section).toBeVisible();
    await expect(footer).toBeVisible();
    await expect(close).toBeVisible();
    await expect(panel.locator('.smpt-mobile-menu__eyebrow')).toHaveCount(0);
    await expect(panel.locator('.smpt-mobile-menu__title')).toHaveCount(0);

    const panelBox = await panel.boundingBox();
    const searchBox = await search.boundingBox();
    const sectionBox = await section.boundingBox();
    const footerBox = await footer.boundingBox();
    const closeBox = await close.boundingBox();

    if (!panelBox || !searchBox || !sectionBox || !footerBox || !closeBox) {
      throw new Error('Could not measure the mobile menu layout.');
    }

    expect(searchBox.y).toBeLessThan(sectionBox.y);
    expect(footerBox.y).toBeGreaterThan(sectionBox.y);
    expect(closeBox.x + closeBox.width).toBeLessThanOrEqual(panelBox.x + panelBox.width - 12);
    expect(closeBox.y + closeBox.height).toBeLessThanOrEqual(panelBox.y + panelBox.height - 12);
  });

  test('uses full-row disclosure buttons with a plus sign for submenu parents', async ({ page }) => {
    await page.setViewportSize({ width: 900, height: 900 });
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(150);

    await page.locator('#site-navigation .menu-toggle').click();

    const root = page.locator('#smpt-mobile-menu [data-smpt-mobile-menu-root] > ul');
    const firstParent = root.locator(':scope > li.menu-item-has-children').nth(1);
    const disclosure = firstParent.locator(':scope > .smpt-mobile-parent-disclosure');

    await expect(disclosure).toBeVisible();
    await expect(root.locator('.smpt-mobile-submenu-toggle')).toHaveCount(0);
    await expect(disclosure.locator('.smpt-mobile-parent-disclosure__label')).toHaveText('+');

    await disclosure.click();

    await expect(firstParent).toHaveClass(/is-open/);
    await expect(disclosure).toHaveAttribute('aria-expanded', 'true');
    await expect(disclosure.locator('.smpt-mobile-parent-disclosure__label')).toHaveText('-');
    await expect(firstParent.locator(':scope > .sub-menu')).toBeVisible();
  });

  test('keeps long nested disclosure labels clear of the circular plus badge', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 900 });
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(150);

    await page.locator('#site-navigation .menu-toggle').click();
    await page.locator('#smpt-mobile-menu .smpt-mobile-parent-disclosure', { hasText: 'Anime' }).first().click();

    const disclosure = page.locator('#smpt-mobile-menu .smpt-mobile-parent-disclosure', { hasText: 'V3 – Filmes e Episódios Especiais' }).first();

    await expect(disclosure).toBeVisible();

    const geometry = await disclosure.evaluate((element) => {
      const content = element.querySelector<HTMLElement>('.smpt-mobile-parent-disclosure__content');
      const label = element.querySelector<HTMLElement>('.smpt-mobile-parent-disclosure__label');

      if (!content || !label) {
        throw new Error('Could not find the disclosure content and label.');
      }

      const contentRect = content.getBoundingClientRect();
      const labelRect = label.getBoundingClientRect();
      const contentStyles = window.getComputedStyle(content);

      return {
        contentRight: contentRect.right,
        labelLeft: labelRect.left,
        contentBottom: contentRect.bottom,
        labelTop: labelRect.top,
        overflow: contentStyles.overflow,
        whiteSpace: contentStyles.whiteSpace,
      };
    });

    expect(geometry.contentRight).toBeLessThanOrEqual(geometry.labelLeft - 8);
    expect(geometry.overflow).toBe('visible');
    expect(geometry.whiteSpace).toBe('normal');
  });

  test('wraps long nested submenu links instead of forcing a single line', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 900 });
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(150);

    await page.locator('#site-navigation .menu-toggle').click();
    await page.locator('#smpt-mobile-menu .smpt-mobile-parent-disclosure', { hasText: 'Anime' }).first().click();

    const link = page.locator('#smpt-mobile-menu a', { hasText: 'V3 – Download Filmes e Episódios Especiais' }).first();

    await expect(link).toBeVisible();

    const metrics = await link.evaluate((element) => {
      const styles = window.getComputedStyle(element);

      return {
        whiteSpace: styles.whiteSpace,
        lineHeight: parseFloat(styles.lineHeight),
        height: element.getBoundingClientRect().height,
        scrollWidth: element.scrollWidth,
        clientWidth: element.clientWidth,
      };
    });

    expect(metrics.whiteSpace).toBe('normal');
    expect(metrics.height).toBeGreaterThan(metrics.lineHeight * 1.5);
    expect(metrics.scrollWidth).toBeLessThanOrEqual(metrics.clientWidth + 1);
  });
});
