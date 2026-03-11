<script setup lang="ts">
import { onMounted } from 'vue'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useAuthStore } from '@/identity/stores/auth'

const authStore = useAuthStore()

onMounted(() => {
  authStore.fetchCurrentUser()
})
</script>

<template>
  <DashboardLayout>
    <div data-testid="profile-page">
      <h2 class="mb-6 text-2xl font-bold text-text">
        Profile
      </h2>

      <div
        v-if="authStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="profile-loading"
      >
        Loading...
      </div>

      <div
        v-else-if="authStore.currentUser"
        class="max-w-lg rounded-xl border border-border bg-surface p-6"
        data-testid="profile-card"
      >
        <div class="mb-4">
          <p class="text-sm font-medium text-text-muted">
            First Name
          </p>
          <p
            class="text-lg text-text"
            data-testid="profile-first-name"
          >
            {{ authStore.currentUser.firstName }}
          </p>
        </div>
        <div class="mb-4">
          <p class="text-sm font-medium text-text-muted">
            Last Name
          </p>
          <p
            class="text-lg text-text"
            data-testid="profile-last-name"
          >
            {{ authStore.currentUser.lastName }}
          </p>
        </div>
        <div>
          <p class="text-sm font-medium text-text-muted">
            Email
          </p>
          <p
            class="text-lg text-text"
            data-testid="profile-email"
          >
            {{ authStore.currentUser.email }}
          </p>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
