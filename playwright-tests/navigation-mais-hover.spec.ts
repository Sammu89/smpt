import { expect, test } from '@playwright/test';

test.describe('priority-plus navigation', () => {
  test('opens Mais flyouts on hover without clipping them at 1100px', async ({ page }) => {
    await page.setViewportSize({ width: 1100, height: 900 });
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(150);

    const desktopMaisItem = page.locator('#site-navigation .main-nav .smpt-mais-item');
    const maisTrigger = desktopMaisItem.locator(':scope > a');
    await expect(maisTrigger).toBeVisible();

    await maisTrigger.hover();
    await expect(desktopMaisItem).toHaveClass(/sfHover/);
    await page.waitForTimeout(120);

    const directMaisItems = desktopMaisItem.locator(':scope > .sub-menu').locator(':scope > li');
    const adaptacoes = directMaisItems.nth(1);
    const sobreNos = directMaisItems.nth(2);
    await expect(adaptacoes).toBeVisible();
    await expect(sobreNos).toBeVisible();

    const adaptacoesBox = await adaptacoes.boundingBox();
    const sobreBox = await sobreNos.boundingBox();
    if (!adaptacoesBox || !sobreBox) {
      throw new Error('Could not measure the compact Mais submenu items.');
    }

    await page.mouse.move(adaptacoesBox.x + adaptacoesBox.width / 2, adaptacoesBox.y + adaptacoesBox.height / 2);
    await page.waitForTimeout(180);

    const adaptacoesSubmenu = adaptacoes.locator(':scope > .sub-menu');
    await expect(adaptacoesSubmenu).toBeVisible();
    const adaptacoesSubmenuBox = await adaptacoesSubmenu.boundingBox();
    if (!adaptacoesSubmenuBox) {
      throw new Error('Could not measure the Adaptações flyout.');
    }

    expect(adaptacoesSubmenuBox.x).toBeGreaterThanOrEqual(0);
    expect(adaptacoesSubmenuBox.x + adaptacoesSubmenuBox.width).toBeLessThanOrEqual(1100);

    const startX = adaptacoesBox.x + adaptacoesBox.width * 0.7;
    const startY = adaptacoesBox.y + adaptacoesBox.height * 0.5;
    const endX = sobreBox.x + sobreBox.width / 2;
    const endY = sobreBox.y + sobreBox.height / 2;

    for (let step = 1; step <= 20; step += 1) {
      const progress = step / 20;
      await page.mouse.move(
        startX + (endX - startX) * progress,
        startY + (endY - startY) * progress
      );
      await page.waitForTimeout(20);
    }

    await expect.poll(async () => {
      return sobreNos.evaluate((el) => el.matches(':hover'));
    }).toBe(true);

    const sobreSubmenu = sobreNos.locator(':scope > .sub-menu');
    await expect(sobreSubmenu).toBeVisible();
    const sobreSubmenuBox = await sobreSubmenu.boundingBox();
    if (!sobreSubmenuBox) {
      throw new Error('Could not measure the Sobre nós flyout.');
    }

    expect(sobreSubmenuBox.x).toBeGreaterThanOrEqual(0);
    expect(sobreSubmenuBox.x + sobreSubmenuBox.width).toBeLessThanOrEqual(1100);
    await expect(sobreSubmenu.locator(':scope > li').first()).toBeVisible();
  });
});
