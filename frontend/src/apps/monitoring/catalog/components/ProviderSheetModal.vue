<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';

import type {
  CreateProviderInput,
  Provider,
  ProviderType,
  UpdateProviderInput,
} from '@/apps/monitoring/catalog/types/provider';

import { useProviderStore } from '@/apps/monitoring/catalog/stores/provider';

const props = defineProps<{
  provider?: null | Provider;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'saved', provider: Provider): void;
  (e: 'deleted'): void;
}>();

const { t } = useI18n();
const providerStore = useProviderStore();

const isEdit = computed(() => !!props.provider);

const DEFAULT_URL: Record<ProviderType, string> = {
  bitbucket: 'https://api.bitbucket.org/2.0',
  github: 'https://api.github.com',
  gitlab: 'https://gitlab.com/api/v4',
};

const form = reactive({
  apiToken: '',
  name: props.provider?.name ?? '',
  type: (props.provider?.type ?? 'github') as ProviderType,
  url: props.provider?.url ?? DEFAULT_URL.github,
  username: props.provider?.username ?? '',
});

function onTypeChange(next: ProviderType) {
  form.type = next;
  if (!isEdit.value) {
    form.url = DEFAULT_URL[next];
  }
}

const submitting = ref(false);
const testing = ref(false);
const testResult = ref<null | { message: string; ok: boolean }>(null);
const formError = ref('');

async function disconnect() {
  if (!props.provider) return;
  const ok = window.confirm(t('monitoring.repos.modal.confirm_disconnect'));
  if (!ok) return;
  submitting.value = true;
  try {
    await providerStore.remove(props.provider.id);
    emit('deleted');
    emit('close');
  } finally {
    submitting.value = false;
  }
}

function glyph(type: ProviderType): string {
  if (type === 'github') return 'gh';
  if (type === 'gitlab') return 'gl';
  return 'bb';
}

const dialogRef = ref<HTMLDialogElement | null>(null);

async function runTest() {
  if (!props.provider) return;
  testing.value = true;
  testResult.value = null;
  try {
    const ok = await providerStore.testConnection(props.provider.id);
    testResult.value = ok
      ? { message: t('monitoring.repos.modal.test_ok'), ok: true }
      : { message: t('monitoring.repos.modal.test_fail'), ok: false };
  } finally {
    testing.value = false;
  }
}

async function save() {
  formError.value = '';
  if (!form.name.trim() || !form.url.trim()) {
    formError.value = t('monitoring.repos.modal.validation_required');
    return;
  }
  submitting.value = true;
  try {
    if (isEdit.value && props.provider) {
      const patch: UpdateProviderInput = {
        name: form.name.trim(),
        url: form.url.trim(),
        username: form.username.trim() || undefined,
      };
      if (form.apiToken) patch.apiToken = form.apiToken;
      await providerStore.update(props.provider.id, patch);
      const updated = providerStore.providers.find((p) => p.id === props.provider?.id);
      if (updated) emit('saved', updated);
    } else {
      const payload: CreateProviderInput = {
        name: form.name.trim(),
        type: form.type,
        url: form.url.trim(),
      };
      if (form.apiToken) payload.apiToken = form.apiToken;
      if (form.username.trim()) payload.username = form.username.trim();
      const created = await providerStore.create(payload);
      emit('saved', created);
    }
    emit('close');
  } catch (err) {
    formError.value = err instanceof Error ? err.message : 'save_failed';
  } finally {
    submitting.value = false;
  }
}

onMounted(() => {
  dialogRef.value?.showModal();
});
onBeforeUnmount(() => {
  if (dialogRef.value?.open) dialogRef.value.close();
});

function onBackdropClick(e: MouseEvent) {
  if (e.target === dialogRef.value) emit('close');
}
</script>

