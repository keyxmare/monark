<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import type { Provider } from '@/apps/monitoring/catalog/types/provider';

import ProviderSheetModal from '@/apps/monitoring/catalog/components/ProviderSheetModal.vue';
import { useProviderStore } from '@/apps/monitoring/catalog/stores/provider';
import { useGlobalSync } from '@/hub/shared/composables/useGlobalSync';
import { type SyncStepName } from '@/hub/shared/types/globalSync';

interface StepVisual {
  detailKey: string;
  id: string;
  labelKey: string;
  name: SyncStepName;
}

const STEPS: StepVisual[] = [
  {
    detailKey: 'monitoring.repos.sync.step_projects_detail',
    id: 'projects',
    labelKey: 'monitoring.repos.sync.step_projects',
    name: 'sync_projects',
  },
  {
    detailKey: 'monitoring.repos.sync.step_coverage_detail',
    id: 'coverage',
    labelKey: 'monitoring.repos.sync.step_coverage',
    name: 'sync_coverage',
  },
  {
    detailKey: 'monitoring.repos.sync.step_deps_detail',
    id: 'deps',
    labelKey: 'monitoring.repos.sync.step_deps',
    name: 'sync_dependencies',
  },
  {
    detailKey: 'monitoring.repos.sync.step_frameworks_detail',
    id: 'frameworks',
    labelKey: 'monitoring.repos.sync.step_frameworks',
    name: 'sync_frameworks',
  },
  {
    detailKey: 'monitoring.repos.sync.step_cve_detail',
    id: 'cve',
    labelKey: 'monitoring.repos.sync.step_cve',
    name: 'scan_cve',
  },
];

const { t } = useI18n();
const providerStore = useProviderStore();
const { cancelSync, currentSync, isRunning, loadCurrent, startSync } = useGlobalSync();

onMounted(() => {
  void loadCurrent();
  if (providerStore.providers.length === 0) {
    void providerStore.fetchAll(1, 50);
  }
});

const providers = computed(() => providerStore.providers);

function isConnected(p: Provider): boolean {
  return p.status === 'connected';
}

function providerGlyph(type: string): string {
  if (type === 'github') return 'gh';
  if (type === 'gitlab') return 'gl';
  if (type === 'bitbucket') return 'bb';
  return 'git';
}

const editingProvider = ref<null | Provider>(null);
const addingProvider = ref(false);

function closeSheet() {
  editingProvider.value = null;
  addingProvider.value = false;
}

function openAdd() {
  addingProvider.value = true;
}

function openEdit(p: Provider) {
  editingProvider.value = p;
}

async function toggleProvider(p: Provider) {
  if (!isConnected(p)) {
    openEdit(p);
    return;
  }
  const ok = window.confirm(
    t('monitoring.repos.modal.confirm_disconnect_inline', { name: p.name }),
  );
  if (!ok) return;
  await providerStore.remove(p.id);
}

const isDone = computed(() => currentSync.value?.status === 'completed');
const showPipeline = computed(() => currentSync.value !== null);
const currentStepIdx = computed(() => {
  const s = currentSync.value;
  if (!s) return 0;
  return s.currentStep ?? 0;
});
interface StepStat {
  current: number;
  total: null | number;
}

function emptyStats(): Record<SyncStepName, StepStat> {
  return {
    scan_cve: { current: 0, total: null },
    sync_coverage: { current: 0, total: null },
    sync_dependencies: { current: 0, total: null },
    sync_frameworks: { current: 0, total: null },
    sync_projects: { current: 0, total: null },
  };
}

const stepStats = ref<Record<SyncStepName, StepStat>>(emptyStats());

watch(
  currentSync,
  (state) => {
    if (!state) {
      stepStats.value = emptyStats();
      return;
    }
    const next = { ...stepStats.value };
    if (state.currentStepName) {
      const prev = next[state.currentStepName];
      next[state.currentStepName] = {
        current: state.stepProgress ?? prev.current,
        total: state.stepTotal > 0 ? state.stepTotal : prev.total,
      };
    }
    for (const name of state.completedSteps) {
      const prev = next[name];
      const total = prev.total ?? state.stepTotal ?? prev.current;
      next[name] = { current: total, total };
    }
    stepStats.value = next;
  },
  { immediate: true },
);

