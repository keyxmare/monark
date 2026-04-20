<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, RouterView, useRoute } from 'vue-router';

import ProviderBar from '@/apps/monitoring/catalog/components/ProviderBar.vue';
import { useProjectStore } from '@/apps/monitoring/catalog/stores/project';
import AmbientBackground from '@/hub/home/AmbientBackground.vue';

interface ShellTab {
  badge?: number;
  icon: string;
  id: string;
  label: string;
  path: string;
}

const APP = {
  accentHue: 55,
  code: 'MON',
};

const route = useRoute();
const { t } = useI18n();
const projectStore = useProjectStore();

const repoCount = computed(() => (projectStore.total > 0 ? projectStore.total : null));

const tabs = computed<ShellTab[]>(() => [
  {
    icon: '▦',
    id: 'dashboard',
    label: t('monitoring.shell.tab_dashboard'),
    path: '/monitoring/dashboard',
  },
  {
    badge: repoCount.value ?? undefined,
    icon: '❒',
    id: 'repos',
    label: t('monitoring.shell.tab_catalog'),
    path: '/monitoring/catalog/projects',
  },
]);

const activeTab = computed(() => {
  const p = route.path;
  if (
    p.startsWith('/monitoring/catalog') ||
    p.startsWith('/monitoring/dependency') ||
    p.startsWith('/monitoring/coverage') ||
    p.startsWith('/monitoring/activity/events')
  ) {
    return 'repos';
  }
  return 'dashboard';
});

const breadcrumb = computed(() => {
  const current = tabs.value.find((t) => t.id === activeTab.value);
  return current ? current.label.toLowerCase() : '';
});

const mounted = ref(false);
onMounted(() => {
  requestAnimationFrame(() => (mounted.value = true));
  if (projectStore.total === 0 && !projectStore.loading) {
    void projectStore.fetchTotal();
  }
});
onBeforeUnmount(() => {
  mounted.value = false;
});
</script>

<template>
  <div class="mon-root">
    <AmbientBackground />
    <main class="mon-shell" :class="{ show: mounted }">
      <header class="topbar">
        <div class="left">
          <RouterLink to="/" class="back-pill" data-testid="back-to-hub">
            <span class="arrow">←</span>
            <span>hub</span>
          </RouterLink>

          <div class="app-badge">
            <span
              class="badge-code"
              :style="{
                background: `hsla(${APP.accentHue}, 70%, 55%, 0.18)`,
                color: `hsl(${APP.accentHue}, 80%, 75%)`,
              }"
            >
              {{ APP.code }}
            </span>
            <div class="badge-text">
              <span class="badge-name">{{ t('monitoring.shell.app_name') }}</span>
              <span class="badge-sub">{{ t('monitoring.shell.app_subtitle') }}</span>
            </div>
          </div>

          <nav class="tab-group" :aria-label="t('monitoring.shell.nav_aria')">
            <RouterLink
              v-for="tab in tabs"
              :key="tab.id"
              :to="tab.path"
              class="tab"
              :class="{ active: activeTab === tab.id }"
              :data-testid="`monitoring-tab-${tab.id}`"
            >
              <span class="icon">{{ tab.icon }}</span>
              <span>{{ tab.label }}</span>
              <span
                v-if="tab.badge !== undefined"
                class="tab-badge"
                :data-testid="`monitoring-tab-${tab.id}-badge`"
              >
                {{ tab.badge }}
              </span>
            </RouterLink>
          </nav>
        </div>

        <div class="breadcrumb">
          <span class="crumb muted">monark</span>
          <span class="sep">/</span>
          <span class="crumb muted">monitoring</span>
          <span class="sep">/</span>
          <span class="crumb">{{ breadcrumb }}</span>
          <span class="cursor" />
        </div>
      </header>

      <div class="content">
        <ProviderBar />
        <RouterView v-slot="{ Component }">
          <transition name="mon-page" mode="out-in">
            <component :is="Component" />
          </transition>
        </RouterView>
      </div>
    </main>
  </div>
</template>

<style scoped>
.mon-root {
  /* Direction: TERMINAL — near-black surfaces, cool greys, magenta/yellow/blue triad (hue 322) */
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
  --hub-accent: oklch(0.78 0.16 322); /* magenta */
  --hub-accent-2: oklch(0.78 0.16 102); /* yellow-green */
  --hub-accent-3: oklch(0.78 0.16 242); /* blue */
  --hub-good: oklch(0.78 0.14 150);
  --hub-bad: oklch(0.72 0.18 25);
  --hub-ease: cubic-bezier(0.2, 0.8, 0.2, 1);
  --hub-ease-out: cubic-bezier(0.16, 1, 0.3, 1);
  --hub-font-sans: 'Darker Grotesque', ui-sans-serif, system-ui, -apple-system, sans-serif;
  --hub-font-mono: 'JetBrains Mono', ui-monospace, Menlo, monospace;
  --hub-font-serif: 'Fraunces', 'Instrument Serif', ui-serif, Georgia, serif;

  position: fixed;
  inset: 0;
  overflow: auto;
  background: var(--hub-bg-0);
  color: var(--hub-fg-0);
  font-family: var(--hub-font-sans);
  font-feature-settings: 'ss01', 'cv11';
  -webkit-font-smoothing: antialiased;
  text-rendering: optimizeLegibility;
}

