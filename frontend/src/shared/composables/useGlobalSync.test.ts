import { beforeEach, describe, expect, it, vi } from 'vitest';

import { useGlobalSync } from '@/shared/composables/useGlobalSync';
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

describe('useGlobalSync', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    useGlobalSync().currentSync.value = null;
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

    const { currentSync, loadCurrent } = useGlobalSync();
    await loadCurrent();

    expect(currentSync.value).toEqual(mockState);
  });

  it('loadCurrent leaves currentSync null when no sync running', async () => {
    vi.mocked(syncService.getCurrentSync).mockResolvedValue(null);

    const { currentSync, loadCurrent } = useGlobalSync();
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

    const { isRunning, loadCurrent } = useGlobalSync();
    await loadCurrent();
    expect(isRunning.value).toBe(true);
  });

  it('startSync calls syncService.startSync', async () => {
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

    const { startSync, currentSync } = useGlobalSync();
    await startSync();

    expect(syncService.startSync).toHaveBeenCalledOnce();
    expect(currentSync.value?.syncId).toBe('xyz');
  });
});
