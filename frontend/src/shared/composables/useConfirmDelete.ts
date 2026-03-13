import type { Ref } from 'vue'

import { computed, ref } from 'vue'

export function useConfirmDelete<T = unknown>() {
  const target = ref<T | null>(null) as Ref<T | null>
  const isOpen = computed(() => target.value !== null)

  function requestDelete(item: T) {
    target.value = item
  }

  function cancel() {
    target.value = null
  }

  async function confirm(deleteFn: () => Promise<void>) {
    await deleteFn()
    target.value = null
  }

  return { target, isOpen, requestDelete, cancel, confirm }
}
