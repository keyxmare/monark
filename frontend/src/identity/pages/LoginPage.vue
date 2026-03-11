<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import AuthLayout from '@/shared/layouts/AuthLayout.vue'
import { api } from '@/shared/utils/api'

const router = useRouter()
const email = ref('')
const password = ref('')
const error = ref('')
const submitting = ref(false)

async function handleSubmit() {
  error.value = ''
  submitting.value = true

  try {
    const response = await api.post<{ token: string }>('/auth/login', {
      email: email.value,
      password: password.value,
    })
    localStorage.setItem('auth_token', response.token)
    router.push({ name: 'dashboard' })
  } catch {
    error.value = 'Invalid credentials'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <div data-testid="login-page">
      <h1 class="mb-6 text-center text-2xl font-bold text-text">Sign in to Monark</h1>

      <form data-testid="login-form" @submit.prevent="handleSubmit">
        <div v-if="error" class="mb-4 rounded-lg bg-danger/10 p-3 text-sm text-danger" role="alert" data-testid="login-error">
          {{ error }}
        </div>

        <div class="mb-4">
          <label for="email" class="mb-1 block text-sm font-medium text-text">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            autocomplete="email"
            class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
            data-testid="login-email"
          />
        </div>

        <div class="mb-6">
          <label for="password" class="mb-1 block text-sm font-medium text-text">Password</label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            autocomplete="current-password"
            class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
            data-testid="login-password"
          />
        </div>

        <button
          type="submit"
          :disabled="submitting"
          class="w-full rounded-lg bg-primary px-4 py-2.5 font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
          data-testid="login-submit"
        >
          {{ submitting ? 'Signing in...' : 'Sign in' }}
        </button>

        <p class="mt-4 text-center text-sm text-text-muted">
          Don't have an account?
          <RouterLink :to="{ name: 'register' }" class="text-primary hover:text-primary-dark" data-testid="login-register-link">
            Sign up
          </RouterLink>
        </p>
      </form>
    </div>
  </AuthLayout>
</template>
