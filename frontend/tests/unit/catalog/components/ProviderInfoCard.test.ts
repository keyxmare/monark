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
  default: { props: ['type', 'size'], template: '<span />' },
}));

import ProviderInfoCard from '@/catalog/components/ProviderInfoCard.vue';

describe('ProviderInfoCard', () => {
  const provider = createProvider({
    name: 'GitLab Corp',
    type: 'gitlab',
    status: 'connected',
    username: 'admin',
    url: 'https://gitlab.example.com',
  });

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders the provider detail card', () => {
    const wrapper = mount(ProviderInfoCard, {
      props: { provider, testingConnection: false },
    });
    expect(wrapper.find('[data-testid="provider-detail-card"]').exists()).toBe(true);
  });

  it('renders the provider name', () => {
    const wrapper = mount(ProviderInfoCard, {
      props: { provider, testingConnection: false },
    });
    expect(wrapper.text()).toContain('GitLab Corp');
  });

  it('renders provider detail fields', () => {
    const wrapper = mount(ProviderInfoCard, {
      props: { provider, testingConnection: false },
    });
    expect(wrapper.find('[data-testid="provider-detail-fields"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="provider-detail-url"]').text()).toBe(
      'https://gitlab.example.com',
    );
    expect(wrapper.find('[data-testid="provider-detail-username"]').text()).toBe('admin');
  });

  it('emits testConnection when button is clicked', async () => {
    const wrapper = mount(ProviderInfoCard, {
      props: { provider, testingConnection: false },
    });
    await wrapper.find('[data-testid="provider-test-connection"]').trigger('click');
    expect(wrapper.emitted('testConnection')).toHaveLength(1);
  });

  it('disables test button when testingConnection is true', () => {
    const wrapper = mount(ProviderInfoCard, {
      props: { provider, testingConnection: true },
    });
    const button = wrapper.find('[data-testid="provider-test-connection"]');
    expect(button.attributes('disabled')).toBeDefined();
    expect(button.text()).toContain('catalog.providers.testing');
  });

  it('shows status badge', () => {
    const wrapper = mount(ProviderInfoCard, {
      props: { provider, testingConnection: false },
    });
    const badge = wrapper.find('[data-testid="provider-detail-status"]');
    expect(badge.exists()).toBe(true);
    expect(badge.text()).toContain('catalog.providers.statuses.connected');
  });
});
