<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'

import { useAuthStore } from '@/identity/stores/auth'

const authStore = useAuthStore()

onMounted(() => {
  if (authStore.token) {
    authStore.fetchCurrentUser()
  }
})
</script>

<template>
  <RouterView />
</template>
