import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string, params?: Record<string, unknown>) => key }),
}));

vi.mock('@/catalog/composables/useFrameworkLts', () => ({
  humanizeMs: vi.fn((ms: number) => `${Math.round(ms / 86400000)}d`),
  msUrgency: vi.fn(() => 'fresh'),
}));

import DependencyHealthScore from '@/dependency/components/DependencyHealthScore.vue';

describe('DependencyHealthScore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders health score section when healthScore is provided', () => {
    const wrapper = mount(DependencyHealthScore, {
      props: {
        healthScore: { outdated: 2, percent: 80, total: 10, totalVulns: 1, upToDate: 8 },
        depGapStats: null,
      },
    });
    expect(wrapper.find('[data-testid="dependency-health-score"]').exists()).toBe(true);
  });

  it('does not render health score section when healthScore is null', () => {
    const wrapper = mount(DependencyHealthScore, {
      props: { healthScore: null, depGapStats: null },
    });
    expect(wrapper.find('[data-testid="dependency-health-score"]').exists()).toBe(false);
  });

  it('shows outdated badge when outdated > 0', () => {
    const wrapper = mount(DependencyHealthScore, {
      props: {
        healthScore: { outdated: 3, percent: 70, total: 10, totalVulns: 0, upToDate: 7 },
        depGapStats: null,
      },
    });
    expect(wrapper.text()).toContain('dependency.dependencies.healthOutdated');
  });

  it('hides outdated badge when outdated is 0', () => {
    const wrapper = mount(DependencyHealthScore, {
      props: {
        healthScore: { outdated: 0, percent: 100, total: 10, totalVulns: 0, upToDate: 10 },
        depGapStats: null,
      },
    });
    expect(wrapper.text()).not.toContain('dependency.dependencies.healthOutdated');
  });

  it('shows vulnerability badge when totalVulns > 0', () => {
    const wrapper = mount(DependencyHealthScore, {
      props: {
        healthScore: { outdated: 0, percent: 100, total: 10, totalVulns: 5, upToDate: 10 },
        depGapStats: null,
      },
    });
    expect(wrapper.text()).toContain('dependency.dependencies.healthVulns');
  });

  it('renders dep gap stats section when depGapStats is provided', () => {
    const wrapper = mount(DependencyHealthScore, {
      props: {
        healthScore: null,
        depGapStats: { average: 86400000, cumulated: 259200000, median: 172800000 },
      },
    });
    expect(wrapper.find('[data-testid="dep-gap-stats"]').exists()).toBe(true);
  });

  it('does not render dep gap stats when depGapStats is null', () => {
    const wrapper = mount(DependencyHealthScore, {
      props: { healthScore: null, depGapStats: null },
    });
    expect(wrapper.find('[data-testid="dep-gap-stats"]').exists()).toBe(false);
  });

  it('renders all three gap stat cards', () => {
    const wrapper = mount(DependencyHealthScore, {
      props: {
        healthScore: null,
        depGapStats: { average: 86400000, cumulated: 259200000, median: 172800000 },
      },
    });
    const cards = wrapper.find('[data-testid="dep-gap-stats"]').findAll('.rounded-xl');
    expect(cards).toHaveLength(3);
  });
});