const displayCounters = ref<Record<SyncStepName, number>>({
  scan_cve: 0,
  sync_coverage: 0,
  sync_dependencies: 0,
  sync_frameworks: 0,
  sync_projects: 0,
});
const rafIds = new Map<SyncStepName, number>();

function animateTo(name: SyncStepName, target: number) {
  const existing = rafIds.get(name);
  if (existing !== undefined) cancelAnimationFrame(existing);
  const start = displayCounters.value[name];
  if (start === target) return;
  const startTime = performance.now();
  const dur = 600;
  const tick = (now: number) => {
    const p = Math.min(1, (now - startTime) / dur);
    const eased = 1 - Math.pow(1 - p, 3);
    displayCounters.value[name] = Math.round(start + (target - start) * eased);
    if (p < 1) rafIds.set(name, requestAnimationFrame(tick));
    else rafIds.delete(name);
  };
  rafIds.set(name, requestAnimationFrame(tick));
}

watch(
  stepStats,
  (stats) => {
    for (const step of STEPS) {
      animateTo(step.name, stats[step.name].current);
    }
  },
  { deep: true },
);

onBeforeUnmount(() => {
  for (const id of rafIds.values()) cancelAnimationFrame(id);
  rafIds.clear();
});

function counterDisplay(i: number) {
  const name = STEPS[i].name;
  const stat = stepStats.value[name];
  const { active, complete, pending } = stateOf(i);
  if (pending) return stat.total !== null ? `— / ${stat.total}` : '— / —';
  if (complete && stat.total !== null) return `${stat.total} / ${stat.total}`;
  const shown = displayCounters.value[name];
  if (active) return stat.total !== null ? `${shown} / ${stat.total}` : `${shown} / —`;
  return stat.total !== null ? `${shown} / ${stat.total}` : `${shown} / —`;
}

function stateOf(idx: number) {
  const sync = currentSync.value;
  if (!sync) return { active: false, complete: false, pending: true };
  const name = STEPS[idx].name;
  const complete = sync.completedSteps.includes(name) || sync.status === 'completed';
  const active = !complete && sync.status === 'running' && sync.currentStepName === name;
  return { active, complete, pending: !active && !complete };
}

const syncError = ref<null | string>(null);

async function abortSync() {
  if (!isRunning.value) return;
  const ok = window.confirm(t('monitoring.repos.sync.confirm_cancel'));
  if (!ok) return;
  syncError.value = null;
  try {
    await cancelSync();
  } catch (err) {
    syncError.value = err instanceof Error ? err.message : 'sync_cancel_failed';
  }
}

async function runSync() {
  if (isRunning.value) return;
  syncError.value = null;
  try {
    await startSync();
  } catch (err) {
    syncError.value = err instanceof Error ? err.message : 'sync_start_failed';
  }
}
</script>

