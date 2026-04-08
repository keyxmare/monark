import { resolve } from 'node:path';
import vue from '@vitejs/plugin-vue';
import { defineConfig } from 'vitest/config';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, './src'),
    },
  },
  test: {
    coverage: {
      exclude: [
        'e2e/**',
        'playwright.config.ts',
        'stryker.config.mjs',
        'eslint.config.js',
        'prettier.config.js',
        'src/**/types/**',
        'src/**/routes.ts',
        'src/app/main.ts',
        'src/shared/types/**',
        'src/shared/constants.ts',
        'src/shared/i18n/**',
      ],
      include: ['src/**/*.{ts,vue}'],
    },
    environment: 'jsdom',
    include: ['src/**/*.{test,spec}.{ts,tsx}', 'tests/**/*.{test,spec}.{ts,tsx}'],
    passWithNoTests: true,
    setupFiles: ['./tests/setup.ts'],
  },
});
