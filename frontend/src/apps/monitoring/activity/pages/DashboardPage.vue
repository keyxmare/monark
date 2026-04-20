<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

import type {
  HostShare,
  LanguageShare,
  MostActiveProjectEntry,
  ProjectDriftEntry,
  TopOutdatedDependencyEntry,
  WeakestCoverageProjectEntry,
} from '@/apps/monitoring/activity/services/dashboard.service';

import { useDashboardStore } from '@/apps/monitoring/activity/stores/dashboard';

const LANG_HUE: Record<string, number> = {
  Astro: 18,
  'C#': 280,
  CSS: 195,
  Go: 195,
  HTML: 12,
  Java: 0,
  JavaScript: 50,
  Kotlin: 25,
  PHP: 270,
  Python: 200,
  Ruby: 0,
  Rust: 16,
  TypeScript: 215,
  Vue: 140,
};

const { t } = useI18n();
const dashboardStore = useDashboardStore();

onMounted(async () => {
  await dashboardStore.load();
});

const loading = computed(() => dashboardStore.loading);

interface Kpi {
  hint: string;
  label: string;
  value: number | string;
  warn?: boolean;
}

const summary = computed(() => dashboardStore.summary);

function fmtNum(n: null | number | undefined): string {
  return n === null || n === undefined ? '—' : String(n);
}

const kpis = computed<Kpi[]>(() => {
  const s = summary.value;
  const cov = s?.coveragePercent;
  const cve = s?.vulnerabilities ?? 0;
  return [
    {
      hint: t('monitoring.dash.kpi.projects_hint'),
      label: t('monitoring.dash.kpi.projects'),
      value: fmtNum(s?.projects),
    },
    {
      hint: t('monitoring.dash.kpi.commits_hint'),
      label: t('monitoring.dash.kpi.commits'),
      value: fmtNum(s?.commits30d),
    },
    {
      hint: t('monitoring.dash.kpi.branches_hint'),
      label: t('monitoring.dash.kpi.branches'),
      value: fmtNum(s?.activeBranches30d),
    },
    {
      hint: t('monitoring.dash.kpi.coverage_hint'),
      label: t('monitoring.dash.kpi.coverage'),
      value: cov === null || cov === undefined ? '—' : `${Math.round(cov)}%`,
    },
    {
      hint: t('monitoring.dash.kpi.deps_hint'),
      label: t('monitoring.dash.kpi.deps'),
      value: fmtNum(s?.dependenciesTracked),
    },
    {
      hint: cve > 0 ? t('monitoring.dash.kpi.cve_warn') : t('monitoring.dash.kpi.cve_ok'),
      label: t('monitoring.dash.kpi.cve'),
      value: fmtNum(s?.vulnerabilities),
      warn: cve > 0,
    },
  ];
});

interface Ring {
  hue: number;
  label: string;
  sub: string;
  value: number;
}

const rings = computed<Ring[]>(() => {
  const s = summary.value;
  const cov = s?.coverageHealthPercent;
  return [
    {
      hue: 140,
      label: t('monitoring.dash.ring.coverage'),
      sub: cov === null || cov === undefined ? '—' : `${Math.round(cov)}%`,
      value: cov === null || cov === undefined ? 0 : Math.min(1, Math.max(0, cov / 100)),
    },
    { hue: 200, label: t('monitoring.dash.ring.runtime'), sub: '—', value: 0 },
    { hue: 322, label: t('monitoring.dash.ring.deps'), sub: '—', value: 0 },
  ];
});

const hasHealthData = computed(() => rings.value.length > 0);

function ringDash(v: number) {
  const r = 34;
  const c = 2 * Math.PI * r;
  return { c, offset: c * (1 - v) };
}

const languages = computed<LanguageShare[]>(() => summary.value?.languages ?? []);
const hasLanguages = computed(() => languages.value.length > 0);
const maxLanguageCount = computed(() =>
  languages.value.reduce((m, l) => Math.max(m, l.projectsCount), 0),
);

function languageHue(name: string): number {
  return LANG_HUE[name] ?? 200;
}
function languagePct(count: number): number {
  const m = maxLanguageCount.value;
  return m > 0 ? count / m : 0;
}

const HOST_COLOR: Record<string, string> = {
  bitbucket: 'hsl(215 60% 60%)',
  github: 'hsl(0 0% 95%)',
  gitlab: 'hsl(28 80% 55%)',
};

const hosts = computed<HostShare[]>(() => summary.value?.hosts ?? []);
const hasHosts = computed(() => hosts.value.length > 0);
const hostsTotal = computed(() => hosts.value.reduce((acc, h) => acc + h.projectsCount, 0));

