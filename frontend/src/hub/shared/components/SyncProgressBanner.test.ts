import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import SyncProgressBanner from '@/hub/shared/components/SyncProgressBanner.vue';
import type { GlobalSyncState } from '@/hub/shared/types/globalSync';

const mockCurrentSync = ref<GlobalSyncState | null>(null);

vi.mock('@/hub/shared/composables/useGlobalSync', () => ({
  useGlobalSync: () => ({
    currentSync: mockCurrentSync,
    isRunning: ref(false),
    loadCurrent: vi.fn(),
    onStepCompleted: vi.fn(),
    startSync: vi.fn(),
  }),
}));

function mountWithSync(state: GlobalSyncState | null) {
  mockCurrentSync.value = state;
  return mount(SyncProgressBanner);
}

describe('SyncProgressBanner', () => {
  it('is hidden when no sync', async () => {
    const wrapper = mountWithSync(null);
    await wrapper.vm.$nextTick();
    expect(wrapper.find('[data-testid="sync-progress-banner"]').exists()).toBe(false);
  });

  it('shows all 5 steps when running', async () => {
    const wrapper = mountWithSync({
      completedSteps: [],
      currentStep: 1,
      currentStepName: 'sync_projects',
      status: 'running',
      stepProgress: 2,
      stepTotal: 5,
      syncId: 'abc',
    });
    await wrapper.vm.$nextTick();
    expect(wrapper.find('[data-testid="sync-progress-banner"]').exists()).toBe(true);
    expect(wrapper.findAll('[data-testid="step-active"]')).toHaveLength(1);
    expect(wrapper.findAll('[data-testid="step-pending"]')).toHaveLength(4);
  });

  it('shows progress bar with correct width', async () => {
    const wrapper = mountWithSync({
      completedSteps: ['sync_projects'],
      currentStep: 3,
      currentStepName: 'sync_dependencies',
      status: 'running',
      stepProgress: 50,
      stepTotal: 100,
      syncId: 'abc',
    });
    await wrapper.vm.$nextTick();
    const bar = wrapper.find('[data-testid="sync-progress-bar"]');
    expect(bar.attributes('style')).toContain('50%');
  });

  it('shows all steps as completed when status is completed', async () => {
    const wrapper = mountWithSync({
      completedSteps: [
        'sync_projects',
        'sync_coverage',
        'sync_dependencies',
        'sync_frameworks',
        'scan_cve',
      ],
      currentStep: 5,
      currentStepName: 'scan_cve',
      status: 'completed',
      stepProgress: 0,
      stepTotal: 0,
      syncId: 'abc',
    });
    await wrapper.vm.$nextTick();
    expect(wrapper.findAll('[data-testid="step-completed"]')).toHaveLength(5);
  });
});
