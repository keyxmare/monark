export type TokenProvider = 'github' | 'gitlab'

export interface AccessToken {
  id: string
  provider: TokenProvider
  scopes: string[]
  expiresAt: string | null
  userId: string
  createdAt: string
}

export interface CreateAccessTokenInput {
  provider: TokenProvider
  token: string
  scopes: string[]
  expiresAt?: string
}
