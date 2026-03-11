<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import AuthLayout from '@/shared/layouts/AuthLayout.vue'
import { api } from '@/shared/utils/api'

const router = useRouter()
const name = ref('')
const email = ref('')
const password = ref('')
const error = ref('')
const submitting = ref(false)

async function handleSubmit() {
  error.value = ''
  submitting.value = true

  try {
    await api.post('/auth/register', {
      email: email.value,
      name: name.value,
      password: password.value,
    })
    router.push({ name: 'login' })
  } catch {
    error.value = 'Registration failed. Please try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <div data-testid="register-page">
      <h1 class="mb-6 text-center text-2xl font-bold text-text">
        Create an account
      </h1>

      <form
        data-testid="register-form"
        @submit.prevent="handleSubmit"
      >
        <div
          v-if="error"
          class="mb-4 rounded-lg bg-danger/10 p-3 text-sm text-danger"
          role="alert"
          data-testid="register-error"
        >
          {{ error }}
        </div>

        <div class="mb-4">
          <label
            for="name"
            class="mb-1 block text-sm font-medium text-text"
          >Name</label>
          <input
            id="name"
            v-model="name"
            type="text"
            required
            autocomplete="name"
            class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
            data-testid="register-name"
          >
        </div>

        <div class="mb-4">
          <label
            for="email"
            class="mb-1 block text-sm font-medium text-text"
          >Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            autocomplete="email"
            class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
            data-testid="register-email"
          >
        </div>

        <div class="mb-6">
          <label
            for="password"
            class="mb-1 block text-sm font-medium text-text"
          >Password</label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            autocomplete="new-password"
            class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
            data-testid="register-password"
          >
        </div>

        <button
          type="submit"
          :disabled="submitting"
          class="w-full rounded-lg bg-primary px-4 py-2.5 font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
          data-testid="register-submit"
        >
          {{ submitting ? 'Creating account...' : 'Create account' }}
        </button>

        <p class="mt-4 text-center text-sm text-text-muted">
          Already have an account?
          <RouterLink
            :to="{ name: 'login' }"
            class="text-primary hover:text-primary-dark"
            data-testid="register-login-link"
          >
            Sign in
          </RouterLink>
        </p>
      </form>
    </div>
  </AuthLayout>
</template>
