/** @type {import('@stryker-mutator/api/core').PartialStrykerOptions} */
export default {
  plugins: ['@stryker-mutator/vitest-runner'],
  testRunner: 'vitest',
  mutate: ['src/**/*.ts', '!src/**/*.d.ts', '!src/**/*.test.ts', '!src/**/*.spec.ts'],
  reporters: ['clear-text', 'progress'],
  concurrency: 4,
  thresholds: {
    high: 80,
    low: 60,
    break: null,
  },
};
