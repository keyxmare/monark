import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import SyncButton from '@/shared/components/SyncButton.vue';
import { i18n } from '@/shared/i18n';

const mockStartSync = vi.fn();
const mockCurrentSync = ref<unknown>(null);
const mockIsRunning = ref(false);

vi.mock('@/shared/composables/useGlobalSync', () => ({
  useGlobalSync: () => ({
    currentSync: mockCurrentSync,
    isRunning: mockIsRunning,
    startSync: mockStartSync,
    loadCurrent: vi.fn(),
    onStepCompleted: vi.fn(),
  }),
}));

function mountButton(running: boolean) {
  mockIsRunning.value = running;
  mockCurrentSync.value = running
    ? {
        syncId: 'x',
        status: 'running',
        currentStep: 1,
        currentStepName: 'sync_projects',
        stepProgress: 0,
        stepTotal: 0,
        completedSteps: [],
      }
    : null;
  return mount(SyncButton, { global: { plugins: [i18n] } });
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
    mockStartSync.mockResolvedValue(undefined);
    const wrapper = mountButton(false);
    await wrapper.find('[data-testid="sync-button"]').trigger('click');
    expect(mockStartSync).toHaveBeenCalledOnce();
  });

  it('shows step info when running', () => {
    const wrapper = mountButton(true);
    expect(wrapper.text()).toContain('Step 1/3');
  });
});
