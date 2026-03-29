<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

export interface DropdownMenuItem {
  action: string;
  disabled?: boolean;
  label: string;
  variant?: 'danger' | 'default';
}

const props = defineProps<{
  items: DropdownMenuItem[];
}>();

const emit = defineEmits<{
  select: [action: string];
}>();

const open = ref(false);
const focusedIndex = ref(-1);
const menuRef = ref<HTMLDivElement | null>(null);
const itemRefs = ref<HTMLButtonElement[]>([]);

function handleClickOutside(event: MouseEvent) {
  if (menuRef.value && !menuRef.value.contains(event.target as Node)) {
    open.value = false;
  }
}

function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    open.value = false;
  }
}

function handleMenuKeydown(event: KeyboardEvent) {
  const count = props.items.length;
  if (!count) return;

  switch (event.key) {
    case ' ':
    case 'Enter':
      event.preventDefault();
      if (focusedIndex.value >= 0) {
        selectItem(props.items[focusedIndex.value]);
      }
      break;
    case 'ArrowDown':
      event.preventDefault();
      focusedIndex.value = (focusedIndex.value + 1) % count;
      break;
    case 'ArrowUp':
      event.preventDefault();
      focusedIndex.value = (focusedIndex.value - 1 + count) % count;
      break;
    case 'End':
      event.preventDefault();
      focusedIndex.value = count - 1;
      break;
    case 'Home':
      event.preventDefault();
      focusedIndex.value = 0;
      break;
  }
}

function selectItem(item: DropdownMenuItem) {
  if (item.disabled) return;
  open.value = false;
  emit('select', item.action);
}

function setItemRef(el: unknown, index: number) {
  if (el) {
    itemRefs.value[index] = el as HTMLButtonElement;
  }
}

function toggle() {
  open.value = !open.value;
}

watch(open, (isOpen) => {
  if (isOpen) {
    focusedIndex.value = 0;
    nextTick(() => {
      itemRefs.value[0]?.focus();
    });
  } else {
    focusedIndex.value = -1;
  }
});

watch(focusedIndex, (index) => {
  if (index >= 0) {
    nextTick(() => {
      itemRefs.value[index]?.focus();
    });
  }
});

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  document.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
  document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
  <div ref="menuRef" class="relative" data-testid="dropdown-menu">
    <button
      class="cursor-pointer rounded-lg p-1.5 text-text-muted transition-colors hover:bg-background hover:text-text"
      data-testid="dropdown-trigger"
      type="button"
      :aria-expanded="open"
      aria-haspopup="true"
      @click.stop="toggle"
    >
      <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
        <path
          d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"
        />
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
        :aria-activedescendant="focusedIndex >= 0 ? `dropdown-item-${items[focusedIndex]?.action}` : undefined"
        class="absolute right-0 z-10 mt-1 min-w-[160px] rounded-lg border border-border bg-surface py-1 shadow-lg"
        data-testid="dropdown-panel"
        role="menu"
        tabindex="0"
        @keydown="handleMenuKeydown"
      >
        <button
          v-for="(item, index) in items"
          :id="`dropdown-item-${item.action}`"
          :key="item.action"
          :ref="(el) => setItemRef(el, index)"
          :class="[
            item.variant === 'danger'
              ? 'text-danger hover:bg-danger/10'
              : 'text-text hover:bg-background',
            item.disabled ? 'cursor-not-allowed opacity-50' : '',
          ]"
          :data-testid="`dropdown-item-${item.action}`"
          :disabled="item.disabled"
          :tabindex="-1"
          class="flex w-full cursor-pointer items-center px-3 py-2 text-left text-sm transition-colors"
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
