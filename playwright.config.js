import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  outputDir: './tests/results',
  snapshotPathTemplate: '{testDir}/__screenshots__/{arg}{ext}',
  timeout: 30000,
  retries: 1,
  use: {
    baseURL: process.env.BASE_URL || 'http://127.0.0.1:8000',
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    viewport: { width: 1440, height: 900 },
  },
  projects: [
    {
      name: 'e2e',
      testDir: './tests/e2e',
      use: { browserName: 'chromium' },
    },
    {
      name: 'visual',
      testDir: './tests/visual',
      use: { browserName: 'chromium' },
    },
  ],
  expect: {
    toHaveScreenshot: {
      maxDiffPixelRatio: 0.01,
      threshold: 0.2,
    },
  },
});
