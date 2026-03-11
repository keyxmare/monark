export interface Team {
  id: string
  name: string
  slug: string
  description: string | null
  memberCount: number
  createdAt: string
  updatedAt: string
}

export interface CreateTeamInput {
  name: string
  slug: string
  description?: string
}

export interface UpdateTeamInput {
  name?: string
  slug?: string
  description?: string
}
