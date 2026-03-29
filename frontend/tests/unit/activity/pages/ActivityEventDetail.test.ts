import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: { id: 'e1' }, query: {} })),
  useRouter: vi.fn(() => ({ push: vi.fn() })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({
    d: (v: Date) => v.toISOString(),
    t: (key: string, params?: Record<string, string>) => key,
  }),
}));

vi.mock('@/shared/layouts/DashboardLayout.vue', () => ({
  default: { template: '<div><slot /></div>' },
}));

const mockFetchOne = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/activity/stores/activity-event', () => ({
  useActivityEventStore: vi.fn(() => ({
    selectedEvent: null,
    error: null,
    fetchOne: mockFetchOne,
    loading: false,
    ...storeOverrides,
  })),
}));

import ActivityEventDetail from '@/activity/pages/ActivityEventDetail.vue';

describe('ActivityEventDetail', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(ActivityEventDetail);
    expect(wrapper.find('[data-testid="activity-event-detail-page"]').exists()).toBe(true);
  });

  it('calls fetchOne on mount', () => {
    mount(ActivityEventDetail);
    expect(mockFetchOne).toHaveBeenCalledWith('e1');
  });

  it('shows loading state', () => {
    storeOverrides = { loading: true };
    const wrapper = mount(ActivityEventDetail);
    expect(wrapper.find('[data-testid="activity-event-detail-loading"]').exists()).toBe(true);
  });

  it('shows error state', () => {
    storeOverrides = { error: 'Not found' };
    const wrapper = mount(ActivityEventDetail);
    expect(wrapper.find('[data-testid="activity-event-detail-error"]').exists()).toBe(true);
  });

  it('renders event details when loaded', () => {
    storeOverrides = {
      selectedEvent: {
        id: 'e1',
        type: 'project.created',
        entityType: 'project',
        entityId: 'p1',
        payload: { key: 'value' },
        occurredAt: '2025-01-01T00:00:00+00:00',
        userId: 'u1',
      },
    };
    const wrapper = mount(ActivityEventDetail);
    expect(wrapper.find('[data-testid="activity-event-detail-card"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="activity-event-detail-type"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="activity-event-detail-entity-type"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="activity-event-detail-entity-id"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="activity-event-detail-user-id"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="activity-event-detail-payload"]').exists()).toBe(true);
  });

  it('renders back link', () => {
    const wrapper = mount(ActivityEventDetail);
    expect(wrapper.find('[data-testid="activity-event-detail-back"]').exists()).toBe(true);
  });
});
