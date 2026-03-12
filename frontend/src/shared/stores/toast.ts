import { ref } from 'vue'
import { defineStore } from 'pinia'

export type ToastVariant = 'success' | 'error' | 'info' | 'progress'

export interface ToastProgress {
  current: number
  total: number
}

export interface Toast {
  id: string
  variant: ToastVariant
  title: string
  message?: string
  progress?: ToastProgress
  duration?: number
}

export interface AddToastOptions {
  variant: ToastVariant
  title: string
  message?: string
  progress?: ToastProgress
  duration?: number
}

export interface UpdateToastOptions {
  variant?: ToastVariant
  title?: string
  message?: string
  progress?: ToastProgress
  duration?: number
}

let nextId = 0
const timers = new Map<string, ReturnType<typeof setTimeout>>()

export const useToastStore = defineStore('shared-toast', () => {
  const toasts = ref<Toast[]>([])

  function addToast(options: AddToastOptions): string {
    const id = `toast-${++nextId}`
    const toast: Toast = { id, ...options }
    toasts.value.push(toast)

    if (toast.variant !== 'progress' && toast.duration !== 0) {
      scheduleRemoval(id, toast.duration ?? 5000)
    }

    return id
  }

  function updateToast(id: string, updates: UpdateToastOptions): void {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index === -1) return

    const toast = toasts.value[index]
    toasts.value[index] = { ...toast, ...updates }

    if (updates.variant && updates.variant !== 'progress') {
      scheduleRemoval(id, updates.duration ?? 5000)
    }
  }

  function removeToast(id: string): void {
    clearTimer(id)
    toasts.value = toasts.value.filter(t => t.id !== id)
  }

  function scheduleRemoval(id: string, delay: number): void {
    clearTimer(id)
    timers.set(id, setTimeout(() => removeToast(id), delay))
  }

  function clearTimer(id: string): void {
    const timer = timers.get(id)
    if (timer) {
      clearTimeout(timer)
      timers.delete(id)
    }
  }

  return {
    toasts,
    addToast,
    updateToast,
    removeToast,
  }
})
