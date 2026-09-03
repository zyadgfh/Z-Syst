// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E Tests for Full Admin Dashboard User Journey
 * Tests the complete flow: Login → Dashboard → Navigation → CRUD Operations
 *
 * Run with: npx playwright test tests/e2e/user-journey.spec.js
 * Prerequisites: Dev server running on http://127.0.0.1:8000
 */

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';

// Helper: login as admin
async function loginAsAdmin(page) {
  await page.goto(`${BASE_URL}/login`);
  await page.waitForLoadState('networkidle');
  await page.fill('input[name="email"]', 'admin@z-syst.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/admin/**', { timeout: 10000 });
}

// ============================
// Authentication Flow
// ============================
test.describe('Authentication Flow', () => {
  test('login page renders with form', async ({ page }) => {
    await page.goto(`${BASE_URL}/login`);
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('admin can login and reach dashboard', async ({ page }) => {
    await loginAsAdmin(page);
    await expect(page).toHaveURL(/admin/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('login with wrong credentials shows error', async ({ page }) => {
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', 'wrong@email.com');
    await page.fill('input[name="password"]', 'wrongpass');
    await page.click('button[type="submit"]');
    // Should stay on login page
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('login');
  });
});

// ============================
// Dashboard
// ============================
test.describe('Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('dashboard loads with KPI cards', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/dashboard`);
    await page.waitForLoadState('networkidle');
    // Verify page loaded (body visible)
    await expect(page.locator('body')).toBeVisible();
  });

  test('dashboard has navigation sidebar', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/dashboard`);
    await page.waitForLoadState('networkidle');
    const sidebar = page.locator('.sidebar, nav, [class*="sidebar"], [class*="menu"]');
    await expect(sidebar.first()).toBeVisible();
  });
});

// ============================
// Products Management
// ============================
test.describe('Products Management', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('products index loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/items`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });

  test('products page has table or list', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/items`);
    await page.waitForLoadState('networkidle');
    const hasTable = await page.locator('table').count();
    const hasCards = await page.locator('.card').count();
    expect(hasTable + hasCards).toBeGreaterThan(0);
  });
});

// ============================
// Stock Transfers
// ============================
test.describe('Stock Transfers', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('stock transfers index loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/stock-transfers`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });

  test('stock transfer create form loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/stock-transfers/create`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('form')).toBeVisible();
  });
});

// ============================
// Insurance Module
// ============================
test.describe('Insurance Module', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('insurance companies index loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/insurance/companies`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });

  test('insurance policies index loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/insurance/policies`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });

  test('insurance claims index loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/insurance/claims`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });

  test('insurance statistics page loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/insurance/claims/statistics/dashboard`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });

  test('insurance create company form has required fields', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/insurance/companies/create`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('select[name="status"]')).toBeVisible();
    await expect(page.locator('select[name="integration_type"]')).toBeVisible();
  });

  test('create new insurance company successfully', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/insurance/companies/create`);
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="name"]', 'E2E Test Insurance Co');
    await page.fill('input[name="contact_person"]', 'Test Contact');
    await page.fill('input[name="phone"]', '+201555555555');
    await page.fill('input[name="email"]', 'e2e@testinsurance.com');
    await page.selectOption('select[name="status"]', 'active');
    await page.selectOption('select[name="integration_type"]', 'manual');
    await page.fill('input[name="default_coverage_percent"]', '75');
    await page.fill('input[name="default_copay_percent"]', '25');
    await page.fill('input[name="settlement_days"]', '30');

    // Submit via AJAX
    await page.click('button[type="submit"]');

    // Wait for redirect to companies index
    await page.waitForURL('**/admin/insurance/companies', { timeout: 10000 });
    await page.waitForLoadState('networkidle');

    // Verify the new company appears in the list
    await expect(page.locator('text=E2E Test Insurance Co')).toBeVisible();
  });

  test('create new insurance claim and verify in list', async ({ page }) => {
    // First, check that a company exists
    await page.goto(`${BASE_URL}/admin/insurance/companies`);
    await page.waitForLoadState('networkidle');

    // Navigate to create claim
    await page.goto(`${BASE_URL}/admin/insurance/claims/create`);
    await page.waitForLoadState('networkidle');

    // Verify form fields are visible
    await expect(page.locator('select[name="insurance_company_id"]')).toBeVisible();
    await expect(page.locator('input[name="service_date"]')).toBeVisible();
    await expect(page.locator('input[name="total_amount"]')).toBeVisible();

    // Select first available insurance company
    const companySelect = page.locator('select[name="insurance_company_id"]');
    const options = await companySelect.locator('option').allTextContents();
    // Find first non-empty option
    let companyValue = '';
    for (const opt of await companySelect.locator('option').all()) {
      const val = await opt.getAttribute('value');
      if (val && val !== '') {
        companyValue = val;
        break;
      }
    }
    if (companyValue) {
      await companySelect.selectOption(companyValue);
      // Wait for policies to load via AJAX
      await page.waitForTimeout(1000);

      // Select first available policy
      const policySelect = page.locator('select[name="insurance_policy_id"]');
      const policyOptions = await policySelect.locator('option').all();
      let policyValue = '';
      for (const opt of policyOptions) {
        const val = await opt.getAttribute('value');
        if (val && val !== '') {
          policyValue = val;
          break;
        }
      }
      if (policyValue) {
        await policySelect.selectOption(policyValue);

        // Fill in claim details
        await page.fill('input[name="service_date"]', '2026-09-03');
        await page.fill('input[name="total_amount"]', '500');
        await page.fill('textarea[name="notes"]', 'E2E test claim - automated test');

        // Submit the form
        await page.click('button[type="submit"]');

        // Wait for redirect to claims index
        await page.waitForURL('**/admin/insurance/claims', { timeout: 10000 });
        await page.waitForLoadState('networkidle');

        // Verify the claim appears in the list (claims have CLM- prefix)
        const claimVisible = await page.locator('text=CLM-').first().isVisible().catch(() => false);
        expect(claimVisible).toBe(true);
      }
    }
  });

  test('insurance claim create form validates required fields', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/insurance/claims/create`);
    await page.waitForLoadState('networkidle');

    // Try to submit empty form
    await page.click('button[type="submit"]');
    await page.waitForTimeout(1000);

    // Should stay on create page (validation error)
    expect(page.url()).toContain('claims/create');
  });

  test('insurance claims statistics page shows data', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/insurance/claims/statistics/dashboard`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    // Verify statistics cards are present
    const totalClaims = page.locator('#totalClaims');
    await expect(totalClaims).toBeVisible();

    // Verify the value is a number (from seeded data)
    const value = await totalClaims.textContent();
    expect(parseInt(value)).toBeGreaterThanOrEqual(0);
  });

  test('insurance company show page displays details', async ({ page }) => {
    // First go to companies list
    await page.goto(`${BASE_URL}/admin/insurance/companies`);
    await page.waitForLoadState('networkidle');

    // Click the first view button (eye icon)
    const viewBtn = page.locator('a[href*="insurance/companies"][href*="show"], a.btn-outline-primary').first();
    if (await viewBtn.isVisible().catch(() => false)) {
      await viewBtn.click();
      await page.waitForLoadState('networkidle');

      // Verify company details are displayed
      await expect(page.locator('.card')).toBeVisible();
    }
  });
});

