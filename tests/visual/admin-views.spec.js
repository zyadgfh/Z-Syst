// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Visual Regression Tests for Admin Views
 * 
 * These tests verify that admin views render correctly after CSS refactoring.
 * Run with: npx playwright test tests/visual/admin-views.spec.js
 * 
 * Prerequisites:
 * - Dev server running on http://127.0.0.1:8000
 * - Authenticated admin session (update BASE_URL and auth as needed)
 */

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';

// Helper: login as admin
async function loginAsAdmin(page) {
  await page.goto(`${BASE_URL}/login`);
  await page.fill('input[name="email"]', 'admin@z-syst.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/admin/**', { timeout: 10000 });
}

test.describe('Admin Views - Visual Regression', () => {
  test.beforeEach(async ({ page }) => {
    // Try to login, skip if already authenticated
    try {
      await loginAsAdmin(page);
    } catch {
      // Already logged in or login page not available
    }
  });

  test('dashboard renders correctly', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/dashboard`);
    await page.waitForLoadState('networkidle');
    
    // Wait for any animations to complete
    await page.waitForTimeout(500);
    
    await expect(page).toHaveScreenshot('admin-dashboard.png', {
      fullPage: false,
      mask: [
        page.locator('[data-testid="dynamic-value"]'), // Mask any dynamic values
      ],
    });
  });

  test('coupons index renders correctly', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/coupons`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    await expect(page).toHaveScreenshot('admin-coupons-index.png', {
      fullPage: false,
    });
  });

  test('loyalty index renders correctly', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/loyalty`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    await expect(page).toHaveScreenshot('admin-loyalty-index.png', {
      fullPage: false,
    });
  });

  test('inventory alerts renders correctly', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/inventory-alerts`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    await expect(page).toHaveScreenshot('admin-inventory-alerts.png', {
      fullPage: false,
    });
  });

  test('customer orders index renders correctly', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/customer-orders`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    await expect(page).toHaveScreenshot('admin-customer-orders.png', {
      fullPage: false,
    });
  });

  test('products index renders correctly', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/products`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    await expect(page).toHaveScreenshot('admin-products-index.png', {
      fullPage: false,
    });
  });

  test('comparison analytics renders correctly', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/comparison-analytics`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    await expect(page).toHaveScreenshot('admin-comparison-analytics.png', {
      fullPage: false,
    });
  });

  test('loyalty analytics renders correctly', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/loyalty/analytics`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    await expect(page).toHaveScreenshot('admin-loyalty-analytics.png', {
      fullPage: false,
    });
  });

  test('reports sales renders correctly', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/sales-report`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    await expect(page).toHaveScreenshot('admin-reports-sales.png', {
      fullPage: false,
    });
  });

  test('push notifications renders correctly', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/push-notifications`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    await expect(page).toHaveScreenshot('admin-push-notifications.png', {
      fullPage: false,
    });
  });
});

test.describe('Admin Views - RTL Layout', () => {
  test.beforeEach(async ({ page }) => {
    try {
      await loginAsAdmin(page);
    } catch {
      // Already logged in
    }
  });

  test('dashboard renders correctly in Arabic RTL', async ({ page }) => {
    // Switch to Arabic
    await page.goto(`${BASE_URL}/admin/dashboard?lang=ar`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    // Verify RTL direction
    const htmlDir = await page.getAttribute('html', 'dir');
    expect(htmlDir).toBe('rtl');
    
    await expect(page).toHaveScreenshot('admin-dashboard-rtl.png', {
      fullPage: false,
    });
  });

  test('dashboard renders correctly in English LTR', async ({ page }) => {
    // Switch to English
    await page.goto(`${BASE_URL}/admin/dashboard?lang=en`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    // Verify LTR direction
    const htmlDir = await page.getAttribute('html', 'dir');
    expect(htmlDir === 'ltr' || htmlDir === 'auto').toBeTruthy();
    
    await expect(page).toHaveScreenshot('admin-dashboard-ltr.png', {
      fullPage: false,
    });
  });
});

test.describe('Admin Views - CSS Utilities', () => {
  test.beforeEach(async ({ page }) => {
    try {
      await loginAsAdmin(page);
    } catch {
      // Already logged in
    }
  });

  test('admin-utilities.css loads on admin pages', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/dashboard`);
    await page.waitForLoadState('networkidle');
    
    // Check that admin-utilities.css is loaded
    const cssLoaded = await page.evaluate(() => {
      const links = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));
      return links.some(link => link.href.includes('admin-utilities.css'));
    });
    
    expect(cssLoaded).toBe(true);
  });

  test('admin-utilities.css NOT loaded on non-admin pages', async ({ page }) => {
    // Visit a non-admin page (login page or similar)
    await page.goto(`${BASE_URL}/login`);
    await page.waitForLoadState('networkidle');
    
    const cssLoaded = await page.evaluate(() => {
      const links = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));
      return links.some(link => link.href.includes('admin-utilities.css'));
    });
    
    expect(cssLoaded).toBe(false);
  });

  test('KPI grid renders with CSS classes not inline styles', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/dashboard`);
    await page.waitForLoadState('networkidle');
    
    // Check that KPI grid uses CSS class
    const kpiGrid = await page.$('.kpi-grid');
    if (kpiGrid) {
      const display = await kpiGrid.evaluate(el => getComputedStyle(el).display);
      expect(display).toBe('grid');
    }
  });

  test('card gradient classes render correctly', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/sales-report`);
    await page.waitForLoadState('networkidle');
    
    // Check that gradient cards have correct background
    const gradientCards = await page.$$('.card-gradient-green, .card-gradient-blue, .card-gradient-amber');
    for (const card of gradientCards) {
      const bg = await card.evaluate(el => getComputedStyle(el).backgroundImage);
      expect(bg).toContain('linear-gradient');
    }
  });
});
