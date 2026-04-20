<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRouter } from 'vue-router';

import AmbientBackground from '@/hub/home/AmbientBackground.vue';
import AppTile from '@/hub/home/AppTile.vue';
import Counter from '@/hub/home/Counter.vue';
import LaunchOverlay from '@/hub/home/LaunchOverlay.vue';
import MonarkMark from '@/hub/home/MonarkMark.vue';
import { useAuthStore } from '@/hub/identity/stores/auth';
import { hubService, type HubSummary } from '@/hub/shared/services/hub.service';

interface AppEntry {
  accentHue: number;
  code: string;
  glyph: string;
  id: string;
  name: string;
  path: string;
  primaryStat: null | PrimaryStat;
  stats: { label: string; value: string }[];
  status: 'live' | 'soon';
  subtitle: string;
}

interface AppEntryStatic {
  accentHue: number;
  code: string;
  glyph: string;
  id: string;
  name: string;
  path: string;
  status: 'live' | 'soon';
  subtitle: string;
}

interface PrimaryStat {
  label: string;
  value: string;
}

const APPS_STATIC: AppEntryStatic[] = [
  {
    accentHue: 55,
    code: 'MON',
    glyph: '◆',
    id: 'monitoring',
    name: 'Monitoring',
    path: '/monitoring/dashboard',
    status: 'live',
    subtitle: 'Projects · deps · coverage',
  },
];

const TICKER_ITEMS = [
  'last commit · monark-core · 12m',
  'new PR in api-gateway #342',
  'coverage ↑ 2.1% cette semaine',
  'next deploy · portfolio-v3',
];

const { t } = useI18n();
const router = useRouter();
const authStore = useAuthStore();

const now = ref(new Date());
const query = ref('');
const tickerIdx = ref(0);
const mounted = ref(false);
const summary = ref<HubSummary | null>(null);

let clockTimer: number | undefined;
let tickerTimer: number | undefined;

onMounted(() => {
  clockTimer = window.setInterval(() => (now.value = new Date()), 1000);
  tickerTimer = window.setInterval(
    () => (tickerIdx.value = (tickerIdx.value + 1) % TICKER_ITEMS.length),
    4000,
  );
  requestAnimationFrame(() => (mounted.value = true));
  hubService
    .getSummary()
    .then((s) => (summary.value = s))
    .catch(() => (summary.value = null));
});

onBeforeUnmount(() => {
  if (clockTimer) clearInterval(clockTimer);
  if (tickerTimer) clearInterval(tickerTimer);
});

const greeting = computed(() => {
  const h = now.value.getHours();
  if (h < 6) return t('hub.home.greeting.night');
  if (h < 12) return t('hub.home.greeting.morning');
  if (h < 18) return t('hub.home.greeting.afternoon');
  return t('hub.home.greeting.evening');
});

const userFirstName = computed(() => authStore.currentUser?.firstName ?? '');

const clockIso = computed(() => now.value.toISOString().replace('T', ' ').slice(0, 19));

const dayProgress = computed(() => {
  const d = now.value;
  const start = new Date(d);
  start.setHours(0, 0, 0, 0);
  return (d.getTime() - start.getTime()) / (24 * 3600 * 1000);
});

const liveCount = computed(() => APPS_STATIC.filter((a) => a.status === 'live').length);
const reposCount = computed(() => summary.value?.reposTracked ?? 0);
const commitsCount = computed(() => summary.value?.commits30d ?? 0);
const focusTime = computed(() => {
  const total = summary.value?.focusSecondsToday;
  if (total === undefined) return '—';
  const hours = Math.floor(total / 3600);
  const minutes = Math.floor((total % 3600) / 60);
  return `${hours}h ${String(minutes).padStart(2, '0')}`;
});

const monitoringStats = computed(() => {
  const s = summary.value;
  return [
    {
      label: 'cov',
      value: s?.coveragePercent != null ? `${Math.round(s.coveragePercent)}%` : '—',
    },
    { label: 'cve', value: s ? String(s.cveCount) : '—' },
    { label: 'commits', value: s ? String(s.commits30d) : '—' },
  ];
});

const monitoringPrimary = computed<PrimaryStat>(() => {
  const s = summary.value;
  return { label: 'projects', value: s ? String(s.reposTracked) : '—' };
});

