<script setup lang="ts">
import { onMounted } from 'vue';
import { RouterView } from 'vue-router';

import { useAuthStore } from '@/hub/identity/stores/auth';
import AppToastContainer from '@/hub/shared/components/AppToastContainer.vue';

const authStore = useAuthStore();

onMounted(() => {
  if (authStore.token) {
    authStore.fetchCurrentUser();
  }
});
</script>

<template>
  <RouterView />
  <AppToastContainer />
</template>