function hostColor(type: string): string {
  return HOST_COLOR[type] ?? 'hsl(200 50% 60%)';
}
function hostPct(host: HostShare): number {
  return hostsTotal.value > 0 ? Math.round((host.projectsCount / hostsTotal.value) * 100) : 0;
}

const mostActive = computed<MostActiveProjectEntry[]>(() => summary.value?.mostActive ?? []);
const weakestCoverage = computed<WeakestCoverageProjectEntry[]>(
  () => summary.value?.weakestCoverage ?? [],
);
const topOutdated = computed<TopOutdatedDependencyEntry[]>(
  () => summary.value?.topOutdatedDependencies ?? [],
);
const projectDrift = computed<ProjectDriftEntry[]>(() => summary.value?.projectDrift ?? []);

const activeMax = computed<number>(() => Math.max(1, ...mostActive.value.map((p) => p.commits)));

function coverageHue(percent: number): number {
  if (percent >= 70) return 145;
  if (percent >= 50) return 38;
  return 0;
}

function driftHue(upToDate: number, total: number): number {
  const ratio = total > 0 ? upToDate / total : 0;
  if (ratio > 0.8) return 145;
  if (ratio > 0.5) return 38;
  return 0;
}
</script>

<template>
  <div class="dash" data-testid="dashboard-page">
    <div v-if="loading" class="loading" data-testid="dashboard-loading">
      {{ t('common.actions.loading') }}
    </div>

    <div v-else class="grid">
      <!-- KPI strip -->
      <div class="tile span-12 kpi-strip">
        <div v-for="(kpi, i) in kpis" :key="kpi.label" class="kpi" :style="{ '--i': i }">
          <div class="kpi-label">{{ kpi.label }}</div>
          <div class="kpi-value" :class="{ warn: kpi.warn }">{{ kpi.value }}</div>
          <div class="kpi-hint">{{ kpi.hint }}</div>
        </div>
      </div>

      <!-- Health rings -->
      <div class="tile span-5">
        <div class="tile-head">
          <div class="tile-title">{{ t('monitoring.dash.health.title') }}</div>
          <div class="tile-sub">{{ t('monitoring.dash.health.sub') }}</div>
        </div>
        <div v-if="hasHealthData" class="rings">
          <div v-for="r in rings" :key="r.label" class="health-ring">
            <div class="ring-svg">
              <svg width="88" height="88" viewBox="0 0 88 88">
                <circle
                  cx="44"
                  cy="44"
                  r="34"
                  fill="none"
                  stroke="var(--hub-line)"
                  stroke-width="6"
                />
                <circle
                  cx="44"
                  cy="44"
                  r="34"
                  fill="none"
                  :stroke="`hsl(${r.hue}, 70%, 60%)`"
                  stroke-width="6"
                  stroke-linecap="round"
                  :stroke-dasharray="ringDash(r.value).c"
                  :stroke-dashoffset="ringDash(r.value).offset"
                  transform="rotate(-90 44 44)"
                />
              </svg>
              <span class="ring-sub">{{ r.sub }}</span>
            </div>
            <div class="ring-label">{{ r.label }}</div>
          </div>
        </div>
        <div v-else class="empty-note">
          {{ t('monitoring.dash.health.empty') }}
        </div>
      </div>

      <!-- Languages -->
      <div class="tile span-4" data-testid="dashboard-languages-tile">
        <div class="tile-head">
          <div class="tile-title">{{ t('monitoring.dash.langs.title') }}</div>
          <div class="tile-sub">
            {{ t('monitoring.dash.langs.sub', { count: languages.length }) }}
          </div>
        </div>
        <div v-if="hasLanguages" class="lang-list">
          <div
            v-for="(lang, i) in languages"
            :key="lang.name"
            class="lang-row"
            :style="{ '--i': i }"
            data-testid="dashboard-language-row"
          >
            <span class="lang-name">{{ lang.name }}</span>
            <div class="lang-bar">
              <div
                class="lang-bar-fill"
                :style="{
                  background: `linear-gradient(90deg, hsl(${languageHue(lang.name)}, 60%, 55%) 0%, hsl(${languageHue(lang.name)}, 60%, 65%) 100%)`,
                  transform: `scaleX(${languagePct(lang.projectsCount)})`,
                }"
              />
            </div>
            <span class="lang-count">{{ lang.projectsCount }}</span>
          </div>
        </div>
        <div v-else class="empty-note">{{ t('monitoring.dash.langs.empty') }}</div>
      </div>

      <!-- Hosts -->
      <div class="tile span-3" data-testid="dashboard-hosts-tile">
        <div class="tile-head">
          <div class="tile-title">{{ t('monitoring.dash.hosts.title') }}</div>
          <div class="tile-sub">{{ t('monitoring.dash.hosts.sub') }}</div>
        </div>
        <div v-if="hasHosts" class="hosts">
          <div v-if="hostsTotal > 0" class="hosts-bar">
            <div
              v-for="h in hosts"
              :key="`bar-${h.type}`"
              class="hosts-bar-segment"
              :style="{ flex: h.projectsCount, background: hostColor(h.type) }"
            />
          </div>
          <div v-for="h in hosts" :key="h.type" class="host-line" data-testid="dashboard-host-line">
            <span class="host-dot" :style="{ background: hostColor(h.type) }" />
            <span class="host-label">{{ h.label }}</span>
            <span class="host-meta">
              {{
                h.connected
                  ? t('monitoring.dash.hosts.connected')
                  : t('monitoring.dash.hosts.disconnected')
              }}
              · {{ h.projectsCount }} ({{ hostPct(h) }}%)
            </span>
          </div>
        </div>
        <div v-else class="empty-note">{{ t('monitoring.dash.hosts.empty') }}</div>
      </div>

      <!-- Most active -->
      <div class="tile span-6">
        <div class="tile-head">
          <div class="tile-title">{{ t('monitoring.dash.active.title') }}</div>
          <div class="tile-sub">{{ t('monitoring.dash.active.sub') }}</div>
        </div>
        <div v-if="mostActive.length === 0" class="empty-note">
          {{ t('monitoring.dash.active.empty') }}
        </div>
        <ul v-else class="active-list">
          <li v-for="(p, i) in mostActive" :key="p.id" class="active-row">
            <span class="active-rank">{{ i + 1 }}</span>
            <span class="active-name" :title="p.name">{{ p.name }}</span>
            <div class="active-bar" aria-hidden="true">
              <span
                class="active-bar-fill"
                :style="{ width: `${(p.commits / activeMax) * 100}%` }"
              />
            </div>
            <span class="active-count">{{ p.commits }}</span>
          </li>
        </ul>
      </div>

      <!-- Weak coverage -->
      <div class="tile span-6">
        <div class="tile-head">
          <div class="tile-title">{{ t('monitoring.dash.weak.title') }}</div>
          <div class="tile-sub">{{ t('monitoring.dash.weak.sub') }}</div>
        </div>
        <div v-if="weakestCoverage.length === 0" class="empty-note">
          {{ t('monitoring.dash.weak.empty') }}
        </div>
        <ul v-else class="coverage-list">
          <li
            v-for="p in weakestCoverage"
            :key="p.id"
            class="coverage-row"
            :style="{ '--hue': coverageHue(p.coveragePercent) }"
          >
            <span class="coverage-name" :title="p.name">{{ p.name }}</span>
            <div class="coverage-bar" aria-hidden="true">
              <span
                class="coverage-bar-fill"
                :style="{ width: `${Math.max(2, Math.min(100, p.coveragePercent))}%` }"
              />
            </div>
            <span class="coverage-pct">{{ Math.round(p.coveragePercent) }}%</span>
          </li>
        </ul>
      </div>

      <!-- Top outdated -->
      <div class="tile span-7">
        <div class="tile-head">
          <div class="tile-title">{{ t('monitoring.dash.outdated.title') }}</div>
          <div class="tile-sub">{{ t('monitoring.dash.outdated.sub') }}</div>
        </div>
        <div v-if="topOutdated.length === 0" class="empty-note">
          {{ t('monitoring.dash.outdated.empty') }}
        </div>
        <ul v-else class="outdated-list">
          <li v-for="d in topOutdated" :key="d.name" class="outdated-row">
            <div class="outdated-head">
              <span class="outdated-name" :title="d.name">{{ d.name }}</span>
              <span class="outdated-meta">
                {{ t('monitoring.dash.outdated.in_projects', { n: d.projectsCount }) }} ·
                {{
                  d.occurrences
                    .slice(0, 3)
                    .map((o) => o.projectName)
                    .join(' · ')
                }}
              </span>
            </div>
            <div class="outdated-chips">
              <span
                v-for="(occ, idx) in d.occurrences.slice(0, 3)"
                :key="`${d.name}-${idx}`"
                class="outdated-chip"
                :title="`${occ.projectName} · ${occ.currentVersion} → ${occ.latestVersion}`"
              >
                {{ occ.currentVersion }}→{{ occ.latestVersion }}
              </span>
              <span v-if="d.occurrences.length > 3" class="outdated-more">
                +{{ d.occurrences.length - 3 }}
              </span>
            </div>
          </li>
        </ul>
      </div>

      <!-- Drift -->
      <div class="tile span-5">
        <div class="tile-head">
          <div class="tile-title">{{ t('monitoring.dash.drift.title') }}</div>
          <div class="tile-sub">{{ t('monitoring.dash.drift.sub') }}</div>
        </div>
        <div v-if="projectDrift.length === 0" class="empty-note">
          {{ t('monitoring.dash.drift.empty') }}
        </div>
        <ul v-else class="drift-list">
          <li
            v-for="p in projectDrift"
            :key="p.slug"
            class="drift-row"
            :style="{ '--hue': driftHue(p.upToDate, p.total) }"
          >
            <div class="drift-content">
              <span class="drift-name" :title="p.name">{{ p.name }}</span>
              <div class="drift-blocks" aria-hidden="true">
                <span
                  v-for="k in p.total"
                  :key="k"
                  class="drift-block"
                  :class="{ ok: k <= p.upToDate }"
                />
              </div>
            </div>
            <span class="drift-ratio">{{ p.upToDate }}/{{ p.total }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dash {
  color: var(--hub-fg-0);
  font-family: var(--hub-font-sans);
}

.loading {
  margin: 40px 0;
  font-family: var(--hub-font-mono);
  font-size: 12px;
  color: var(--hub-fg-2);
}

.grid {
  display: grid;
  grid-template-columns: repeat(12, 1fr);
  gap: 16px;
  animation: dash-in 500ms var(--hub-ease);
}
@keyframes dash-in {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.tile {
  padding: 18px;
  border-radius: 14px;
  border: 1px solid var(--hub-line);
  background: color-mix(in oklab, var(--hub-bg-1) 70%, transparent);
  display: flex;
  flex-direction: column;
  gap: 14px;
  min-height: 0;
}
.span-3 {
  grid-column: span 3;
}
.span-4 {
  grid-column: span 4;
}
.span-5 {
  grid-column: span 5;
}
.span-6 {
  grid-column: span 6;
}
.span-7 {
  grid-column: span 7;
}
.span-12 {
  grid-column: span 12;
}
@media (max-width: 1200px) {
  .span-3,
  .span-4,
  .span-5,
  .span-6,
  .span-7 {
    grid-column: span 12;
  }
}

.tile-head {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.tile-title {
  font-family: var(--hub-font-serif);
  font-style: italic;
  font-size: 18px;
  color: var(--hub-fg-0);
}
.tile-sub {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  color: var(--hub-fg-3);
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.empty-note {
  padding: 18px;
  text-align: center;
  border: 1px dashed var(--hub-line);
  border-radius: 10px;
  font-family: var(--hub-font-mono);
  font-size: 10px;
  letter-spacing: 0.06em;
  color: var(--hub-fg-3);
}

/* KPI strip */
.kpi-strip {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 0;
  padding: 0;
  overflow: hidden;
  border-radius: 14px;
}
@media (max-width: 980px) {
  .kpi-strip {
    grid-template-columns: repeat(2, 1fr);
  }
}
.kpi {
  padding: 16px 18px;
  border-right: 1px solid var(--hub-line);
  display: flex;
  flex-direction: column;
  gap: 5px;
  animation: kpi-in 500ms var(--hub-ease) both;
  animation-delay: calc(var(--i) * 60ms);
}
.kpi:last-child {
  border-right: none;
}
@keyframes kpi-in {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.kpi-label {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  color: var(--hub-fg-3);
  letter-spacing: 0.12em;
  text-transform: uppercase;
}
.kpi-value {
  font-family: var(--hub-font-serif);
  font-size: 32px;
  line-height: 1;
  color: var(--hub-fg-0);
  letter-spacing: -0.01em;
}
.kpi-value.warn {
  color: var(--hub-bad);
}
.kpi-hint {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-2);
}

/* Rings */
.rings {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}
.health-ring {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}
.ring-svg {
  position: relative;
  width: 88px;
  height: 88px;
}
.ring-sub {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  font-family: var(--hub-font-serif);
  font-style: italic;
  font-size: 16px;
  color: var(--hub-fg-0);
}
.ring-label {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-2);
  letter-spacing: 0.08em;
  text-align: center;
}

/* Languages */
.lang-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.lang-row {
  display: grid;
  grid-template-columns: 100px 1fr 32px;
  align-items: center;
  gap: 10px;
  animation: lang-in 500ms var(--hub-ease) both;
  animation-delay: calc(var(--i) * 60ms);
}
@keyframes lang-in {
  from {
    opacity: 0;
    transform: translateY(4px);
  }
  to {
    opacity: 1;
    transform: none;
  }
}
.lang-name {
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-1);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.lang-bar {
  height: 8px;
  background: var(--hub-bg-3, color-mix(in oklab, var(--hub-fg-0) 6%, transparent));
  border-radius: 4px;
  overflow: hidden;
  position: relative;
}
.lang-bar-fill {
  position: absolute;
  inset: 0;
  transform-origin: left;
  border-radius: 4px;
  animation: bar-fill 700ms var(--hub-ease) both;
  animation-delay: calc(var(--i) * 60ms);
}
@keyframes bar-fill {
  from {
    transform: scaleX(0);
  }
}
.lang-count {
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-2);
  text-align: right;
  tabular-nums: 1;
}

/* Hosts */
.hosts {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.hosts-bar {
  height: 12px;
  border-radius: 6px;
  overflow: hidden;
  display: flex;
  background: var(--hub-bg-3, color-mix(in oklab, var(--hub-fg-0) 6%, transparent));
}
.hosts-bar-segment {
  height: 100%;
  transition: flex 600ms var(--hub-ease);
}
.host-line {
  display: flex;
  align-items: center;
  gap: 10px;
  font-family: var(--hub-font-mono);
  font-size: 11px;
}
.host-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}
.host-label {
  color: var(--hub-fg-1);
  flex: 1;
}
.host-meta {
  font-size: 10px;
  color: var(--hub-fg-3);
}

/* Highlights lists */
.active-list,
.coverage-list,
.outdated-list,
.drift-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.active-row {
  display: grid;
  grid-template-columns: 16px 1fr 70px 48px;
  align-items: center;
  gap: 10px;
  padding: 6px 10px;
  border-radius: 8px;
  transition: background 160ms var(--hub-ease);
}
.active-row:hover {
  background: var(--hub-bg-3);
}
.active-rank {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-3);
}
.active-name {
  font-family: var(--hub-font-mono);
  font-size: 12px;
  color: var(--hub-fg-0);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.active-bar {
  height: 6px;
  border-radius: 3px;
  background: var(--hub-bg-3);
  overflow: hidden;
}
.active-bar-fill {
  display: block;
  height: 100%;
  border-radius: 3px;
  background: linear-gradient(90deg, hsl(145 60% 55%), hsl(195 60% 60%));
}
.active-count {
  text-align: right;
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-1);
}

.coverage-row {
  display: grid;
  grid-template-columns: 1fr 100px 44px;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  border-radius: 10px;
  border: 1px solid var(--hub-line);
  transition: all 160ms var(--hub-ease);
}
.coverage-row:hover {
  border-color: var(--hub-accent);
}
.coverage-name {
  font-family: var(--hub-font-mono);
  font-size: 12px;
  color: var(--hub-fg-0);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.coverage-bar {
  height: 6px;
  border-radius: 3px;
  background: var(--hub-bg-3);
  overflow: hidden;
}
.coverage-bar-fill {
  display: block;
  height: 100%;
  border-radius: 3px;
  background: hsl(var(--hue), 60%, 55%);
}
.coverage-pct {
  text-align: right;
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: hsl(var(--hue), 60%, 65%);
}

.outdated-row {
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid var(--hub-line);
  background: var(--hub-bg-2);
}
.outdated-head {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}
.outdated-name {
  font-family: var(--hub-font-mono);
  font-size: 12px;
  color: var(--hub-fg-0);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.outdated-meta {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  color: var(--hub-fg-3);
  letter-spacing: 0.06em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.outdated-chips {
  display: flex;
  align-items: center;
  gap: 4px;
  flex-wrap: wrap;
}
.outdated-chip {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  padding: 3px 6px;
  border-radius: 4px;
  border: 1px solid var(--hub-line-strong);
  background: var(--hub-bg-1);
  color: var(--hub-fg-2);
}
.outdated-more {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  color: var(--hub-fg-3);
}

.drift-row {
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: center;
  gap: 8px;
  padding: 6px 10px;
  border-radius: 8px;
  transition: background 160ms var(--hub-ease);
}
.drift-row:hover {
  background: var(--hub-bg-3);
}
.drift-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}
.drift-name {
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-0);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.drift-blocks {
  display: flex;
  gap: 2px;
  height: 4px;
}
.drift-block {
  flex: 1;
  min-width: 2px;
  background: var(--hub-bg-3);
  border-radius: 1px;
}
.drift-block.ok {
  background: hsl(var(--hue), 60%, 55%);
}
.drift-ratio {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: hsl(var(--hue), 60%, 65%);
  white-space: nowrap;
}
</style>