const APPS = computed<AppEntry[]>(() =>
  APPS_STATIC.map((a) => ({
    ...a,
    primaryStat: a.id === 'monitoring' ? monitoringPrimary.value : null,
    stats: a.id === 'monitoring' ? monitoringStats.value : [],
  })),
);

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase();
  if (!q) return APPS.value;
  return APPS.value.filter(
    (a) =>
      a.name.toLowerCase().includes(q) ||
      a.code.toLowerCase().includes(q) ||
      a.subtitle.toLowerCase().includes(q),
  );
});

const LAUNCH_DURATION_MS = 1900;
const launching = ref<AppEntry | null>(null);
let launchTimer: number | undefined;

function launch(app: AppEntry) {
  if (app.status !== 'live' || !app.path) return;
  if (launching.value) return;
  launching.value = app;
  launchTimer = window.setTimeout(() => {
    router.push(app.path);
  }, LAUNCH_DURATION_MS);
}

function onSearchKey(e: KeyboardEvent) {
  if (e.key === 'Enter') {
    const first = filtered.value.find((a) => a.status === 'live');
    if (first) launch(first);
  }
}

onBeforeUnmount(() => {
  if (launchTimer) clearTimeout(launchTimer);
});

onMounted(() => {
  const onKey = (e: KeyboardEvent) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
      e.preventDefault();
      const input = document.querySelector<HTMLInputElement>('[data-testid="hub-search"]');
      input?.focus();
    }
  };
  window.addEventListener('keydown', onKey);
  onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
});
</script>

<template>
  <div class="hub-root" data-testid="hub-home">
    <AmbientBackground />

    <main class="hub-shell">
      <!-- Topbar -->
      <header class="topbar">
        <RouterLink to="/" class="brand" data-testid="hub-brand">
          <MonarkMark />
          <div class="brand-text">
            <span class="brand-tag">MONARK /</span>
            <span class="brand-name">personal dev hub</span>
          </div>
        </RouterLink>
        <div class="status-strip">
          <span class="pulse-dot">
            <span class="dot" />
            <span class="ring" />
          </span>
          <span>{{ t('hub.home.status_nominal') }}</span>
          <span class="sep">│</span>
          <span>{{ clockIso }} UTC</span>
          <span class="sep">│</span>
          <span>v0.1.0</span>
        </div>
      </header>

      <!-- Center -->
      <section class="center">
        <div class="pane left">
          <div class="eyebrow" :class="{ show: mounted }">
            — {{ greeting }}<span v-if="userFirstName">, {{ userFirstName.toLowerCase() }}</span>
          </div>
          <h1 class="display">
            <span class="word" :class="{ show: mounted }" style="--d: 100ms">{{
              t('hub.home.display.word1')
            }}</span
            >&nbsp;<span class="word serif" :class="{ show: mounted }" style="--d: 220ms">{{
              t('hub.home.display.word2')
            }}</span>
            <br />
            <span class="word shimmer" :class="{ show: mounted }" style="--d: 340ms">{{
              t('hub.home.display.word3')
            }}</span>
          </h1>
          <p class="lede" :class="{ show: mounted }">
            {{ t('hub.home.lede') }}
          </p>

          <div class="search" :class="{ show: mounted }">
            <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2" />
              <path d="m20 20-3.5-3.5" fill="none" stroke="currentColor" stroke-width="2" />
            </svg>
            <input
              v-model="query"
              type="search"
              :placeholder="t('hub.home.search_placeholder')"
              :aria-label="t('hub.home.search_placeholder')"
              data-testid="hub-search"
              @keydown="onSearchKey"
            />
            <span class="kbd">⌘ K</span>
          </div>

          <div class="quick-stats" :class="{ show: mounted }">
            <div class="stat">
              <span class="stat-k">{{ t('hub.home.stats.apps') }}</span>
              <span class="stat-v accent"><Counter :to="liveCount" /></span>
            </div>
            <div class="stat">
              <span class="stat-k">{{ t('hub.home.stats.repos') }}</span>
              <span class="stat-v"><Counter :to="reposCount" /></span>
            </div>
            <div class="stat">
              <span class="stat-k">{{ t('hub.home.stats.commits') }}</span>
              <span class="stat-v"><Counter :to="commitsCount" /></span>
            </div>
            <div class="stat">
              <span class="stat-k">{{ t('hub.home.stats.focus') }}</span>
              <span class="stat-v">{{ focusTime }}</span>
            </div>
          </div>
        </div>

        <div class="pane right">
          <div class="apps-meta">
            <div class="meta-left">
              APPS / {{ String(filtered.length).padStart(2, '0') }} /
              {{ String(APPS.length).padStart(2, '0') }}
            </div>
            <div class="meta-right">{{ t('hub.home.meta_keys') }}</div>
          </div>
          <div class="apps-grid" :class="{ single: APPS.length === 1 }">
            <AppTile
              v-for="(app, i) in filtered"
              :key="app.id"
              :accent-hue="app.accentHue"
              :code="app.code"
              :glyph="app.glyph"
              :index="i"
              :mounted="mounted"
              :name="app.name"
              :primary-stat="app.primaryStat"
              :stats="app.stats"
              :status="app.status"
              :subtitle="app.subtitle"
              :data-testid="`hub-app-${app.id}`"
              @launch="launch(app)"
            />
          </div>
        </div>
      </section>

      <!-- Footer -->
      <footer class="footer">
        <div class="foot-block">
          <span>{{ t('hub.home.day_label') }}</span>
          <div class="day-bar">
            <div class="day-fill" :style="{ width: `${dayProgress * 100}%` }" />
          </div>
          <span>{{ Math.round(dayProgress * 100) }}% {{ t('hub.home.day_elapsed') }}</span>
        </div>
        <div class="foot-block center-block">
          <span>◈ monark</span>
          <span>·</span>
          <span>{{ t('hub.home.signature') }}</span>
        </div>
        <div class="foot-block right-block">
          <div class="ticker">
            <div
              v-for="(item, idx) in TICKER_ITEMS"
              :key="idx"
              class="ticker-line"
              :style="{
                transform: `translateY(${(idx - tickerIdx) * 14}px)`,
                opacity: idx === tickerIdx ? 1 : 0,
              }"
            >
              {{ item }}
            </div>
          </div>
        </div>
      </footer>
    </main>

    <LaunchOverlay
      v-if="launching"
      :accent-hue="launching.accentHue"
      :code="launching.code"
      :glyph="launching.glyph"
      :name="launching.name"
      :subtitle="launching.subtitle"
    />
  </div>