<template>
  <Teleport to="body">
    <!-- eslint-disable vuejs-accessibility/click-events-have-key-events, vuejs-accessibility/no-static-element-interactions -->
    <dialog
      ref="dialogRef"
      class="provider-sheet-root provider-sheet-dialog"
      aria-modal="true"
      @close="emit('close')"
      @cancel.prevent="emit('close')"
      @click="onBackdropClick"
    >
      <div class="modal-panel" @click.stop>
        <header class="modal-head">
          <span class="modal-glyph" :class="form.type">{{ glyph(form.type) }}</span>
          <div class="modal-title">
            <div class="title-main">
              {{
                isEdit
                  ? t('monitoring.repos.modal.title_edit', { name: form.name })
                  : t('monitoring.repos.modal.title_add')
              }}
            </div>
            <div v-if="provider" class="title-sub">
              {{ provider.projectsCount }} repos ·
              {{
                provider.lastSyncAt
                  ? t('monitoring.repos.modal.last_sync', { at: provider.lastSyncAt })
                  : t('monitoring.repos.modal.never_synced')
              }}
            </div>
          </div>
          <button
            type="button"
            class="modal-close"
            :aria-label="t('common.actions.close')"
            @click="emit('close')"
          >
            ×
          </button>
        </header>

        <div class="modal-body">
          <div v-if="!isEdit" class="field full">
            <span class="field-label">{{ t('monitoring.repos.modal.field_type') }}</span>
            <div class="type-group" role="radiogroup">
              <button
                v-for="k in ['github', 'gitlab', 'bitbucket'] as ProviderType[]"
                :key="k"
                type="button"
                class="type-btn"
                :class="{ active: form.type === k }"
                :aria-pressed="form.type === k"
                @click="onTypeChange(k)"
              >
                <span class="type-glyph">{{ glyph(k) }}</span>
                <span>{{ k }}</span>
              </button>
            </div>
          </div>

          <label class="field">
            <span class="field-label">{{ t('monitoring.repos.modal.field_name') }}</span>
            <input v-model="form.name" type="text" placeholder="perso, team-x…" />
          </label>

          <label class="field">
            <span class="field-label">{{ t('monitoring.repos.modal.field_username') }}</span>
            <input v-model="form.username" type="text" placeholder="ldm" />
          </label>

          <label class="field full">
            <span class="field-label">{{ t('monitoring.repos.modal.field_endpoint') }}</span>
            <input v-model="form.url" type="url" spellcheck="false" />
          </label>

          <label class="field full">
            <span class="field-label">
              {{ t('monitoring.repos.modal.field_token') }}
              <span v-if="isEdit" class="field-hint">{{
                t('monitoring.repos.modal.token_edit_hint')
              }}</span>
            </span>
            <input
              v-model="form.apiToken"
              type="password"
              autocomplete="new-password"
              placeholder="ghp_••••••••••••••••••••"
            />
          </label>
        </div>

        <div v-if="isEdit" class="test-row">
          <button type="button" class="btn-ghost" :disabled="testing" @click="runTest">
            {{
              testing ? t('monitoring.repos.modal.testing') : t('monitoring.repos.modal.test_btn')
            }}
          </button>
          <span v-if="testResult" class="test-result" :class="{ ok: testResult.ok }">
            {{ testResult.message }}
          </span>
          <span v-else class="test-hint">{{ t('monitoring.repos.modal.test_hint') }}</span>
        </div>

        <footer class="modal-foot">
          <button
            v-if="isEdit"
            type="button"
            class="btn-danger"
            :disabled="submitting"
            @click="disconnect"
          >
            {{ t('monitoring.repos.modal.disconnect') }}
          </button>
          <span v-else />
          <div class="foot-right">
            <span v-if="formError" class="form-error">{{ formError }}</span>
            <button type="button" class="btn-ghost" :disabled="submitting" @click="emit('close')">
              {{ t('common.actions.cancel') }}
            </button>
            <button type="button" class="btn-primary" :disabled="submitting" @click="save">
              {{
                isEdit ? t('monitoring.repos.modal.save') : t('monitoring.repos.modal.save_create')
              }}
            </button>
          </div>
        </footer>
      </div>
    </dialog>
  </Teleport>
</template>

<style scoped>
.provider-sheet-root {
  --hub-bg-0: #07070a;
  --hub-bg-1: #101014;
  --hub-bg-2: #16171c;
  --hub-bg-3: #1d1e24;
  --hub-fg-0: #e8e8ea;
  --hub-fg-1: #b4b4b8;
  --hub-fg-2: #72747a;
  --hub-fg-3: #42444a;
  --hub-line: rgba(255, 255, 255, 0.07);
  --hub-line-strong: rgba(255, 255, 255, 0.16);
  --hub-accent: oklch(0.78 0.16 322);
  --hub-bad: oklch(0.72 0.18 25);
  --hub-good: oklch(0.78 0.14 150);
  --hub-ease: cubic-bezier(0.2, 0.8, 0.2, 1);
  --hub-font-sans: 'Darker Grotesque', ui-sans-serif, system-ui, -apple-system, sans-serif;
  --hub-font-mono: 'JetBrains Mono', ui-monospace, Menlo, monospace;
  --hub-font-serif: 'Fraunces', 'Instrument Serif', ui-serif, Georgia, serif;
  color: var(--hub-fg-0);
  font-family: var(--hub-font-sans);
}

