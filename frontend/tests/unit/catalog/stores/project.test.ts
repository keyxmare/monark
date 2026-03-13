import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useProjectStore } from '@/catalog/stores/project'

vi.mock('@/catalog/services/project.service', () => ({
  projectService: {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
  },
}))

import { projectService } from '@/catalog/services/project.service'

const mockProject = {
  id: 'abc-123',
  name: 'My Project',
  slug: 'my-project',
  description: 'A test project',
  repositoryUrl: 'https://gitlab.com/test/project',
  defaultBranch: 'main',
  visibility: 'private' as const,
  ownerId: 'owner-456',
  techStacksCount: 2,
  createdAt: '2026-01-01T00:00:00+00:00',
  updatedAt: '2026-01-01T00:00:00+00:00',
}

describe('Project Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetches all projects', async () => {
    vi.mocked(projectService.list).mockResolvedValue({
      data: {
        items: [mockProject],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    })

    const store = useProjectStore()
    await store.fetchAll()

    expect(store.projects).toHaveLength(1)
    expect(store.projects[0].name).toBe('My Project')
  })

  it('fetches a single project', async () => {
    vi.mocked(projectService.get).mockResolvedValue({
      data: mockProject,
      status: 200,
    })

    const store = useProjectStore()
    await store.fetchOne('abc-123')

    expect(store.selected).toEqual(mockProject)
  })

  it('creates a project', async () => {
    vi.mocked(projectService.create).mockResolvedValue({
      data: mockProject,
      status: 201,
    })

    const store = useProjectStore()
    const result = await store.create({
      name: 'My Project',
      slug: 'my-project',
      repositoryUrl: 'https://gitlab.com/test/project',
      defaultBranch: 'main',
      visibility: 'private',
      ownerId: 'owner-456',
    })

    expect(result.id).toBe('abc-123')
    expect(store.projects).toHaveLength(1)
  })

  it('updates a project', async () => {
    const updatedProject = { ...mockProject, name: 'Updated Project' }
    vi.mocked(projectService.update).mockResolvedValue({
      data: updatedProject,
      status: 200,
    })

    const store = useProjectStore()
    store.projects = [mockProject]
    await store.update('abc-123', { name: 'Updated Project' })

    expect(store.selected?.name).toBe('Updated Project')
    expect(store.projects[0].name).toBe('Updated Project')
  })

  it('removes a project', async () => {
    vi.mocked(projectService.remove).mockResolvedValue(undefined)

    const store = useProjectStore()
    store.projects = [mockProject]

    await store.remove('abc-123')

    expect(store.projects).toHaveLength(0)
  })

  it('sets error on fetch failure', async () => {
    vi.mocked(projectService.list).mockRejectedValue(new Error('Network error'))

    const store = useProjectStore()
    await store.fetchAll()

    expect(store.error).toBe('Failed to load projects')
  })
})
