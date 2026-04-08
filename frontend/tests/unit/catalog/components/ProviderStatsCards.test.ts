import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

import ProviderStatsCards from '@/catalog/components/ProviderStatsCards.vue';

describe('ProviderStatsCards', () => {
  const defaultProps = {
    status: 'connected' as const,
    projectsCount: 12,
    syncFreshness: 'fresh' as const,
    apiLatency: 45,
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders the stats container', () => {
    const wrapper = mount(ProviderStatsCards, { props: defaultProps });
    expect(wrapper.find('[data-testid="provider-health-stats"]').exists()).toBe(true);
  });

  it('renders all 4 stat cards', () => {
    const wrapper = mount(ProviderStatsCards, { props: defaultProps });
    const cards = wrapper.findAll('[data-testid="provider-health-stats"] > div');
    expect(cards).toHaveLength(4);
  });

  it('displays the status text', () => {
    const wrapper = mount(ProviderStatsCards, { props: defaultProps });
    expect(wrapper.text()).toContain('catalog.providers.statuses.connected');
  });

  it('displays the projects count', () => {
    const wrapper = mount(ProviderStatsCards, { props: defaultProps });
    expect(wrapper.text()).toContain('12');
  });

  it('displays the sync freshness', () => {
    const wrapper = mount(ProviderStatsCards, { props: defaultProps });
    expect(wrapper.text()).toContain('catalog.providers.health.fresh');
  });

  it('displays API latency when available', () => {
    const wrapper = mount(ProviderStatsCards, { props: defaultProps });
    expect(wrapper.text()).toContain('45ms');
  });

  it('displays --- when API latency is null', () => {
    const wrapper = mount(ProviderStatsCards, { props: { ...defaultProps, apiLatency: null } });
    expect(wrapper.text()).toContain('---');
  });
});
