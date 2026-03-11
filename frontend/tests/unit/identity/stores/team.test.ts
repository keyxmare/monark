import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useTeamStore } from '@/identity/stores/team'

vi.mock('@/identity/services/team.service', () => ({
  teamService: {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
  },
}))

import { teamService } from '@/identity/services/team.service'

const mockTeam = {
  id: '789',
  name: 'Engineering',
  slug: 'engineering',
  description: 'The engineering team',
  memberCount: 5,
  createdAt: '2026-01-01T00:00:00+00:00',
  updatedAt: '2026-01-01T00:00:00+00:00',
}

describe('Team Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetches all teams', async () => {
    vi.mocked(teamService.list).mockResolvedValue({
      data: {
        items: [mockTeam],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    })

    const store = useTeamStore()
    await store.fetchAll()

    expect(store.teams).toHaveLength(1)
    expect(store.teams[0].name).toBe('Engineering')
  })

  it('fetches a single team', async () => {
    vi.mocked(teamService.get).mockResolvedValue({
      data: mockTeam,
      status: 200,
    })

    const store = useTeamStore()
    await store.fetchOne('789')

    expect(store.selectedTeam).toEqual(mockTeam)
  })

  it('creates a team', async () => {
    vi.mocked(teamService.create).mockResolvedValue({
      data: mockTeam,
      status: 201,
    })

    const store = useTeamStore()
    const result = await store.create({
      name: 'Engineering',
      slug: 'engineering',
      description: 'The engineering team',
    })

    expect(result.id).toBe('789')
    expect(store.teams).toHaveLength(1)
  })

  it('updates a team', async () => {
    const updatedTeam = { ...mockTeam, name: 'Backend Engineering' }
    vi.mocked(teamService.update).mockResolvedValue({
      data: updatedTeam,
      status: 200,
    })

    const store = useTeamStore()
    store.teams = [mockTeam]
    await store.update('789', { name: 'Backend Engineering' })

    expect(store.selectedTeam?.name).toBe('Backend Engineering')
    expect(store.teams[0].name).toBe('Backend Engineering')
  })

  it('removes a team', async () => {
    vi.mocked(teamService.remove).mockResolvedValue(undefined)

    const store = useTeamStore()
    store.teams = [mockTeam]

    await store.remove('789')

    expect(store.teams).toHaveLength(0)
  })

  it('sets error on fetch failure', async () => {
    vi.mocked(teamService.list).mockRejectedValue(new Error('Network error'))

    const store = useTeamStore()
    await store.fetchAll()

    expect(store.error).toBe('Failed to load teams')
  })
})
