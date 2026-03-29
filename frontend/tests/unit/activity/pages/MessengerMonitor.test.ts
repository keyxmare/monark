import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: {}, query: {} })),
  useRouter: vi.fn(() => ({ push: vi.fn() })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

vi.mock('@/shared/layouts/DashboardLayout.vue', () => ({
  default: { template: '<div><slot /></div>' },
}));

vi.mock('@/shared/composables/useMercure', async () => {
  const { ref } = await import('vue');
  return {
    useMercure: vi.fn(() => ({
      connected: ref(false),
      data: ref(null),
    })),
  };
});

const mockFetchStats = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/activity/stores/messenger', () => ({
  useMessengerStore: vi.fn(() => ({
    queues: [],
    workers: [],
    error: null,
    fetchStats: mockFetchStats,
    loading: false,
    ...storeOverrides,
  })),
}));

import MessengerMonitor from '@/activity/pages/MessengerMonitor.vue';

describe('MessengerMonitor', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(MessengerMonitor);
    expect(wrapper.find('[data-testid="messenger-monitor-page"]').exists()).toBe(true);
  });

  it('calls fetchStats on mount', () => {
    mount(MessengerMonitor);
    expect(mockFetchStats).toHaveBeenCalled();
  });

  it('renders the title', () => {
    const wrapper = mount(MessengerMonitor);
    expect(wrapper.find('[data-testid="messenger-title"]').exists()).toBe(true);
  });

  it('renders summary section', () => {
    const wrapper = mount(MessengerMonitor);
    expect(wrapper.find('[data-testid="messenger-summary"]').exists()).toBe(true);
  });

  it('shows loading state when loading and no queues', () => {
    storeOverrides = { loading: true };
    const wrapper = mount(MessengerMonitor);
    expect(wrapper.find('[data-testid="messenger-loading"]').exists()).toBe(true);
  });

  it('shows error state', () => {
    storeOverrides = { error: 'Connection refused' };
    const wrapper = mount(MessengerMonitor);
    expect(wrapper.find('[data-testid="messenger-error"]').exists()).toBe(true);
  });

  it('shows no workers warning when no workers', () => {
    const wrapper = mount(MessengerMonitor);
    expect(wrapper.find('[data-testid="no-workers-warning"]').exists()).toBe(true);
  });

  it('renders workers section when workers exist', () => {
    storeOverrides = {
      workers: [{ connection: 'amqp://localhost:5672', prefetch: 10, state: 'running' }],
    };
    const wrapper = mount(MessengerMonitor);
    expect(wrapper.find('[data-testid="workers-section"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="worker-card-0"]').exists()).toBe(true);
  });

  it('renders queue rows when queues exist', () => {
    storeOverrides = {
      workers: [{ connection: 'amqp://localhost', prefetch: 10, state: 'running' }],
      queues: [
        {
          name: 'async',
          messages: 10,
          messages_ready: 8,
          messages_unacknowledged: 2,
          consumers: 1,
          publish_rate: 5.0,
          deliver_rate: 4.5,
        },
      ],
    };
    const wrapper = mount(MessengerMonitor);
    expect(wrapper.find('[data-testid="queue-row-async"]').exists()).toBe(true);
  });

  it('shows empty state when no queues', () => {
    storeOverrides = {
      workers: [{ connection: 'amqp://localhost', prefetch: 10, state: 'running' }],
    };
    const wrapper = mount(MessengerMonitor);
    expect(wrapper.find('[data-testid="messenger-empty"]').exists()).toBe(true);
  });

  it('renders SSE status indicator', () => {
    const wrapper = mount(MessengerMonitor);
    expect(wrapper.find('[data-testid="sse-status"]').exists()).toBe(true);
  });

  it('renders refresh button', () => {
    const wrapper = mount(MessengerMonitor);
    expect(wrapper.find('[data-testid="refresh-btn"]').exists()).toBe(true);
  });
});
