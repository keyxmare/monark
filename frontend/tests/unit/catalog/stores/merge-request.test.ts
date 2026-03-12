import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useMergeRequestStore } from '@/catalog/stores/merge-request'

vi.mock('@/catalog/services/merge-request.service', () => ({
  mergeRequestService: {
    list: vi.fn(),
    get: vi.fn(),
  },
}))

import { mergeRequestService } from '@/catalog/services/merge-request.service'

const mockMergeRequest = {
  id: 'mr-123',
  externalId: '42',
  title: 'feat: login',
  description: 'Login page',
  sourceBranch: 'feature/login',
  targetBranch: 'main',
  status: 'open' as const,
  author: 'dev',
  url: 'https://gitlab.com/test/-/merge_requests/42',
  additions: 100,
  deletions: 20,
  reviewers: ['alice'],
  labels: ['feature'],
  mergedAt: null,
  closedAt: null,
  projectId: 'project-456',
  createdAt: '2026-03-10T10:00:00+00:00',
  updatedAt: '2026-03-11T14:00:00+00:00',
}

describe('MergeRequest Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetches all merge requests for a project', async () => {
    vi.mocked(mergeRequestService.list).mockResolvedValue({
      data: {
        items: [mockMergeRequest],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    })

    const store = useMergeRequestStore()
    await store.fetchAll('project-456')

    expect(store.mergeRequests).toHaveLength(1)
    expect(store.mergeRequests[0].externalId).toBe('42')
    expect(store.total).toBe(1)
    expect(mergeRequestService.list).toHaveBeenCalledWith('project-456', 1, 20, undefined, undefined)
  })

  it('fetches a single merge request', async () => {
    vi.mocked(mergeRequestService.get).mockResolvedValue({
      data: mockMergeRequest,
      status: 200,
    })

    const store = useMergeRequestStore()
    await store.fetchOne('mr-123')

    expect(store.selected).toEqual(mockMergeRequest)
  })

  it('fetches merge requests with filters', async () => {
    vi.mocked(mergeRequestService.list).mockResolvedValue({
      data: {
        items: [mockMergeRequest],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    })

    const store = useMergeRequestStore()
    await store.fetchAll('project-456', 1, 20, 'open', 'dev')

    expect(mergeRequestService.list).toHaveBeenCalledWith('project-456', 1, 20, 'open', 'dev')
    expect(store.mergeRequests).toHaveLength(1)
  })

  it('sets error on fetch failure', async () => {
    vi.mocked(mergeRequestService.list).mockRejectedValue(new Error('Network error'))

    const store = useMergeRequestStore()
    await store.fetchAll('project-456')

    expect(store.error).toBe('Failed to load merge requests')
  })
})