</template>

<style scoped>
.hub-root {
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

.hub-root::selection {
  background: var(--hub-accent);
  color: var(--hub-bg-0);
}

.hub-shell {
  position: relative;
  z-index: 5;
  min-height: 100vh;
  display: grid;
  grid-template-rows: auto 1fr auto;
  padding: 32px clamp(40px, 5vw, 120px);
  max-width: 2400px;
  margin: 0 auto;
}

/* ── Topbar ──────────────────────────────────── */
.topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 4px 24px;
  border-bottom: 1px solid var(--hub-line);
  margin-bottom: 40px;
}
.brand {
  display: flex;
  align-items: center;
  gap: 14px;
  color: inherit;
}
.brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
}
.brand-tag {
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-2);
  letter-spacing: 0.08em;
}
.brand-name {
  font-family: var(--hub-font-serif);
  font-style: italic;
  font-size: 20px;
}
.status-strip {
  display: flex;
  align-items: center;
  gap: 28px;
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-2);
}
.status-strip .sep {
  color: var(--hub-fg-3);
}
.pulse-dot {
  position: relative;
  width: 8px;
  height: 8px;
  display: inline-block;
}
.pulse-dot .dot {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: var(--hub-good);
  box-shadow: 0 0 8px var(--hub-good);
}
.pulse-dot .ring {
  position: absolute;
  inset: -2px;
  border-radius: 50%;
  border: 1px solid var(--hub-good);
  animation: ring-expand 2s ease-out infinite;
}
@keyframes ring-expand {
  0% {
    transform: scale(0.4);
    opacity: 0.8;
  }
  100% {
    transform: scale(2.2);
    opacity: 0;
  }
}

/* ── Center ──────────────────────────────────── */
.center {
  display: grid;
  grid-template-columns: minmax(460px, 1fr) minmax(640px, 1.1fr);
  gap: clamp(48px, 4vw, 100px);
  align-items: center;
  padding: clamp(20px, 3vw, 60px) 0;
}
@media (max-width: 1100px) {
  .center {
    grid-template-columns: 1fr;
  }
}

.eyebrow {
  font-family: var(--hub-font-mono);
  font-size: 11px;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: var(--hub-fg-2);
  opacity: 0;
  transform: translateY(12px);
  transition:
    opacity 600ms var(--hub-ease),
    transform 600ms var(--hub-ease);
}
.eyebrow.show {
  opacity: 1;
  transform: translateY(0);
}