.provider-sheet-dialog {
  padding: 0;
  margin: auto;
  width: min(720px, 92vw);
  max-height: 90vh;
  border: 0;
  background: transparent;
  color: inherit;
  overflow: visible;
}
.provider-sheet-dialog::backdrop {
  background: color-mix(in oklab, #07070a 78%, transparent);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  animation: overlay-in 220ms cubic-bezier(0.2, 0.8, 0.2, 1);
}
.modal-panel {
  width: 100%;
  max-height: 90vh;
  overflow: auto;
  background: var(--hub-bg-1);
  border: 1px solid var(--hub-line-strong);
  border-radius: 18px;
  box-shadow: 0 30px 80px rgba(0, 0, 0, 0.45);
  animation: panel-in 260ms var(--hub-ease);
  display: flex;
  flex-direction: column;
}
@keyframes overlay-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
@keyframes panel-in {
  from {
    opacity: 0;
    transform: translateY(8px) scale(0.985);
  }
  to {
    opacity: 1;
    transform: none;
  }
}

.modal-head {
  padding: 20px 24px;
  border-bottom: 1px solid var(--hub-line);
  display: flex;
  align-items: center;
  gap: 14px;
}
.modal-glyph {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--hub-bg-3);
  color: var(--hub-fg-0);
  display: grid;
  place-items: center;
  font-family: var(--hub-font-mono);
  font-size: 12px;
  letter-spacing: 0.06em;
}
.modal-glyph.github {
  background: color-mix(in oklab, hsl(0 60% 60%) 22%, var(--hub-bg-3));
}
.modal-glyph.gitlab {
  background: color-mix(in oklab, hsl(28 70% 55%) 22%, var(--hub-bg-3));
}
.modal-glyph.bitbucket {
  background: color-mix(in oklab, hsl(220 60% 55%) 22%, var(--hub-bg-3));
}
.modal-title {
  flex: 1;
}
.title-main {
  font-family: var(--hub-font-sans);
  font-size: 18px;
  color: var(--hub-fg-0);
}
.title-sub {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-3);
  letter-spacing: 0.08em;
  margin-top: 2px;
}
.modal-close {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 1px solid var(--hub-line-strong);
  background: transparent;
  color: var(--hub-fg-2);
  font-family: var(--hub-font-mono);
  font-size: 16px;
  cursor: pointer;
  transition: all 160ms var(--hub-ease);
}
.modal-close:hover {
  border-color: var(--hub-accent);
  color: var(--hub-fg-0);
}

.modal-body {
  padding: 24px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}
.field.full {
  grid-column: 1 / -1;
}
.field-label {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--hub-fg-3);
  display: flex;
  align-items: center;
  gap: 8px;
}
.field-hint {
  text-transform: none;
  letter-spacing: 0.04em;
  color: var(--hub-fg-3);
  font-size: 9px;
}
.field input {
  padding: 9px 12px;
  border-radius: 8px;
  border: 1px solid var(--hub-line);
  background: var(--hub-bg-2);
  color: var(--hub-fg-0);
  font-family: var(--hub-font-mono);
  font-size: 12px;
  outline: none;
  transition: border-color 160ms var(--hub-ease);
}
.field input:focus {
  border-color: var(--hub-accent);
}

.type-group {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.type-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px 8px 8px;
  border-radius: 999px;
  border: 1px solid var(--hub-line);
  background: transparent;
  color: var(--hub-fg-2);
  font-family: var(--hub-font-mono);
  font-size: 11px;
  letter-spacing: 0.06em;
  cursor: pointer;
  transition: all 180ms var(--hub-ease);
}
.type-btn .type-glyph {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: var(--hub-bg-3);
  display: grid;
  place-items: center;
  font-size: 9px;
}
.type-btn:hover {
  color: var(--hub-fg-0);
  border-color: var(--hub-line-strong);
}
.type-btn.active {
  background: var(--hub-bg-2);
  border-color: var(--hub-accent);
  color: var(--hub-fg-0);
}

.test-row {
  margin: 0 24px 20px;
  padding: 14px;
  border-radius: 10px;
  border: 1px dashed var(--hub-line-strong);
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
.test-hint,
.test-result {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-3);
}
.test-result.ok {
  color: var(--hub-good);
}

.modal-foot {
  padding: 16px 24px;
  border-top: 1px solid var(--hub-line);
  display: flex;
  gap: 10px;
  justify-content: space-between;
  align-items: center;
  background: color-mix(in oklab, var(--hub-bg-2) 50%, transparent);
}
.foot-right {
  display: flex;
  gap: 10px;
  align-items: center;
}
.form-error {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-bad);
}

.btn-ghost,
.btn-primary,
.btn-danger {
  padding: 8px 16px;
  border-radius: 999px;
  font-family: var(--hub-font-mono);
  font-size: 10px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  cursor: pointer;
  transition: all 180ms var(--hub-ease);
}
.btn-ghost {
  border: 1px solid var(--hub-line-strong);
  background: transparent;
  color: var(--hub-fg-1);
}
.btn-ghost:hover {
  border-color: var(--hub-accent);
  color: var(--hub-fg-0);
}
.btn-primary {
  border: 1px solid var(--hub-fg-0);
  background: var(--hub-fg-0);
  color: var(--hub-bg-0);
}
.btn-primary:hover {
  background: var(--hub-accent);
  border-color: var(--hub-accent);
  color: var(--hub-bg-0);
}
.btn-danger {
  border: 1px solid color-mix(in oklab, var(--hub-bad) 60%, var(--hub-line));
  background: transparent;
  color: var(--hub-bad);
}
.btn-danger:hover {
  background: color-mix(in oklab, var(--hub-bad) 15%, transparent);
}
.btn-ghost:disabled,
.btn-primary:disabled,
.btn-danger:disabled {
  opacity: 0.5;
  cursor: wait;
}
</style>