<template>
  <div class="provider-bar" data-testid="provider-bar">
    <div class="row">
      <div class="providers">
        <span class="label">{{ t('monitoring.repos.providers') }}</span>
        <div v-for="p in providers" :key="p.id" class="chip" :class="{ connected: isConnected(p) }">
          <span class="chip-glyph" :class="p.type">{{ providerGlyph(p.type) }}</span>
          <div class="chip-text">
            <span class="chip-name">{{ p.name }}</span>
            <span class="chip-sub">
              {{
                isConnected(p)
                  ? t('monitoring.repos.chip_connected', { count: p.projectsCount })
                  : t('monitoring.repos.chip_disconnected')
              }}
            </span>
          </div>
          <button
            type="button"
            class="chip-toggle"
            :class="{ on: isConnected(p) }"
            :aria-label="
              isConnected(p)
                ? t('monitoring.repos.modal.disconnect')
                : t('monitoring.repos.modal.connect')
            "
            @click.stop="toggleProvider(p)"
          >
            {{
              isConnected(p)
                ? t('monitoring.repos.chip_toggle_disconnect')
                : t('monitoring.repos.chip_toggle_connect')
            }}
          </button>
          <button
            v-if="isConnected(p)"
            type="button"
            class="chip-edit"
            :aria-label="t('monitoring.repos.modal.edit')"
            :title="t('monitoring.repos.modal.edit')"
            @click.stop="openEdit(p)"
          >
            <svg
              width="12"
              height="12"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <circle cx="12" cy="12" r="3" />
              <path
                d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"
              />
            </svg>
          </button>
        </div>
        <button type="button" class="add-source" @click="openAdd">
          + {{ t('monitoring.repos.add_source') }}
        </button>
      </div>

      <div class="sync-cluster">
        <button
          type="button"
          class="sync-btn"
          :class="{ running: isRunning, done: isDone }"
          :disabled="isRunning"
          @click="runSync"
        >
          <span v-if="isDone" class="sync-check">✓</span>
          <svg
            v-else
            class="sync-icon"
            :class="{ spin: isRunning }"
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.2"
            aria-hidden="true"
          >
            <path d="M21 12a9 9 0 0 1-15.4 6.4L3 16" />
            <path d="M3 12a9 9 0 0 1 15.4-6.4L21 8" />
            <path d="M21 3v5h-5" />
            <path d="M3 21v-5h5" />
          </svg>
          <span>
            {{
              isRunning
                ? `${t('monitoring.repos.sync.running')} · ${currentStepIdx}/${STEPS.length}`
                : isDone
                  ? t('monitoring.repos.sync.done')
                  : t('monitoring.repos.sync_now')
            }}
          </span>
        </button>
        <button
          v-if="isRunning"
          type="button"
          class="cancel-btn"
          :aria-label="t('monitoring.repos.sync.cancel')"
          :title="t('monitoring.repos.sync.cancel')"
          data-testid="sync-cancel"
          @click="abortSync"
        >
          ×
        </button>
      </div>
    </div>

    <div v-if="syncError" class="sync-error" role="alert">
      {{ t('monitoring.repos.sync.failed') }} · {{ syncError }}
    </div>

    <div v-if="showPipeline" class="pipeline">
      <div
        v-for="(step, i) in STEPS"
        :key="step.id"
        class="step"
        :class="{
          active: stateOf(i).active,
          complete: stateOf(i).complete,
          pending: stateOf(i).pending,
        }"
      >
        <span v-if="stateOf(i).active" class="step-shimmer" aria-hidden="true" />
        <div class="step-body">
          <span
            class="step-dot"
            :class="{
              active: stateOf(i).active,
              complete: stateOf(i).complete,
              pending: stateOf(i).pending,
            }"
          >
            <span v-if="stateOf(i).complete">✓</span>
            <svg
              v-else-if="stateOf(i).active"
              width="18"
              height="18"
              viewBox="0 0 18 18"
              class="dot-spin"
            >
              <circle
                cx="9"
                cy="9"
                r="7"
                stroke="var(--hub-line-strong)"
                stroke-width="2"
                fill="none"
              />
              <circle
                cx="9"
                cy="9"
                r="7"
                stroke="var(--hub-accent)"
                stroke-width="2"
                fill="none"
                stroke-linecap="round"
                stroke-dasharray="44"
                stroke-dashoffset="28"
              />
            </svg>
            <span v-else class="dot-idx">{{ i + 1 }}</span>
          </span>
          <div class="step-meta">
            <div class="step-top">
              <span class="step-label">{{ t(step.labelKey) }}</span>
              <span
                class="step-counter"
                :class="{
                  active: stateOf(i).active,
                  complete: stateOf(i).complete,
                  pending: stateOf(i).pending,
                }"
              >
                {{ counterDisplay(i) }}
              </span>
            </div>
            <span class="step-detail">{{ t(step.detailKey) }}</span>
          </div>
        </div>
        <span
          class="step-bar"
          :class="{ active: stateOf(i).active, complete: stateOf(i).complete }"
        />
      </div>
    </div>

    <ProviderSheetModal
      v-if="editingProvider"
      :provider="editingProvider"
      @close="closeSheet"
      @deleted="closeSheet"
      @saved="closeSheet"
    />
    <ProviderSheetModal
      v-if="addingProvider"
      :provider="null"
      @close="closeSheet"
      @saved="closeSheet"
    />
  </div>
</template>

<style scoped>
.provider-bar {
  padding: 16px;
  border-radius: 14px;
  border: 1px solid var(--hub-line);
  background: color-mix(in oklab, var(--hub-bg-2) 75%, transparent);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 18px;
  flex-wrap: wrap;
}

