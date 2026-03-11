import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import type { User } from '@/identity/types/user'
import { authService } from '@/identity/services/auth.service'

export const useAuthStore = defineStore('auth', () => {
  const currentUser = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => token.value !== null)

  async function login(email: string, password: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await authService.login(email, password)
      token.value = response.data.token
      currentUser.value = response.data.user
      localStorage.setItem('auth_token', response.data.token)
    } catch (err) {
      error.value = 'Invalid credentials'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function register(
    email: string,
    password: string,
    firstName: string,
    lastName: string,
  ): Promise<void> {
    loading.value = true
    error.value = null

    try {
      await authService.register({ email, password, firstName, lastName })
    } catch (err) {
      error.value = 'Registration failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function logout(): Promise<void> {
    try {
      await authService.logout()
    } finally {
      token.value = null
      currentUser.value = null
      localStorage.removeItem('auth_token')
    }
  }

  async function fetchCurrentUser(): Promise<void> {
    if (!token.value) return

    loading.value = true
    try {
      const response = await authService.getCurrentUser()
      currentUser.value = response.data
    } catch {
      token.value = null
      currentUser.value = null
      localStorage.removeItem('auth_token')
    } finally {
      loading.value = false
    }
  }

  return {
    currentUser,
    token,
    loading,
    error,
    isAuthenticated,
    login,
    register,
    logout,
    fetchCurrentUser,
  }
})
