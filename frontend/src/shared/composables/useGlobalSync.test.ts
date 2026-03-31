import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { createGlobalSync } from '@/shared/composables/useGlobalSync';
import { syncService } from '@/shared/services/sync.service';

vi.mock('@/shared/services/sync.service');
vi.mock('@/shared/composables/useMercure', () => ({
  useMercure: vi.fn(() => ({
    data: { value: null },
    connected: { value: false },
    exhausted: { value: false },
    close: vi.fn(),
  })),
}));

describe('createGlobalSync', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('loadCurrent sets currentSync when a running sync exists', async () => {
    const mockState = {
      syncId: 'abc',
      status: 'running' as const,
      currentStep: 1 as const,
      currentStepName: 'sync_projects' as const,
      stepProgress: 0,
      stepTotal: 5,
      completedSteps: [] as never[],
    };
    vi.mocked(syncService.getCurrentSync).mockResolvedValue(mockState);

    const { currentSync, loadCurrent } = createGlobalSync();
    await loadCurrent();

    expect(currentSync.value).toEqual(mockState);
  });

  it('loadCurrent leaves currentSync null when no sync running', async () => {
    vi.mocked(syncService.getCurrentSync).mockResolvedValue(null);

    const { currentSync, loadCurrent } = createGlobalSync();
    await loadCurrent();

    expect(currentSync.value).toBeNull();
  });

  it('isRunning is true when status is running', async () => {
    vi.mocked(syncService.getCurrentSync).mockResolvedValue({
      syncId: 'abc',
      status: 'running',
      currentStep: 2,
      currentStepName: 'sync_versions',
      stepProgress: 10,
      stepTotal: 100,
      completedSteps: ['sync_projects'],
    });

    const { isRunning, loadCurrent } = createGlobalSync();
    await loadCurrent();
    expect(isRunning.value).toBe(true);
  });

  it('startSync calls syncService.startSync and subscribes to mercure', async () => {
    vi.mocked(syncService.startSync).mockResolvedValue({
      syncId: 'xyz',
      status: 'running',
      currentStep: 1,
    });
    vi.mocked(syncService.getCurrentSync).mockResolvedValue({
      syncId: 'xyz',
      status: 'running',
      currentStep: 1,
      currentStepName: 'sync_projects',
      stepProgress: 0,
      stepTotal: 3,
      completedSteps: [],
    });

    const { startSync, currentSync } = createGlobalSync();
    await startSync();

    expect(syncService.startSync).toHaveBeenCalledOnce();
    expect(currentSync.value?.syncId).toBe('xyz');
  });

  it('onStepCompleted callback fires when a new step appears in completedSteps', async () => {
    const { useMercure } = await import('@/shared/composables/useMercure');
    let messageHandler: ((data: unknown) => void) | undefined;
    vi.mocked(useMercure).mockImplementation((_topic, opts) => {
      messageHandler = opts?.onMessage as (data: unknown) => void;
      return {
        data: { value: null },
        connected: { value: false },
        exhausted: { value: false },
        close: vi.fn(),
      };
    });

    vi.mocked(syncService.getCurrentSync).mockResolvedValue({
      syncId: 'abc',
      status: 'running',
      currentStep: 1,
      currentStepName: 'sync_projects',
      stepProgress: 0,
      stepTotal: 2,
      completedSteps: [],
    });

    const { loadCurrent, onStepCompleted } = createGlobalSync();
    await loadCurrent();

    const completedSteps: string[] = [];
    onStepCompleted((step) => completedSteps.push(step));

    messageHandler?.({
      syncId: 'abc',
      status: 'running',
      currentStep: 2,
      currentStepName: 'sync_versions',
      stepProgress: 0,
      stepTotal: 10,
      completedSteps: ['sync_projects'],
    });

    expect(completedSteps).toEqual(['sync_projects']);
  });
});