.display {
  font-family: var(--hub-font-sans);
  font-weight: 900;
  font-size: clamp(72px, 8.5vw, 180px);
  line-height: 0.88;
  letter-spacing: -0.035em;
  margin: 12px 0 6px;
  color: var(--hub-fg-0);
}
.word {
  display: inline-block;
  opacity: 0;
  transform: translateY(0.25em);
  filter: blur(6px);
  transition:
    opacity 700ms var(--hub-ease-out),
    transform 700ms var(--hub-ease-out),
    filter 700ms var(--hub-ease-out);
  transition-delay: var(--d, 0ms);
}
.word.show {
  opacity: 1;
  transform: translateY(0);
  filter: blur(0);
}
.word.serif {
  font-family: var(--hub-font-serif);
  font-style: italic;
  font-weight: 300;
  letter-spacing: -0.03em;
}
.word.shimmer {
  background-image: linear-gradient(
    90deg,
    var(--hub-accent),
    var(--hub-accent-2),
    var(--hub-accent-3),
    var(--hub-accent)
  );
  background-size: 300% 100%;
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  animation: shimmer 12s linear infinite;
}
@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

.lede {
  font-size: clamp(17px, 1.1vw, 22px);
  line-height: 1.5;
  color: var(--hub-fg-1);
  max-width: min(560px, 38vw);
  margin-top: 22px;
  opacity: 0;
  transform: translateY(12px);
  transition:
    opacity 700ms 400ms var(--hub-ease),
    transform 700ms 400ms var(--hub-ease);
}
.lede.show {
  opacity: 1;
  transform: translateY(0);
}

.search {
  margin-top: 32px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  background: color-mix(in oklab, var(--hub-bg-2) 85%, transparent);
  border: 1px solid var(--hub-line-strong);
  border-radius: 999px;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  max-width: 460px;
  opacity: 0;
  transform: translateY(10px);
  transition:
    opacity 700ms 500ms var(--hub-ease),
    transform 700ms 500ms var(--hub-ease);
  color: var(--hub-fg-2);
}
.search.show {
  opacity: 1;
  transform: translateY(0);
}
.search input {
  flex: 1;
  background: transparent;
  border: 0;
  outline: none;
  color: var(--hub-fg-0);
  font-family: var(--hub-font-sans);
  font-size: 15px;
}
.search input::placeholder {
  color: var(--hub-fg-2);
}
.search .kbd {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-2);
  padding: 3px 7px;
  border: 1px solid var(--hub-line-strong);
  border-radius: 5px;
  letter-spacing: 0.05em;
}

.quick-stats {
  display: grid;
  grid-template-columns: repeat(4, auto);
  gap: clamp(24px, 3vw, 52px);
  margin-top: 48px;
  opacity: 0;
  transform: translateY(10px);
  transition:
    opacity 700ms 700ms var(--hub-ease),
    transform 700ms 700ms var(--hub-ease);
}
.quick-stats.show {
  opacity: 1;
  transform: translateY(0);
}
.stat {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.stat-k {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-2);
  letter-spacing: 0.12em;
  text-transform: uppercase;
}
.stat-v {
  font-family: var(--hub-font-serif);
  font-style: italic;
  font-size: clamp(32px, 2.6vw, 48px);
  color: var(--hub-fg-0);
  line-height: 1;
}
.stat-v.mono-stat {
  font-family: var(--hub-font-mono);
  font-style: normal;
  font-size: clamp(24px, 1.8vw, 32px);
}
.stat-v.accent {
  color: var(--hub-accent);
}

/* ── Apps grid ──────────────────────────────── */
.apps-meta {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 18px;
  padding: 0 4px;
  font-family: var(--hub-font-mono);
  font-size: 11px;
}
.meta-left {
  color: var(--hub-fg-2);
  letter-spacing: 0.12em;
}
.meta-right {
  color: var(--hub-fg-3);
}
.apps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: clamp(14px, 1.2vw, 22px);
}
.apps-grid.single {
  grid-template-columns: minmax(280px, 460px);
}

.app-tile {
  position: relative;
  text-align: left;
  padding: 20px 22px;
  background: color-mix(in oklab, var(--hub-bg-2) 80%, transparent);
  border: 1px solid var(--hub-line);
  border-radius: 14px;
  overflow: hidden;
  color: inherit;
  font: inherit;
  cursor: pointer;
  opacity: 0;
  transform: translateY(20px);
  transition:
    transform 400ms var(--hub-ease),
    border-color 200ms,
    opacity 700ms var(--hub-ease),
    background 250ms ease;
  transition-delay: var(--delay, 0ms);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}