.providers {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}
.label {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-3);
  letter-spacing: 0.14em;
  text-transform: uppercase;
  margin-right: 4px;
}
.chip {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 6px 6px 6px 6px;
  border-radius: 999px;
  border: 1px solid var(--hub-line);
  background: transparent;
  font-family: var(--hub-font-mono);
  font-size: 11px;
  transition:
    border-color 180ms var(--hub-ease),
    background 180ms var(--hub-ease);
}
.chip.connected {
  border-color: var(--hub-line-strong);
  background: var(--hub-bg-1);
}
.chip-glyph {
  display: grid;
  place-items: center;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: var(--hub-bg-3);
  color: var(--hub-fg-3);
  font-family: var(--hub-font-mono);
  font-size: 9px;
  letter-spacing: 0.06em;
}
.chip.connected .chip-glyph {
  color: var(--hub-fg-0);
}
.chip.connected .chip-glyph.github {
  background: color-mix(in oklab, hsl(0 60% 60%) 20%, var(--hub-bg-3));
}
.chip.connected .chip-glyph.gitlab {
  background: color-mix(in oklab, hsl(28 70% 55%) 20%, var(--hub-bg-3));
}
.chip.connected .chip-glyph.bitbucket {
  background: color-mix(in oklab, hsl(220 60% 55%) 20%, var(--hub-bg-3));
}
.chip-text {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
  padding-left: 2px;
}
.chip-name {
  font-family: var(--hub-font-sans);
  font-size: 12px;
  color: var(--hub-fg-2);
}
.chip.connected .chip-name {
  color: var(--hub-fg-0);
}
.chip-sub {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  color: var(--hub-fg-3);
  letter-spacing: 0.04em;
  margin-top: 2px;
}
.chip-toggle {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 5px 10px;
  border-radius: 999px;
  border: 1px solid var(--hub-line-strong);
  background: var(--hub-fg-0);
  color: var(--hub-bg-0);
  cursor: pointer;
  margin-left: 4px;
  transition: all 180ms var(--hub-ease);
}
.chip-toggle.on {
  background: transparent;
  color: var(--hub-fg-2);
}
.chip-toggle.on:hover {
  border-color: var(--hub-bad);
  color: var(--hub-bad);
}
.chip-toggle:not(.on):hover {
  background: var(--hub-accent);
  color: var(--hub-bg-0);
  border-color: var(--hub-accent);
}
.chip-edit {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  border: 1px solid var(--hub-line-strong);
  background: transparent;
  color: var(--hub-fg-2);
  display: grid;
  place-items: center;
  cursor: pointer;
  margin-left: -4px;
  padding: 0;
  transition:
    color 180ms var(--hub-ease),
    border-color 180ms var(--hub-ease);
}
.chip-edit:hover {
  color: var(--hub-fg-0);
  border-color: var(--hub-accent);
}

.add-source {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 6px 12px;
  border: 1px dashed var(--hub-line-strong);
  border-radius: 999px;
  color: var(--hub-fg-2);
  background: transparent;
  cursor: pointer;
  transition: all 180ms var(--hub-ease);
}
.add-source:hover {
  border-color: var(--hub-accent);
  color: var(--hub-fg-0);
}

