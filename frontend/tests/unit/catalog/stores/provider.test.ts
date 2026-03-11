import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useProviderStore } from '@/catalog/stores/provider'

vi.mock('@/catalog/services/provider.service', () => ({
  providerService: {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
    testConnection: vi.fn(),
    listRemoteProjects: vi.fn(),
    importProjects: vi.fn(),
  },
}))

import { providerService } from '@/catalog/services/provider.service'

const mockProvider = {
  id: 'prov-123',
  name: 'GitLab Corp',
  type: 'gitlab' as const,
  url: 'https://gitlab.example.com',
  status: 'pending' as const,
  lastSyncAt: null,
  createdAt: '2026-01-01T00:00:00+00:00',
  updatedAt: '2026-01-01T00:00:00+00:00',
}

const mockRemoteProject = {
  externalId: 'ext-1',
  name: 'Remote Project',
  slug: 'remote-project',
  description: 'A remote project',
  repositoryUrl: 'https://gitlab.example.com/group/project',
  defaultBranch: 'main',
  visibility: 'private',
  avatarUrl: null,
  alreadyImported: false,
}

describe('Provider Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetches all providers', async () => {
    vi.mocked(providerService.list).mockResolvedValue({
      data: {
        items: [mockProvider],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    })

    const store = useProviderStore()
    await store.fetchAll()

    expect(store.providers).toHaveLength(1)
    expect(store.providers[0].name).toBe('GitLab Corp')
  })

  it('fetches a single provider', async () => {
    vi.mocked(providerService.get).mockResolvedValue({
      data: mockProvider,
      status: 200,
    })

    const store = useProviderStore()
    await store.fetchOne('prov-123')

    expect(store.selected).toEqual(mockProvider)
  })

  it('creates a provider', async () => {
    vi.mocked(providerService.create).mockResolvedValue({
      data: mockProvider,
      status: 201,
    })

    const store = useProviderStore()
    const result = await store.create({
      name: 'GitLab Corp',
      type: 'gitlab',
      url: 'https://gitlab.example.com',
      apiToken: 'glpat-xxxx',
    })

    expect(result.id).toBe('prov-123')
    expect(store.providers).toHaveLength(1)
  })

  it('updates a provider', async () => {
    const updatedProvider = { ...mockProvider, name: 'Updated GitLab' }
    vi.mocked(providerService.update).mockResolvedValue({
      data: updatedProvider,
      status: 200,
    })

    const store = useProviderStore()
    store.providers = [mockProvider]
    await store.update('prov-123', { name: 'Updated GitLab' })

    expect(store.selected?.name).toBe('Updated GitLab')
    expect(store.providers[0].name).toBe('Updated GitLab')
  })

  it('removes a provider', async () => {
    vi.mocked(providerService.remove).mockResolvedValue(undefined)

    const store = useProviderStore()
    store.providers = [mockProvider]

    await store.remove('prov-123')

    expect(store.providers).toHaveLength(0)
  })

  it('tests connection successfully', async () => {
    vi.mocked(providerService.testConnection).mockResolvedValue({
      data: { connected: true },
      status: 200,
    })

    const store = useProviderStore()
    store.providers = [mockProvider]
    store.selected = { ...mockProvider }

    const result = await store.testConnection('prov-123')

    expect(result).toBe(true)
    expect(store.selected?.status).toBe('connected')
    expect(store.providers[0].status).toBe('connected')
  })

  it('tests connection with failure', async () => {
    vi.mocked(providerService.testConnection).mockResolvedValue({
      data: { connected: false },
      status: 200,
    })

    const store = useProviderStore()
    store.providers = [mockProvider]
    store.selected = { ...mockProvider }

    const result = await store.testConnection('prov-123')

    expect(result).toBe(false)
    expect(store.selected?.status).toBe('error')
  })

  it('fetches remote projects', async () => {
    vi.mocked(providerService.listRemoteProjects).mockResolvedValue({
      data: {
        items: [mockRemoteProject],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    })

    const store = useProviderStore()
    await store.fetchRemoteProjects('prov-123')

    expect(store.remoteProjects).toHaveLength(1)
    expect(store.remoteProjects[0].name).toBe('Remote Project')
  })

  it('imports projects and marks them as imported', async () => {
    const mockImportedProject = {
      id: 'proj-1',
      name: 'Remote Project',
      slug: 'remote-project',
      description: 'A remote project',
      repositoryUrl: 'https://gitlab.example.com/group/project',
      defaultBranch: 'main',
      visibility: 'private' as const,
      ownerId: 'owner-1',
      techStacksCount: 0,
      pipelinesCount: 0,
      createdAt: '2026-01-01T00:00:00+00:00',
      updatedAt: '2026-01-01T00:00:00+00:00',
    }

    vi.mocked(providerService.importProjects).mockResolvedValue({
      data: [mockImportedProject],
      status: 201,
    })

    const store = useProviderStore()
    store.remoteProjects = [mockRemoteProject]

    await store.importProjects('prov-123', {
      projects: [{
        externalId: 'ext-1',
        name: 'Remote Project',
        slug: 'remote-project',
        description: 'A remote project',
        repositoryUrl: 'https://gitlab.example.com/group/project',
        defaultBranch: 'main',
        visibility: 'private',
      }],
    })

    expect(store.remoteProjects[0].alreadyImported).toBe(true)
  })

  it('sets error on fetch failure', async () => {
    vi.mocked(providerService.list).mockRejectedValue(new Error('Network error'))

    const store = useProviderStore()
    await store.fetchAll()

    expect(store.error).toBe('Failed to load providers')
  })
})
