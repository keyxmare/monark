import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useTechStackStore } from '@/catalog/stores/tech-stack'

vi.mock('@/catalog/services/tech-stack.service', () => ({
  techStackService: {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    remove: vi.fn(),
  },
}))

import { techStackService } from '@/catalog/services/tech-stack.service'

const mockTechStack = {
  id: 'ts-123',
  language: 'PHP',
  framework: 'Symfony',
  version: '8.0',
  detectedAt: '2026-03-10T12:00:00+00:00',
  projectId: 'project-456',
  createdAt: '2026-03-10T12:00:00+00:00',
}

describe('TechStack Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetches all tech stacks', async () => {
    vi.mocked(techStackService.list).mockResolvedValue({
      data: {
        items: [mockTechStack],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    })

    const store = useTechStackStore()
    await store.fetchAll()

    expect(store.techStacks).toHaveLength(1)
    expect(store.techStacks[0].language).toBe('PHP')
  })

  it('fetches a single tech stack', async () => {
    vi.mocked(techStackService.get).mockResolvedValue({
      data: mockTechStack,
      status: 200,
    })

    const store = useTechStackStore()
    await store.fetchOne('ts-123')

    expect(store.selected).toEqual(mockTechStack)
  })

  it('creates a tech stack', async () => {
    vi.mocked(techStackService.create).mockResolvedValue({
      data: mockTechStack,
      status: 201,
    })

    const store = useTechStackStore()
    const result = await store.create({
      language: 'PHP',
      framework: 'Symfony',
      version: '8.0',
      detectedAt: '2026-03-10T12:00:00+00:00',
      projectId: 'project-456',
    })

    expect(result.id).toBe('ts-123')
    expect(store.techStacks).toHaveLength(1)
  })

  it('removes a tech stack', async () => {
    vi.mocked(techStackService.remove).mockResolvedValue(undefined)

    const store = useTechStackStore()
    store.techStacks = [mockTechStack]

    await store.remove('ts-123')

    expect(store.techStacks).toHaveLength(0)
  })

  it('sets error on fetch failure', async () => {
    vi.mocked(techStackService.list).mockRejectedValue(new Error('Network error'))

    const store = useTechStackStore()
    await store.fetchAll()

    expect(store.error).toBe('Failed to load tech stacks')
  })
})
