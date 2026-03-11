export interface User {
  id: string
  email: string
  firstName: string
  lastName: string
  avatar: string | null
  roles: string[]
  createdAt: string
  updatedAt: string
}

export interface CreateUserInput {
  email: string
  password: string
  firstName: string
  lastName: string
}

export interface UpdateUserInput {
  firstName?: string
  lastName?: string
  avatar?: string
  email?: string
}

export interface AuthTokenResponse {
  token: string
  user: User
}