.app-tile.show {
  opacity: 1;
  transform: translateY(0);
}
.app-tile:not(.disabled):hover {
  border-color: var(--hub-line-strong);
  transform: translateY(-4px);
  background: color-mix(in oklab, var(--hub-bg-3) 85%, transparent);
}
.app-tile.disabled {
  cursor: not-allowed;
}
.app-tile.disabled::after {
  content: '';
  position: absolute;
  inset: 0;
  background-image: repeating-linear-gradient(
    -45deg,
    rgba(255, 255, 255, 0.02) 0 8px,
    transparent 8px 18px
  );
  pointer-events: none;
}
.tile-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}
.glyph {
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  background: hsla(var(--hue), 60%, 70%, 0.12);
  border: 1px solid hsla(var(--hue), 60%, 70%, 0.3);
  color: hsl(var(--hue), 80%, 80%);
  font-size: 20px;
  transition: transform 300ms var(--hub-ease);
}
.app-tile:hover .glyph {
  transform: scale(1.06) rotate(-4deg);
}
.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: var(--hub-font-mono);
  font-size: 10px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 4px 8px;
  border-radius: 999px;
  color: var(--hub-fg-2);
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid var(--hub-line);
}
.status-pill.live {
  color: hsl(var(--hue), 80%, 85%);
  background: hsla(var(--hue), 60%, 65%, 0.12);
  border-color: hsla(var(--hue), 60%, 65%, 0.35);
}
.status-pill .pill-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--hub-fg-3);
}
.status-pill.live .pill-dot {
  background: var(--hub-good);
  animation: pill-pulse 2s ease-in-out infinite;
}
@keyframes pill-pulse {
  0%,
  100% {
    opacity: 0.6;
    transform: scale(1);
  }
  50% {
    opacity: 1;
    transform: scale(1.15);
  }
}

.tile-title {
  margin-top: 16px;
  display: flex;
  align-items: baseline;
  gap: 10px;
}
.tile-title .code {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-3);
  letter-spacing: 0.15em;
}
.tile-title h3 {
  margin: 0;
  font-weight: 500;
  font-size: 22px;
  letter-spacing: -0.01em;
}
.subtitle {
  margin: 6px 0 0;
  color: var(--hub-fg-2);
  font-size: 13px;
  line-height: 1.4;
}
.stats-row {
  margin-top: 18px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  font-family: var(--hub-font-mono);
  font-size: 11px;
}
.stat-mini {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.stat-mini .k {
  color: var(--hub-fg-3);
  font-size: 9px;
  letter-spacing: 0.12em;
}
.stat-mini .v {
  color: var(--hub-fg-0);
  font-size: 14px;
}
.arrow {
  position: absolute;
  bottom: 16px;
  right: 18px;
  font-family: var(--hub-font-mono);
  font-size: 14px;
  color: var(--hub-fg-2);
  transition: transform 300ms var(--hub-ease);
}
.app-tile.disabled .arrow {
  opacity: 0.3;
}
.app-tile:not(.disabled):hover .arrow {
  transform: translate(4px, 0);
}

/* ── Footer ──────────────────────────────────── */
.footer {
  margin-top: 36px;
  padding-top: 18px;
  border-top: 1px solid var(--hub-line);
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 24px;
  align-items: center;
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-2);
}
.foot-block {
  display: flex;
  align-items: center;
  gap: 18px;
}
.foot-block.center-block {
  justify-content: center;
  color: var(--hub-fg-3);
}
.foot-block.right-block {
  justify-content: flex-end;
}
.day-bar {
  flex: 1;
  max-width: 220px;
  height: 2px;
  background: var(--hub-line-strong);
  border-radius: 2px;
  position: relative;
}
.day-fill {
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  background: var(--hub-accent);
  transition: width 1s linear;
}
.ticker {
  position: relative;
  min-width: 280px;
  height: 14px;
  overflow: hidden;
}
.ticker-line {
  position: absolute;
  right: 0;
  top: 0;
  white-space: nowrap;
  transition:
    transform 500ms var(--hub-ease),
    opacity 500ms var(--hub-ease);
}

/* ── Scrollbar & focus ──────────────────────── */
.hub-root ::-webkit-scrollbar {
  width: 10px;
  height: 10px;
}
.hub-root ::-webkit-scrollbar-track {
  background: transparent;
}
.hub-root ::-webkit-scrollbar-thumb {
  background: var(--hub-line-strong);
  border-radius: 10px;
}
.hub-root :focus-visible {
  outline: 2px solid var(--hub-accent);
  outline-offset: 2px;
  border-radius: 4px;
}
</style>
