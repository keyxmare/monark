import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { createProvider } from '../../../factories';

vi.mock('vue-i18n', () => ({
  useI18n: () => ({
    d: (date: Date) => date.toISOString(),
    t: (key: string) => key,
  }),
}));

vi.mock('@/catalog/components/ProviderIcon.vue', () => ({
  default: { props: ['type', 'size'], template: '<span data-testid="mock-provider-icon" />' },
}));

vi.mock('@/shared/components/DropdownMenu.vue', () => ({
  default: { template: '<div data-testid="mock-dropdown" />' },
}));

import ProviderCard from '@/catalog/components/ProviderCard.vue';

describe('ProviderCard', () => {
  const provider = createProvider({ name: 'My GitHub', type: 'github', status: 'connected' });
  const items = [{ action: 'edit', label: 'Edit' }];

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders the provider name', () => {
    const wrapper = mount(ProviderCard, { props: { provider, items } });
    expect(wrapper.text()).toContain('My GitHub');
  });

  it('renders the status badge', () => {
    const wrapper = mount(ProviderCard, { props: { provider, items } });
    const badge = wrapper.find('[data-testid="provider-status-badge"]');
    expect(badge.exists()).toBe(true);
    expect(badge.text()).toContain('catalog.providers.statuses.connected');
  });

  it('renders the provider URL link', () => {
    const wrapper = mount(ProviderCard, { props: { provider, items } });
    const link = wrapper.find('[data-testid="provider-url-link"]');
    expect(link.exists()).toBe(true);
    expect(link.attributes('href')).toBe(provider.url);
  });

  it('renders projects count', () => {
    const wrapper = mount(ProviderCard, { props: { provider, items } });
    const count = wrapper.find('[data-testid="provider-projects-count"]');
    expect(count.exists()).toBe(true);
    expect(count.text()).toContain(String(provider.projectsCount));
  });

  it('emits navigate on click', async () => {
    const wrapper = mount(ProviderCard, { props: { provider, items } });
    await wrapper.find('[data-testid="provider-list-card"]').trigger('click');
    expect(wrapper.emitted('navigate')).toHaveLength(1);
  });

  it('emits navigate on Enter keydown', async () => {
    const wrapper = mount(ProviderCard, { props: { provider, items } });
    await wrapper.find('[data-testid="provider-list-card"]').trigger('keydown.enter');
    expect(wrapper.emitted('navigate')).toHaveLength(1);
  });
});
