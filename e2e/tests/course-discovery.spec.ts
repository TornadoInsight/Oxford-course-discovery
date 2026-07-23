import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

/**
 * Assumes the course finder is published at "/" (see README — the local
 * dev setup sets the "Course Finder" page as the site's front page) and
 * that `wp course-discovery seed` has been run.
 */

test.describe('Course Discovery filters', () => {
  test('shows results and lets a user search by keyword', async ({ page }) => {
    await page.goto('/');

    await expect(page.getByRole('heading', { name: 'Course Finder' })).toBeVisible();
    const initialCount = await page.locator('[data-course-discovery-count]').innerText();
    expect(initialCount).toMatch(/\d+ courses? found/);

    await page.getByLabel('Search courses').fill('design');
    await expect(page.locator('[data-course-discovery-count]')).toHaveText('2 courses found');
    await expect(page.getByRole('heading', { name: 'Graphic Design Foundations' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Business English Intensive' })).toHaveCount(0);
  });

  test('start date options are listed in chronological order', async ({ page }) => {
    await page.goto('/');

    const trigger = page.getByRole('button', { name: 'Start Dates' });
    await trigger.click();

    const options = page.locator('.course-discovery__field--start_dates [role="option"]');
    const labels = await options.allInnerTexts();
    const trimmed = labels.map((label) => label.trim());

    const chronological = [...trimmed].sort(
      (a, b) => Date.parse(`1 ${a}`) - Date.parse(`1 ${b}`)
    );
    expect(trimmed).toEqual(chronological);
  });

  test('locations combobox is fully keyboard operable and narrows results', async ({ page }) => {
    await page.goto('/');

    // Tab from the search field to the Locations trigger button using only the keyboard.
    await page.getByLabel('Search courses').focus();
    await page.keyboard.press('Tab'); // past provider checkboxes region start
    const trigger = page.getByRole('button', { name: 'Locations' });
    await trigger.focus();

    await page.keyboard.press('ArrowDown');
    await expect(page.locator('.course-discovery__field--locations [role="listbox"]')).toBeVisible();

    await page.keyboard.press('Space'); // select first focused option (China, alphabetically)
    await page.keyboard.press('Escape');

    await expect(trigger).toHaveText(/Locations \(1\)/);
    await expect(page.locator('[data-course-discovery-count]')).toHaveText(/\d+ courses? found/);
  });

  test('has no critical automated accessibility violations', async ({ page }) => {
    await page.goto('/');

    const results = await new AxeBuilder({ page })
      .include('.course-discovery')
      .withTags(['wcag2a', 'wcag2aa'])
      .analyze();

    const critical = results.violations.filter((v) => v.impact === 'critical' || v.impact === 'serious');
    expect(critical, JSON.stringify(critical, null, 2)).toEqual([]);
  });
});
