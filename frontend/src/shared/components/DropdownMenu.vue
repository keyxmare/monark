<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'

export interface DropdownMenuItem {
  action: string
  disabled?: boolean
  label: string
  variant?: 'danger' | 'default'
}

defineProps<{
  items: DropdownMenuItem[]
}>()

const emit = defineEmits<{
  select: [action: string]
}>()

const open = ref(false)
const menuRef = ref<HTMLDivElement | null>(null)

function handleClickOutside(event: MouseEvent) {
  if (menuRef.value && !menuRef.value.contains(event.target as Node)) {
    open.value = false
  }
}

function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    open.value = false
  }
}

function selectItem(item: DropdownMenuItem) {
  if (item.disabled) return
  open.value = false
  emit('select', item.action)
}

function toggle() {
  open.value = !open.value
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  document.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
  <div
    ref="menuRef"
    class="relative"
    data-testid="dropdown-menu"
  >
    <button
      class="rounded-lg p-1.5 text-text-muted transition-colors hover:bg-background hover:text-text"
      data-testid="dropdown-trigger"
      type="button"
      :aria-expanded="open"
      aria-haspopup="true"
      @click.stop="toggle"
    >
      <svg
        class="h-5 w-5"
        fill="currentColor"
        viewBox="0 0 20 20"
      >
        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z" />
      </svg>
    </button>

    <Transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="scale-95 opacity-0"
      enter-to-class="scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="scale-100 opacity-100"
      leave-to-class="scale-95 opacity-0"
    >
      <div
        v-if="open"
        class="absolute right-0 z-10 mt-1 min-w-[160px] rounded-lg border border-border bg-surface py-1 shadow-lg"
        data-testid="dropdown-panel"
        role="menu"
      >
        <button
          v-for="item in items"
          :key="item.action"
          :class="[
            item.variant === 'danger'
              ? 'text-danger hover:bg-danger/10'
              : 'text-text hover:bg-background',
            item.disabled ? 'cursor-not-allowed opacity-50' : '',
          ]"
          :data-testid="`dropdown-item-${item.action}`"
          :disabled="item.disabled"
          class="flex w-full items-center px-3 py-2 text-left text-sm transition-colors"
          role="menuitem"
          type="button"
          @click.stop="selectItem(item)"
        >
          {{ item.label }}
        </button>
      </div>
    </Transition>
  </div>
</template>
