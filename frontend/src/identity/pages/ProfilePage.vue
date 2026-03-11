<script setup lang="ts">
import { onMounted, ref } from 'vue'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { api } from '@/shared/utils/api'

interface UserProfile {
  email: string
  name: string
}

const profile = ref<UserProfile | null>(null)
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    profile.value = await api.get<UserProfile>('/auth/profile')
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <DashboardLayout>
    <div data-testid="profile-page">
      <h2 class="mb-6 text-2xl font-bold text-text">Profile</h2>

      <div v-if="loading" class="py-8 text-center text-text-muted" data-testid="profile-loading">
        Loading...
      </div>

      <div v-else-if="profile" class="max-w-lg rounded-xl border border-border bg-surface p-6" data-testid="profile-card">
        <div class="mb-4">
          <p class="text-sm font-medium text-text-muted">Name</p>
          <p class="text-lg text-text" data-testid="profile-name">{{ profile.name }}</p>
        </div>
        <div>
          <p class="text-sm font-medium text-text-muted">Email</p>
          <p class="text-lg text-text" data-testid="profile-email">{{ profile.email }}</p>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