.sync-cluster {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.cancel-btn {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 1px solid var(--hub-line-strong);
  background: transparent;
  color: var(--hub-fg-2);
  font-family: var(--hub-font-mono);
  font-size: 16px;
  line-height: 1;
  cursor: pointer;
  display: grid;
  place-items: center;
  transition:
    color 180ms var(--hub-ease),
    border-color 180ms var(--hub-ease),
    background 180ms var(--hub-ease);
}
.cancel-btn:hover {
  color: var(--hub-bad);
  border-color: var(--hub-bad);
  background: color-mix(in oklab, var(--hub-bad) 12%, transparent);
}

/* Sync button — default light, running accent, done good */
.sync-btn {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 10px 16px;
  border-radius: 999px;
  border: 1px solid var(--hub-line-strong);
  background: var(--hub-fg-0);
  color: var(--hub-bg-0);
  font-family: var(--hub-font-mono);
  font-size: 11px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  transition: all 250ms var(--hub-ease);
}
.sync-btn.running {
  border-color: var(--hub-accent);
  background: color-mix(in oklab, var(--hub-accent) 18%, var(--hub-bg-2));
  color: var(--hub-fg-0);
}
.sync-btn.done {
  border-color: var(--hub-good);
  background: color-mix(in oklab, var(--hub-good) 18%, var(--hub-bg-2));
  color: var(--hub-fg-0);
}
.sync-btn:disabled {
  cursor: not-allowed;
}
.sync-icon {
  display: inline-block;
}
.sync-icon.spin {
  animation: sync-spin 1.1s linear infinite;
}
.sync-check {
  color: var(--hub-good);
  font-size: 13px;
}
@keyframes sync-spin {
  to {
    transform: rotate(360deg);
  }
}

.sync-error {
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid color-mix(in oklab, var(--hub-bad) 40%, var(--hub-line));
  background: color-mix(in oklab, var(--hub-bad) 12%, var(--hub-bg-1));
  color: var(--hub-fg-0);
  font-family: var(--hub-font-mono);
  font-size: 10px;
  letter-spacing: 0.06em;
}

/* Pipeline */
.pipeline {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
  padding-top: 2px;
}
@media (max-width: 900px) {
  .pipeline {
    grid-template-columns: repeat(2, 1fr);
  }
}

.step {
  position: relative;
  padding: 10px 12px;
  border-radius: 8px;
  background: var(--hub-bg-1);
  border: 1px solid var(--hub-line);
  overflow: hidden;
  transition: all 300ms var(--hub-ease);
}
.step.active {
  background: color-mix(in oklab, var(--hub-accent) 14%, var(--hub-bg-1));
  border-color: var(--hub-accent);
}
.step.complete {
  background: color-mix(in oklab, var(--hub-good) 10%, var(--hub-bg-1));
  border-color: color-mix(in oklab, var(--hub-good) 40%, var(--hub-line));
}

.step-shimmer {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    90deg,
    transparent,
    color-mix(in oklab, var(--hub-accent) 30%, transparent),
    transparent
  );
  background-size: 200% 100%;
  animation: step-shimmer 1.4s linear infinite;
}
@keyframes step-shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

.step-body {
  position: relative;
  display: flex;
  align-items: center;
  gap: 8px;
}

.step-dot {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  font-family: var(--hub-font-mono);
  font-size: 10px;
  font-weight: 700;
}
.step-dot.complete {
  background: var(--hub-good);
  color: var(--hub-bg-0);
  font-size: 11px;
}
.step-dot.pending {
  border: 1px dashed var(--hub-line-strong);
  color: var(--hub-fg-3);
  font-size: 9px;
  font-weight: 400;
}
.step-dot.active {
  background: transparent;
}
.dot-spin {
  animation: sync-spin 0.9s linear infinite;
}
.dot-idx {
  font-family: var(--hub-font-mono);
  font-size: 9px;
}

.step-meta {
  display: flex;
  flex-direction: column;
  line-height: 1.15;
  min-width: 0;
  flex: 1;
}
.step-top {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 8px;
}
.step-label {
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-2);
  letter-spacing: 0.04em;
}
.step.active .step-label,
.step.complete .step-label {
  color: var(--hub-fg-0);
}

.step-counter {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.02em;
  white-space: nowrap;
}
.step-counter.pending {
  color: var(--hub-fg-3);
}
.step-counter.active {
  color: var(--hub-accent);
}
.step-counter.complete {
  color: var(--hub-good);
}

.step-detail {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  color: var(--hub-fg-3);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.step-bar {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 2px;
  background: color-mix(in oklab, var(--hub-line) 50%, transparent);
}
.step-bar::after {
  content: '';
  display: block;
  height: 100%;
  width: 0;
  transition: width 400ms var(--hub-ease);
  background: var(--hub-good);
}
.step-bar.active::after {
  width: 100%;
  background: linear-gradient(90deg, transparent, var(--hub-accent), transparent);
  background-size: 40% 100%;
  animation: slide-bar 1.4s linear infinite;
}
.step-bar.complete::after {
  width: 100%;
  background: var(--hub-good);
}
@keyframes slide-bar {
  0% {
    background-position: -40% 0;
  }
  100% {
    background-position: 140% 0;
  }
}
</style>
