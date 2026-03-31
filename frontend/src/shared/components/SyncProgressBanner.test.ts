import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import SyncProgressBanner from '@/shared/components/SyncProgressBanner.vue';
import { GLOBAL_SYNC_KEY } from '@/shared/composables/useGlobalSync';
import type { GlobalSyncState } from '@/shared/types/globalSync';

function mountWithSync(state: GlobalSyncState | null) {
  const currentSync = ref(state);
  const isRunning = ref(state?.status === 'running');
  return mount(SyncProgressBanner, {
    global: {
      provide: {
        [GLOBAL_SYNC_KEY as symbol]: {
          currentSync,
          isRunning,
          startSync: vi.fn(),
          loadCurrent: vi.fn(),
          onStepCompleted: vi.fn(),
        },
      },
    },
  });
}

describe('SyncProgressBanner', () => {
  it('is hidden when no sync', async () => {
    const wrapper = mountWithSync(null);
    await wrapper.vm.$nextTick();
    expect(wrapper.find('[data-testid="sync-progress-banner"]').exists()).toBe(false);
  });

  it('shows all 3 steps when running', async () => {
    const wrapper = mountWithSync({
      syncId: 'abc',
      status: 'running',
      currentStep: 1,
      currentStepName: 'sync_projects',
      stepProgress: 2,
      stepTotal: 5,
      completedSteps: [],
    });
    await wrapper.vm.$nextTick();
    expect(wrapper.find('[data-testid="sync-progress-banner"]').exists()).toBe(true);
    expect(wrapper.findAll('[data-testid="step-active"]')).toHaveLength(1);
    expect(wrapper.findAll('[data-testid="step-pending"]')).toHaveLength(2);
  });

  it('shows progress bar with correct width', async () => {
    const wrapper = mountWithSync({
      syncId: 'abc',
      status: 'running',
      currentStep: 2,
      currentStepName: 'sync_versions',
      stepProgress: 50,
      stepTotal: 100,
      completedSteps: ['sync_projects'],
    });
    await wrapper.vm.$nextTick();
    const bar = wrapper.find('[data-testid="sync-progress-bar"]');
    expect(bar.attributes('style')).toContain('50%');
  });

  it('shows all steps as completed when status is completed', async () => {
    const wrapper = mountWithSync({
      syncId: 'abc',
      status: 'completed',
      currentStep: 3,
      currentStepName: 'scan_cve',
      stepProgress: 0,
      stepTotal: 0,
      completedSteps: ['sync_projects', 'sync_versions', 'scan_cve'],
    });
    await wrapper.vm.$nextTick();
    expect(wrapper.findAll('[data-testid="step-completed"]')).toHaveLength(3);
  });
});
