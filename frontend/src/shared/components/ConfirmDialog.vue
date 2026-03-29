<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = withDefaults(
  defineProps<{
    cancelLabel?: string;
    confirmLabel?: string;
    message?: string;
    open: boolean;
    title?: string;
    variant?: 'danger' | 'default';
  }>(),
  {
    cancelLabel: undefined,
    confirmLabel: undefined,
    message: undefined,
    title: undefined,
    variant: 'default',
  },
);

const emit = defineEmits<{
  cancel: [];
  confirm: [];
}>();

const { t } = useI18n();
const dialogRef = ref<HTMLDialogElement | null>(null);

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      dialogRef.value?.showModal();
    } else {
      dialogRef.value?.close();
    }
  },
);

function handleCancel() {
  emit('cancel');
}

function handleConfirm() {
  emit('confirm');
}
</script>

<template>
  <Teleport to="body">
    <dialog
      ref="dialogRef"
      class="m-auto max-w-md rounded-xl border border-border bg-surface p-0 shadow-xl backdrop:bg-black/50"
      data-testid="confirm-dialog"
      @cancel.prevent="handleCancel"
    >
      <form
        v-if="open"
        method="dialog"
        class="p-6"
        @submit.prevent="handleConfirm"
      >
        <h3
          class="mb-2 text-lg font-semibold text-text"
          data-testid="confirm-dialog-title"
        >
          {{ title ?? t('common.confirm.title') }}
        </h3>
        <p
          class="mb-6 text-sm text-text-muted"
          data-testid="confirm-dialog-message"
        >
          <slot>{{ message ?? t('common.confirm.deleteMessage') }}</slot>
        </p>
        <div class="flex justify-end gap-3">
          <button
            class="rounded-lg border border-border bg-surface px-4 py-2 text-sm font-medium text-text transition-colors hover:bg-background"
            data-testid="confirm-dialog-cancel"
            @click="handleCancel"
          >
            {{ cancelLabel ?? t('common.actions.cancel') }}
          </button>
          <button
            :class="
              variant === 'danger'
                ? 'bg-danger text-white hover:bg-danger/80'
                : 'bg-primary text-white hover:bg-primary-dark'
            "
            class="rounded-lg px-4 py-2 text-sm font-medium transition-colors"
            data-testid="confirm-dialog-confirm"
            @click="handleConfirm"
          >
            {{ confirmLabel ?? t('common.actions.confirm') }}
          </button>
        </div>
      </form>
    </dialog>
  </Teleport>
</template>
