import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { usePipelineStore } from '@/catalog/stores/pipeline'

vi.mock('@/catalog/services/pipeline.service', () => ({
  pipelineService: {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
  },
}))

import { pipelineService } from '@/catalog/services/pipeline.service'

const mockPipeline = {
  id: 'pipe-123',
  externalId: '12345',
  ref: 'main',
  status: 'success' as const,
  duration: 120,
  startedAt: '2026-03-10T12:00:00+00:00',
  finishedAt: '2026-03-10T12:02:00+00:00',
  projectId: 'project-456',
  createdAt: '2026-03-10T12:00:00+00:00',
}

describe('Pipeline Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetches all pipelines', async () => {
    vi.mocked(pipelineService.list).mockResolvedValue({
      data: {
        items: [mockPipeline],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    })

    const store = usePipelineStore()
    await store.fetchAll()

    expect(store.pipelines).toHaveLength(1)
    expect(store.pipelines[0].externalId).toBe('12345')
  })

  it('fetches a single pipeline', async () => {
    vi.mocked(pipelineService.get).mockResolvedValue({
      data: mockPipeline,
      status: 200,
    })

    const store = usePipelineStore()
    await store.fetchOne('pipe-123')

    expect(store.selected).toEqual(mockPipeline)
  })

  it('creates a pipeline', async () => {
    vi.mocked(pipelineService.create).mockResolvedValue({
      data: mockPipeline,
      status: 201,
    })

    const store = usePipelineStore()
    const result = await store.create({
      externalId: '12345',
      ref: 'main',
      status: 'success',
      duration: 120,
      startedAt: '2026-03-10T12:00:00+00:00',
      finishedAt: '2026-03-10T12:02:00+00:00',
      projectId: 'project-456',
    })

    expect(result.id).toBe('pipe-123')
    expect(store.pipelines).toHaveLength(1)
  })

  it('fetches pipelines filtered by ref', async () => {
    vi.mocked(pipelineService.list).mockResolvedValue({
      data: {
        items: [mockPipeline],
        total: 1,
        page: 1,
        per_page: 10,
        total_pages: 1,
      },
      status: 200,
    })

    const store = usePipelineStore()
    await store.fetchAll(1, 10, 'project-456', 'main')

    expect(pipelineService.list).toHaveBeenCalledWith(1, 10, 'project-456', 'main')
    expect(store.pipelines).toHaveLength(1)
  })

  it('sets error on fetch failure', async () => {
    vi.mocked(pipelineService.list).mockRejectedValue(new Error('Network error'))

    const store = usePipelineStore()
    await store.fetchAll()

    expect(store.error).toBe('Failed to load pipelines')
  })
})