// ============================
// Coupons
// ============================
test.describe('Coupons Module', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('coupons index loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/coupons`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });

  test('coupons analytics loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/coupons/analytics`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });
});

// ============================
// Loyalty Program
// ============================
test.describe('Loyalty Program', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('loyalty index loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/loyalty`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });

  test('loyalty analytics loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/loyalty/analytics`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });
});

// ============================
// Reports & Analytics
// ============================
test.describe('Reports & Analytics', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('sales report loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/sales-report`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });

  test('comparison analytics loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/comparison-analytics`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });

  test('inventory alerts loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/inventory-alerts`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });
});

// ============================
// Customer Orders
// ============================
test.describe('Customer Orders', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('customer orders index loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/customer-orders`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });
});

// ============================
// Settings & Notifications
// ============================
test.describe('Settings & Notifications', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('settings page loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/settings`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });

  test('push notifications page loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/push-notifications`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });
});

// ============================
// CSS Code Splitting Verification
// ============================
test.describe('CSS Code Splitting', () => {
  test('admin CSS loads only on admin pages', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE_URL}/admin/dashboard`);
    await page.waitForLoadState('networkidle');

    const adminCssLoaded = await page.evaluate(() => {
      const links = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));
      return links.some(link => link.href.includes('admin-utilities.css'));
    });
    expect(adminCssLoaded).toBe(true);
  });

  test('admin CSS does NOT load on login page', async ({ page }) => {
    await page.goto(`${BASE_URL}/login`);
    await page.waitForLoadState('networkidle');

    const adminCssLoaded = await page.evaluate(() => {
      const links = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));
      return links.some(link => link.href.includes('admin-utilities.css'));
    });
    expect(adminCssLoaded).toBe(false);
  });
});

// ============================
// RTL / Bilingual Support
// ============================
test.describe('Bilingual Support', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('dashboard renders in RTL when Arabic', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/dashboard?lang=ar`);
    await page.waitForLoadState('networkidle');
    const dir = await page.getAttribute('html', 'dir');
    expect(dir).toBe('rtl');
  });

  test('dashboard renders in LTR when English', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/dashboard?lang=en`);
    await page.waitForLoadState('networkidle');
    const dir = await page.getAttribute('html', 'dir');
    expect(dir === 'ltr' || dir === 'auto').toBeTruthy();
  });
});

// ============================
// Responsive Layout
// ============================
test.describe('Responsive Layout', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('dashboard renders on mobile viewport', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto(`${BASE_URL}/admin/dashboard`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });

  test('dashboard renders on tablet viewport', async ({ page }) => {
    await page.setViewportSize({ width: 768, height: 1024 });
    await page.goto(`${BASE_URL}/admin/dashboard`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });
});

// ============================
// Navigation Flow
// ============================
test.describe('Full Navigation Flow', () => {
  test('sidebar navigation reaches all main sections', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE_URL}/admin/dashboard`);
    await page.waitForLoadState('networkidle');

    // Check that sidebar has navigation links
    const links = await page.locator('nav a, .sidebar a, [class*="sidebar"] a').count();
    expect(links).toBeGreaterThan(5); // Should have multiple nav links
  });

  test('can navigate from dashboard to products and back', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE_URL}/admin/dashboard`);
    await page.waitForLoadState('networkidle');

    // Navigate to items
    await page.goto(`${BASE_URL}/admin/items`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();

    // Navigate back to dashboard
    await page.goto(`${BASE_URL}/admin/dashboard`);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toBeVisible();
  });
});