.mon-root :deep(::selection) {
  background: var(--hub-accent);
  color: var(--hub-bg-0);
}

.mon-shell {
  position: relative;
  z-index: 5;
  min-height: 100vh;
  padding: 32px clamp(24px, 4vw, 80px);
  max-width: 2400px;
  margin: 0 auto;
  opacity: 0;
  transform: translateY(8px);
  transition:
    opacity 500ms var(--hub-ease),
    transform 500ms var(--hub-ease);
}
.mon-shell.show {
  opacity: 1;
  transform: translateY(0);
}

.topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 18px;
  padding-bottom: 18px;
  border-bottom: 1px solid var(--hub-line);
  flex-wrap: wrap;
}

.left {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.back-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px 8px 10px;
  border-radius: 999px;
  border: 1px solid var(--hub-line-strong);
  color: var(--hub-fg-1);
  font-family: var(--hub-font-mono);
  font-size: 11px;
  letter-spacing: 0.08em;
  background: color-mix(in oklab, var(--hub-bg-2) 70%, transparent);
  transition: all 200ms var(--hub-ease);
}
.back-pill:hover {
  border-color: var(--hub-accent);
  color: var(--hub-fg-0);
}
.back-pill .arrow {
  font-size: 14px;
}

.app-badge {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 6px 14px 6px 6px;
  border-radius: 999px;
  border: 1px solid var(--hub-line);
  background: color-mix(in oklab, var(--hub-bg-1) 60%, transparent);
}
.badge-code {
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  font-family: var(--hub-font-mono);
  font-size: 10px;
  letter-spacing: 0.1em;
}
.badge-text {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
}
.badge-name {
  font-family: var(--hub-font-serif);
  font-style: italic;
  font-size: 18px;
}
.badge-sub {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-2);
}

.tab-group {
  display: flex;
  gap: 2px;
  padding: 3px;
  border-radius: 999px;
  border: 1px solid var(--hub-line);
  background: color-mix(in oklab, var(--hub-bg-2) 60%, transparent);
}
.tab {
  position: relative;
  padding: 7px 14px;
  border-radius: 999px;
  color: var(--hub-fg-2);
  font-family: var(--hub-font-mono);
  font-size: 10px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 180ms var(--hub-ease);
}
.tab:hover {
  color: var(--hub-fg-1);
}
.tab.active {
  background: var(--hub-bg-0);
  color: var(--hub-fg-0);
  box-shadow:
    0 1px 2px rgba(0, 0, 0, 0.25),
    inset 0 0 0 1px var(--hub-line-strong);
}
.tab .icon {
  opacity: 0.85;
}
.tab-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 16px;
  padding: 0 5px;
  margin-left: 2px;
  border-radius: 999px;
  background: hsla(var(--mon-hue, 55), 70%, 55%, 0.18);
  border: 1px solid hsla(var(--mon-hue, 55), 70%, 55%, 0.35);
  color: hsl(var(--mon-hue, 55), 85%, 80%);
  font-family: var(--hub-font-mono);
  font-size: 10px;
  letter-spacing: 0.04em;
  font-variant-numeric: tabular-nums;
}
.tab.active .tab-badge {
  background: hsla(var(--mon-hue, 55), 70%, 55%, 0.28);
  color: var(--hub-fg-0);
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-2);
}
.crumb.muted {
  color: var(--hub-fg-3);
}
.breadcrumb .sep {
  color: var(--hub-fg-3);
}
.cursor {
  width: 6px;
  height: 14px;
  background: var(--hub-accent);
  margin-left: 2px;
  animation: mon-blink 1.1s step-end infinite;
}
@keyframes mon-blink {
  0%,
  49% {
    opacity: 1;
  }
  50%,
  100% {
    opacity: 0;
  }
}

.content {
  margin-top: 28px;
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.mon-page-enter-active,
.mon-page-leave-active {
  transition:
    opacity 300ms var(--hub-ease),
    transform 300ms var(--hub-ease);
}
.mon-page-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.mon-page-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.mon-root :deep(::-webkit-scrollbar) {
  width: 10px;
  height: 10px;
}
.mon-root :deep(::-webkit-scrollbar-track) {
  background: transparent;
}
.mon-root :deep(::-webkit-scrollbar-thumb) {
  background: var(--hub-line-strong);
  border-radius: 10px;
}
.mon-root :deep(:focus-visible) {
  outline: 2px solid var(--hub-accent);
  outline-offset: 2px;
  border-radius: 4px;
}
</style>
