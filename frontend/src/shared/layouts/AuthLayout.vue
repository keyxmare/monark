<script setup lang="ts">
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-surface-muted">
    <div
      class="w-full max-w-md rounded-xl bg-surface p-8 shadow-lg"
      data-testid="auth-container"
    >
      <slot />
    </div>
  </div>
</template>
