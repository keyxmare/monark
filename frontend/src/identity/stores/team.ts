import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { CreateTeamInput, Team, UpdateTeamInput } from '@/identity/types/team'
import { teamService } from '@/identity/services/team.service'

export const useTeamStore = defineStore('team', () => {
  const teams = ref<Team[]>([])
  const selectedTeam = ref<Team | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const totalPages = ref(0)
  const currentPage = ref(1)
  const total = ref(0)

  async function fetchAll(page = 1, perPage = 20): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await teamService.list(page, perPage)
      teams.value = response.data.items
      totalPages.value = response.data.total_pages
      currentPage.value = response.data.page
      total.value = response.data.total
    } catch {
      error.value = 'Failed to load teams'
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await teamService.get(id)
      selectedTeam.value = response.data
    } catch {
      error.value = 'Failed to load team'
    } finally {
      loading.value = false
    }
  }

  async function create(data: CreateTeamInput): Promise<Team> {
    loading.value = true
    error.value = null

    try {
      const response = await teamService.create(data)
      teams.value.unshift(response.data)
      return response.data
    } catch {
      error.value = 'Failed to create team'
      throw new Error('Failed to create team')
    } finally {
      loading.value = false
    }
  }

  async function update(id: string, data: UpdateTeamInput): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await teamService.update(id, data)
      selectedTeam.value = response.data
      const index = teams.value.findIndex(t => t.id === id)
      if (index !== -1) {
        teams.value[index] = response.data
      }
    } catch {
      error.value = 'Failed to update team'
    } finally {
      loading.value = false
    }
  }

  async function remove(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      await teamService.remove(id)
      teams.value = teams.value.filter(t => t.id !== id)
    } catch {
      error.value = 'Failed to delete team'
    } finally {
      loading.value = false
    }
  }

  return {
    teams,
    selectedTeam,
    loading,
    error,
    totalPages,
    currentPage,
    total,
    fetchAll,
    fetchOne,
    create,
    update,
    remove,
  }
})
