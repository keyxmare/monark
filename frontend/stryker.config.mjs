/** @type {import('@stryker-mutator/api/core').PartialStrykerOptions} */
export default {
  concurrency: 4,
  mutate: ['src/**/*.ts', '!src/**/*.d.ts', '!src/**/*.test.ts', '!src/**/*.spec.ts'],
  plugins: ['@stryker-mutator/vitest-runner'],
  reporters: ['clear-text', 'progress'],
  testRunner: 'vitest',
  thresholds: {
    break: 80,
    high: 80,
    low: 60,
  },
};
