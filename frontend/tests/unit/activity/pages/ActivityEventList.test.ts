import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: {}, query: {} })),
  useRouter: vi.fn(() => ({ push: vi.fn() })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ d: (v: Date) => v.toISOString(), t: (key: string) => key }),
}));

vi.mock('@/shared/layouts/DashboardLayout.vue', () => ({
  default: { template: '<div><slot /></div>' },
}));

const mockFetchAll = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/activity/stores/activity-event', () => ({
  useActivityEventStore: vi.fn(() => ({
    events: [],
    error: null,
    fetchAll: mockFetchAll,
    loading: false,
    ...storeOverrides,
  })),
}));

import ActivityEventList from '@/activity/pages/ActivityEventList.vue';

describe('ActivityEventList', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(ActivityEventList);
    expect(wrapper.find('[data-testid="activity-event-list-page"]').exists()).toBe(true);
  });

  it('calls fetchAll on mount', () => {
    mount(ActivityEventList);
    expect(mockFetchAll).toHaveBeenCalled();
  });

  it('shows loading state', () => {
    storeOverrides = { loading: true };
    const wrapper = mount(ActivityEventList);
    expect(wrapper.find('[data-testid="activity-event-list-loading"]').exists()).toBe(true);
  });

  it('shows error state', () => {
    storeOverrides = { error: 'Failed to fetch' };
    const wrapper = mount(ActivityEventList);
    expect(wrapper.find('[data-testid="activity-event-list-error"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="activity-event-list-error"]').text()).toContain(
      'Failed to fetch',
    );
  });

  it('shows empty state when no events', () => {
    const wrapper = mount(ActivityEventList);
    expect(wrapper.find('[data-testid="activity-event-list-empty"]').exists()).toBe(true);
  });

  it('renders event rows when events exist', () => {
    storeOverrides = {
      events: [
        {
          id: 'e1',
          type: 'project.created',
          entityType: 'project',
          entityId: 'p1',
          payload: {},
          occurredAt: '2025-01-01T00:00:00+00:00',
          userId: 'u1',
        },
      ],
    };
    const wrapper = mount(ActivityEventList);
    const rows = wrapper.findAll('[data-testid="activity-event-list-row"]');
    expect(rows).toHaveLength(1);
  });

  it('renders view links for events', () => {
    storeOverrides = {
      events: [
        {
          id: 'e1',
          type: 'project.created',
          entityType: 'project',
          entityId: 'p1',
          payload: {},
          occurredAt: '2025-01-01T00:00:00+00:00',
          userId: 'u1',
        },
      ],
    };
    const wrapper = mount(ActivityEventList);
    expect(wrapper.find('[data-testid="activity-event-view-link"]').exists()).toBe(true);
  });
});
