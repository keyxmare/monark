<script setup lang="ts">
import AppToast from '@/shared/components/AppToast.vue';
import { useToastStore } from '@/shared/stores/toast';

const toastStore = useToastStore();
</script>

<template>
  <Teleport to="body">
    <div
      aria-live="polite"
      role="status"
      class="pointer-events-none fixed right-4 bottom-4 z-50 flex flex-col-reverse gap-3"
      data-testid="toast-container"
    >
      <TransitionGroup
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="translate-x-full opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-full opacity-0"
      >
        <AppToast
          v-for="toast in toastStore.toasts"
          :key="toast.id"
          :toast="toast"
          @close="toastStore.removeToast"
        />
      </TransitionGroup>
    </div>
  </Teleport>
</template>
