import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  timeout: 30_000,
  fullyParallel: true,
  reporter: 'list',
  use: {
    baseURL: process.env.COURSE_DISCOVERY_BASE_URL || 'http://127.0.0.1:8080',
    trace: 'on-first-retry',
  },
});
