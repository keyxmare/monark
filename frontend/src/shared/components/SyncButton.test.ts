import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import SyncButton from '@/shared/components/SyncButton.vue';
import { GLOBAL_SYNC_KEY } from '@/shared/composables/useGlobalSync';
import { i18n } from '@/shared/i18n';

function mountButton(isRunning: boolean, startSync = vi.fn()) {
  const currentSync = ref(
    isRunning
      ? {
          syncId: 'x',
          status: 'running' as const,
          currentStep: 1 as const,
          currentStepName: 'sync_projects' as const,
          stepProgress: 0,
          stepTotal: 0,
          completedSteps: [] as never[],
        }
      : null,
  );
  return mount(SyncButton, {
    global: {
      plugins: [i18n],
      provide: {
        [GLOBAL_SYNC_KEY as symbol]: {
          currentSync,
          isRunning: ref(isRunning),
          startSync,
          loadCurrent: vi.fn(),
          onStepCompleted: vi.fn(),
        },
      },
    },
  });
}

describe('SyncButton', () => {
  it('is enabled when not running', () => {
    const wrapper = mountButton(false);
    expect(wrapper.find('[data-testid="sync-button"]').attributes('disabled')).toBeUndefined();
  });

  it('is disabled when running', () => {
    const wrapper = mountButton(true);
    expect(wrapper.find('[data-testid="sync-button"]').attributes('disabled')).toBeDefined();
  });

  it('calls startSync on click', async () => {
    const startSync = vi.fn().mockResolvedValue(undefined);
    const wrapper = mountButton(false, startSync);
    await wrapper.find('[data-testid="sync-button"]').trigger('click');
    expect(startSync).toHaveBeenCalledOnce();
  });

  it('shows step info when running', () => {
    const wrapper = mountButton(true);
    expect(wrapper.text()).toContain('Step 1/3');
  });
});
