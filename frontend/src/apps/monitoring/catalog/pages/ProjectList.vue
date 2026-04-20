<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import type {
  FrameworkLagSummary,
  Project,
  RuntimeSummary,
  TechStackSummary,
} from '@/apps/monitoring/catalog/types/project';

import ActivityChart from '@/apps/monitoring/catalog/components/ActivityChart.vue';
import BranchPicker from '@/apps/monitoring/catalog/components/BranchPicker.vue';
import CoverageBreakdown from '@/apps/monitoring/catalog/components/CoverageBreakdown.vue';
import ProjectDependenciesPanel from '@/apps/monitoring/catalog/components/ProjectDependenciesPanel.vue';
import { projectService } from '@/apps/monitoring/catalog/services/project.service';
import { useProjectStore } from '@/apps/monitoring/catalog/stores/project';

type ActivityLevel = 'high' | 'idle' | 'low' | 'medium' | 'very_high';
type CoverageTone = 'bad' | 'good' | 'warn';
type HostFilter = 'all' | 'github' | 'gitlab' | 'other';
type PartRole = 'back' | 'front' | 'infra' | 'other';
type RuntimeStatus = 'behind_lts' | 'lts' | 'unknown';
type SortKey = 'name' | 'recent';

const { locale, t } = useI18n();
const relativeFormatter = computed(
  () => new Intl.RelativeTimeFormat(locale.value, { numeric: 'auto' }),
);
const projectStore = useProjectStore();

const query = ref('');
const sort = ref<SortKey>('recent');
const hostFilter = ref<HostFilter>('all');
const selectedId = ref<null | string>(null);

onMounted(async () => {
  await projectStore.fetchAll(1, 100);
  if (projectStore.projects.length > 0) {
    selectedId.value = projectStore.projects[0].id;
  }
});

function hostGlyph(h: 'github' | 'gitlab' | 'other'): string {
  if (h === 'github') return 'gh';
  if (h === 'gitlab') return 'gl';
  return 'git';
}

function hostOf(url: string): 'github' | 'gitlab' | 'other' {
  if (!url) return 'other';
  if (url.includes('github')) return 'github';
  if (url.includes('gitlab')) return 'gitlab';
  return 'other';
}

const filtered = computed<Project[]>(() => {
  let list = [...projectStore.projects];
  if (hostFilter.value !== 'all') {
    list = list.filter((p) => hostOf(p.repositoryUrl) === hostFilter.value);
  }
  const q = query.value.trim().toLowerCase();
  if (q) {
    list = list.filter(
      (p) =>
        p.name.toLowerCase().includes(q) ||
        p.slug.toLowerCase().includes(q) ||
        p.techStacks.some((ts) => ts.language.toLowerCase().includes(q)),
    );
  }
  if (sort.value === 'name') list.sort((a, b) => a.name.localeCompare(b.name));
  if (sort.value === 'recent') {
    list.sort((a, b) => {
      const aKey = a.lastActivityAt ?? a.updatedAt;
      const bKey = b.lastActivityAt ?? b.updatedAt;
      return (bKey || '').localeCompare(aKey || '');
    });
  }
  return list;
});

const filtering = computed(() => query.value.length > 0 || hostFilter.value !== 'all');
const paddedCount = computed(() => String(filtered.value.length).padStart(2, '0'));
const paddedTotal = computed(() => String(projectStore.projects.length).padStart(2, '0'));

const selected = computed<null | Project>(
  () => filtered.value.find((p) => p.id === selectedId.value) ?? filtered.value[0] ?? null,
);

const branchesByProject = ref<Record<string, string[]>>({});
const branchesLoading = ref<Record<string, boolean>>({});
const trackedBranchByProject = ref<Record<string, string>>({});

const TRACKED_BRANCH_KEY = 'monark_tracked_branches';

function loadPersistedTrackedBranches(): void {
  try {
    const raw = localStorage.getItem(TRACKED_BRANCH_KEY);
    if (raw) {
      const parsed: unknown = JSON.parse(raw);
      if (parsed && typeof parsed === 'object') {
        trackedBranchByProject.value = parsed as Record<string, string>;
      }
    }
  } catch {
    trackedBranchByProject.value = {};
  }
}
loadPersistedTrackedBranches();

async function ensureBranchesLoaded(projectId: string): Promise<void> {
  if (branchesByProject.value[projectId] !== undefined) return;
  if (branchesLoading.value[projectId]) return;
  branchesLoading.value[projectId] = true;
  try {
    const list = await projectService.listBranches(projectId);
    branchesByProject.value[projectId] = list;
  } catch {
    branchesByProject.value[projectId] = [];
  } finally {
    branchesLoading.value[projectId] = false;
  }
}

function persistTrackedBranches(): void {
  try {
    localStorage.setItem(TRACKED_BRANCH_KEY, JSON.stringify(trackedBranchByProject.value));
  } catch {
    // ignore quota errors
  }
}

const currentBranches = computed<string[]>(() => {
  const p = selected.value;
  if (!p) return [];
  const loaded = branchesByProject.value[p.id];
  if (loaded && loaded.length > 0) return loaded;
  return [p.defaultBranch];
});

const currentBranch = computed<string>(() => {
  const p = selected.value;
  if (!p) return '';
  return trackedBranchByProject.value[p.id] ?? p.defaultBranch;
});

const currentBranchLoading = computed<boolean>(() => {
  const p = selected.value;
  if (!p) return false;
  return branchesLoading.value[p.id] ?? false;
});

function onBranchSelected(branch: string): void {
  const p = selected.value;
  if (!p) return;
  trackedBranchByProject.value = { ...trackedBranchByProject.value, [p.id]: branch };
  persistTrackedBranches();
}

watch(
  () => selected.value?.id,
  (id) => {
    if (id !== undefined) void ensureBranchesLoaded(id);
  },
  { immediate: true },
);

function activityLevel(commits30d: number): ActivityLevel {
  if (commits30d <= 0) return 'idle';
  if (commits30d <= 10) return 'low';
  if (commits30d <= 40) return 'medium';
  if (commits30d <= 100) return 'high';
  return 'very_high';
}

function coverageTone(value: null | number): CoverageTone | null {
  if (value === null || Number.isNaN(value)) return null;
  if (value >= 80) return 'good';
  if (value >= 50) return 'warn';
  return 'bad';
}

function formatCoverage(v: null | number): string {
  if (v === null || Number.isNaN(v)) return '—';
  return `${Math.round(v)}%`;
}

function formatRelative(iso: null | string): string {
  if (!iso) return '—';
  const then = new Date(iso).getTime();
  if (Number.isNaN(then)) return '—';
  const rtf = relativeFormatter.value;
  const diffSec = Math.floor((then - Date.now()) / 1000);
  const abs = Math.abs(diffSec);
  if (abs < 60) return rtf.format(diffSec, 'second');
  if (abs < 3600) return rtf.format(Math.round(diffSec / 60), 'minute');
  if (abs < 86400) return rtf.format(Math.round(diffSec / 3600), 'hour');
  if (abs < 2592000) return rtf.format(Math.round(diffSec / 86400), 'day');
  if (abs < 31536000) return rtf.format(Math.round(diffSec / 2592000), 'month');
  return rtf.format(Math.round(diffSec / 31536000), 'year');
}

function miniBarHeight(value: number, max: number): number {
  if (max <= 0) return 0;
  return Math.max(0, Math.min(100, (value / max) * 100));
}

function miniBarOpacity(value: number, max: number): number {
  if (max <= 0) return 0.3;
  return Number((0.3 + (value / max) * 0.7).toFixed(2));
}

function select(id: string) {
  selectedId.value = id;
}

function shortSha(sha: null | string): string {
  if (!sha) return '—';
  return sha.slice(0, 7);
}

function sparklinePoints(series: number[], width: number, height: number): string {
  if (series.length === 0) return '';
  const max = Math.max(1, ...series);
  const step = series.length > 1 ? width / (series.length - 1) : 0;
  return series
    .map((v, i) => {
      const x = Number((i * step).toFixed(2));
      const y = Number((height - (v / max) * (height - 2) - 1).toFixed(2));
      return `${x},${y}`;
    })
    .join(' ');
}

const HOSTS: { key: HostFilter; label: string }[] = [
  { key: 'all', label: 'all' },
  { key: 'github', label: 'github' },
  { key: 'gitlab', label: 'gitlab' },
];
const SORTS: { key: SortKey; label: string }[] = [
  { key: 'recent', label: 'activity' },
  { key: 'name', label: 'name' },
];

const LANG_COLORS: Record<string, string> = {
  Astro: '#ff5d01',
  'C#': '#178600',
  'C++': '#f34b7d',
  Go: '#00add8',
  Java: '#b07219',
  JavaScript: '#f1e05a',
  Kotlin: '#a97bff',
  PHP: '#777bb4',
  Python: '#3572A5',
  Ruby: '#cc342d',
  Rust: '#dea584',
  Shell: '#89e051',
  Swift: '#f05138',
  TypeScript: '#3178c6',
};

type ProjectKind = 'backend' | 'config' | 'frontend' | 'fullstack';

function langColor(name: string): string {
  return LANG_COLORS[name] ?? '#6b7280';
}

const FRONTEND_LANGS = new Set(['Astro', 'JavaScript', 'Svelte', 'TypeScript', 'Vue']);
const BACKEND_LANGS = new Set([
  'C#',
  'C++',
  'Clojure',
  'Elixir',
  'Go',
  'Java',
  'Kotlin',
  'PHP',
  'Python',
  'Ruby',
  'Rust',
  'Scala',
  'Swift',
]);
const INFRA_LANGS = new Set(['Dockerfile', 'HCL', 'Makefile', 'Nginx', 'Nix', 'Shell', 'YAML']);

const PART_META: Record<PartRole, { hue: number; order: number }> = {
  back: { hue: 200, order: 0 },
  front: { hue: 320, order: 1 },
  infra: { hue: 55, order: 2 },
  other: { hue: 260, order: 3 },
};

const RUNTIME_FOR_LANG: Record<string, string> = {
  'C#': '.NET',
  Clojure: 'JVM',
  Elixir: 'Erlang/BEAM',
  Go: 'Go',
  Java: 'JVM',
  JavaScript: 'Node.js',
  Kotlin: 'JVM',
  PHP: 'PHP',
  Python: 'Python',
  Ruby: 'Ruby',
  Rust: 'Rust',
  Scala: 'JVM',
  Swift: 'Swift',
  TypeScript: 'Node.js',
};

const RUNTIME_PRODUCT_TO_LANGS: Record<string, string[]> = {
  go: ['Go'],
  nodejs: ['JavaScript', 'Node.js', 'TypeScript'],
  php: ['PHP'],
  python: ['Python'],
  ruby: ['Ruby'],
  rust: ['Rust'],
};

interface LagSegment {
  count: number;
  key: 'major' | 'minor' | 'ok' | 'patch';
  label: string;
  weight: number;
}

interface RuntimeMarker {
  color: string;
  keys: string[];
  level: 'bottom' | 'top';
  position: number;
  prio: number;
  value: string;
}

interface StackPartGroup {
  frameworks: TechStackSummary[];
  languages: string[];
  primaryLabel: string;
  primaryRuntime: null | string;
  role: PartRole;
  techStacks: TechStackSummary[];
}

function classify(langs: string[]): null | ProjectKind {
  if (langs.length === 0) return null;
  const hasFront = langs.some((l) => FRONTEND_LANGS.has(l));
  const hasBack = langs.some((l) => BACKEND_LANGS.has(l));
  if (hasFront && hasBack) return 'fullstack';
  if (hasBack) return 'backend';
  if (hasFront) return 'frontend';
  return 'config';
}

function compareSemver(a: null | string, b: null | string): number {
  if (a === null || b === null) return 0;
  const pa = a.split('.').map((n) => parseInt(n, 10));
  const pb = b.split('.').map((n) => parseInt(n, 10));
  const len = Math.max(pa.length, pb.length);
  for (let i = 0; i < len; i++) {
    const va = Number.isFinite(pa[i]) ? pa[i] : 0;
    const vb = Number.isFinite(pb[i]) ? pb[i] : 0;
    if (va !== vb) return va - vb;
  }
  return 0;
}

function groupedParts(p: Project): StackPartGroup[] {
  const withFramework = p.techStacks.filter((ts) => ts.framework !== null);
  const pool = withFramework.length > 0 ? withFramework : p.techStacks;
  const groups = new Map<PartRole, TechStackSummary[]>();
  for (const ts of pool) {
    const role = partRole(ts.language);
    if (!groups.has(role)) groups.set(role, []);
    groups.get(role)!.push(ts);
  }
  return [...groups.entries()]
    .map(([role, techStacks]) => {
      const frameworks = techStacks.filter((ts) => ts.framework !== null);
      const primary = frameworks[0] ?? techStacks[0];
      const languages = [...new Set(techStacks.map((ts) => ts.language))];
      const runtime = languages.map((l) => RUNTIME_FOR_LANG[l]).find(Boolean) ?? null;
      return {
        frameworks,
        languages,
        primaryLabel: primary.framework ?? primary.language,
        primaryRuntime: runtime,
        role,
        techStacks,
      };
    })
    .sort((a, b) => PART_META[a.role].order - PART_META[b.role].order);
}

function isApplicative(kind: null | ProjectKind): boolean {
  return kind === 'frontend' || kind === 'backend' || kind === 'fullstack';
}

function lagAriaLabel(lag: FrameworkLagSummary): string {
  return t('monitoring.repos.lag.aria', {
    major: lag.major,
    minor: lag.minor,
    ok: lag.upToDate,
    patch: lag.patch,
  });
}

function lagSegments(lag: FrameworkLagSummary): LagSegment[] {
  const total = lag.major + lag.minor + lag.patch + lag.upToDate;
  if (total === 0) return [];
  return [
    {
      count: lag.major,
      key: 'major' as const,
      label: t('monitoring.repos.lag.major'),
      weight: lag.major,
    },
    {
      count: lag.minor,
      key: 'minor' as const,
      label: t('monitoring.repos.lag.minor'),
      weight: lag.minor,
    },
    {
      count: lag.patch,
      key: 'patch' as const,
      label: t('monitoring.repos.lag.patch'),
      weight: lag.patch,
    },
    {
      count: lag.upToDate,
      key: 'ok' as const,
      label: t('monitoring.repos.lag.up_to_date'),
      weight: lag.upToDate,
    },
  ].filter((s) => s.weight > 0);
}

function partRole(language: string): PartRole {
  if (BACKEND_LANGS.has(language)) return 'back';
  if (FRONTEND_LANGS.has(language)) return 'front';
  if (INFRA_LANGS.has(language)) return 'infra';
  return 'other';
}

function projectKind(p: Project): null | ProjectKind {
  const detected = p.techStacks.filter((ts) => ts.framework !== null).map((ts) => ts.language);
  if (detected.length > 0) {
    return classify(detected);
  }
  const top = p.techStacks[0];
  if (!top) return null;
  return classify([top.language]);
}

function resolveCoverage(p: Project): null | number {
  if (p.coveragePercent !== null) return p.coveragePercent;
  return isApplicative(projectKind(p)) ? 0 : null;
}

function runtimeLabel(rt: RuntimeSummary, p: Project): string {
  const langs = RUNTIME_PRODUCT_TO_LANGS[rt.productKey] ?? [];
  const matched = p.techStacks.find((ts) => ts.framework !== null && langs.includes(ts.language));
  return matched?.framework ?? rt.name;
}

function runtimeMarkerPosition(
  value: null | string,
  anchors: { latest: null | string; lts: null | string; min: null | string },
): number {
  const stops = [anchors.min, anchors.lts, anchors.latest]
    .filter((v): v is string => v !== null)
    .sort(compareSemver);
  if (value === null || stops.length === 0) return 50;
  const min = stops[0];
  const max = stops[stops.length - 1];
  if (compareSemver(min, max) === 0) return 50;
  const toNum = (v: string): number => {
    const parts = v.split('.').map((n) => parseInt(n, 10) || 0);
    return (parts[0] ?? 0) * 1e6 + (parts[1] ?? 0) * 1e3 + (parts[2] ?? 0);
  };
  const a = toNum(min);
  const b = toNum(max);
  const c = toNum(value);
  if (b === a) return 50;
  return Math.max(2, Math.min(98, ((c - a) / (b - a)) * 100));
}

function runtimeMarkers(rt: RuntimeSummary): RuntimeMarker[] {
  const raw: { color: string; key: string; prio: number; value: null | string }[] = [
    { color: 'var(--hub-accent)', key: 'min', prio: 3, value: rt.minVersion },
    { color: '#4ade80', key: 'lts', prio: 2, value: rt.ltsVersion },
    { color: 'var(--hub-fg-1)', key: 'latest', prio: 1, value: rt.latestVersion },
  ];
  const anchors = { latest: rt.latestVersion, lts: rt.ltsVersion, min: rt.minVersion };
  const grouped = new Map<string, RuntimeMarker>();
  for (const m of raw) {
    if (m.value === null) continue;
    const existing = grouped.get(m.value);
    if (existing !== undefined) {
      existing.keys.push(m.key);
      if (m.prio > existing.prio) {
        existing.color = m.color;
        existing.prio = m.prio;
      }
      continue;
    }
    grouped.set(m.value, {
      color: m.color,
      keys: [m.key],
      level: 'bottom',
      position: runtimeMarkerPosition(m.value, anchors),
      prio: m.prio,
      value: m.value,
    });
  }
  const sorted = [...grouped.values()].sort((a, b) => a.position - b.position);
  const COLLISION_THRESHOLD = 18;
  for (let i = 1; i < sorted.length; i++) {
    if (sorted[i].position - sorted[i - 1].position < COLLISION_THRESHOLD) {
      sorted[i].level = sorted[i - 1].level === 'bottom' ? 'top' : 'bottom';
    }
  }
  return sorted;
}

function runtimeRole(rt: RuntimeSummary, p: Project): PartRole {
  const langs = RUNTIME_PRODUCT_TO_LANGS[rt.productKey] ?? [];
  const matched = p.techStacks.find((ts) => ts.framework !== null && langs.includes(ts.language));
  if (matched === undefined) {
    const any = p.techStacks.find((ts) => langs.includes(ts.language));
    if (any === undefined) return 'other';
    return partRole(any.language);
  }
  return partRole(matched.language);
}

function runtimeStatus(r: RuntimeSummary): RuntimeStatus {
  if (r.minVersion === null || r.ltsVersion === null) return 'unknown';
  return compareSemver(r.minVersion, r.ltsVersion) >= 0 ? 'lts' : 'behind_lts';
}
</script>

<template>
  <div class="repos" data-testid="repos-page">
    <div class="split">
      <aside class="list-pane">
        <div class="toolbar">
          <div class="toolbar-row">
            <span class="count-label">
              REPOS · {{ paddedCount }}<span v-if="filtering"> / {{ paddedTotal }}</span>
            </span>
            <div class="search">
              <span class="search-icon" aria-hidden="true">⌕</span>
              <input
                v-model="query"
                type="search"
                :placeholder="t('monitoring.repos.search_placeholder')"
                :aria-label="t('monitoring.repos.search_placeholder')"
              />
              <button
                v-if="query"
                type="button"
                class="search-clear"
                :aria-label="t('common.actions.clear')"
                @click="query = ''"
              >
                ×
              </button>
            </div>
          </div>
          <div class="toolbar-row segs">
            <div class="seg-group" role="radiogroup" aria-label="host">
              <button
                v-for="h in HOSTS"
                :key="h.key"
                type="button"
                class="seg"
                :class="{ active: hostFilter === h.key }"
                :aria-pressed="hostFilter === h.key"
                @click="hostFilter = h.key"
              >
                {{ h.label }}
              </button>
            </div>
            <div class="seg-group" role="radiogroup" aria-label="sort">
              <button
                v-for="s in SORTS"
                :key="s.key"
                type="button"
                class="seg"
                :class="{ active: sort === s.key }"
                :aria-pressed="sort === s.key"
                @click="sort = s.key"
              >
                {{ s.label }}
              </button>
            </div>
          </div>
        </div>

        <div v-if="projectStore.loading" class="loading">
          {{ t('common.actions.loading') }}
        </div>

        <div v-else class="rows">
          <button
            v-for="(p, i) in filtered"
            :key="p.id"
            type="button"
            class="row"
            :class="{ active: p.id === selected?.id }"
            :style="{ '--i': i }"
            :data-testid="`repo-row-${p.slug}`"
            @click="select(p.id)"
          >
            <span v-if="p.id === selected?.id" class="row-bar" />
            <div class="row-top">
              <div class="row-lead">
                <span class="host-icon">{{ hostGlyph(hostOf(p.repositoryUrl)) }}</span>
                <span class="row-name">{{ p.name }}</span>
                <span v-if="projectKind(p)" class="kind-tag" :class="`kind-${projectKind(p)}`">
                  {{ t(`monitoring.repos.kind.${projectKind(p)}`) }}
                </span>
              </div>
              <span class="row-branch">{{ p.defaultBranch }}</span>
            </div>
            <div class="row-metrics">
              <span v-if="p.techStacks.length > 0" class="lang-chip">
                <span class="lang-dots" aria-hidden="true">
                  <span
                    v-for="(ts, idx) in p.techStacks.slice(0, 3)"
                    :key="ts.language"
                    class="lang-dot"
                    :style="{
                      background: langColor(ts.language),
                      boxShadow: `0 0 8px ${langColor(ts.language)}60`,
                      marginLeft: idx === 0 ? '0' : '-3px',
                    }"
                  />
                </span>
                <span class="lang-labels">
                  {{
                    p.techStacks
                      .slice(0, 3)
                      .map((ts) => ts.language)
                      .join(' · ')
                  }}
                </span>
              </span>
              <span v-else-if="p.lastActivityAt" class="row-fallback">
                <span class="row-fallback-label">{{
                  t('monitoring.repos.row_last_activity')
                }}</span>
                <span class="row-fallback-value">{{ formatRelative(p.lastActivityAt) }}</span>
              </span>
              <span v-else class="row-fallback muted">
                <span class="row-fallback-label">{{ t('monitoring.repos.row_no_activity') }}</span>
              </span>
              <svg class="spark" width="70" height="18" aria-hidden="true">
                <polyline
                  v-if="p.commitsDailySeries.length > 0 && p.commitsLast30d > 0"
                  :points="sparklinePoints(p.commitsDailySeries, 70, 18)"
                  fill="none"
                  :stroke="
                    p.id === selected?.id
                      ? 'var(--hub-accent)'
                      : 'color-mix(in oklab, var(--hub-fg-2) 35%, transparent)'
                  "
                  stroke-width="1.3"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <line
                  v-else
                  x1="0"
                  x2="70"
                  y1="9"
                  y2="9"
                  stroke="color-mix(in oklab, var(--hub-fg-3) 30%, transparent)"
                  stroke-width="1"
                  stroke-dasharray="2 3"
                />
              </svg>
              <span
                class="coverage-empty"
                :class="{ 'has-value': resolveCoverage(p) !== null }"
                :aria-label="
                  resolveCoverage(p) === null
                    ? 'coverage unknown'
                    : `coverage ${Math.round(resolveCoverage(p) ?? 0)}%`
                "
              >
                {{ formatCoverage(resolveCoverage(p)) }}
              </span>
            </div>
            <div
              v-if="
                p.frameworkLag.major +
                  p.frameworkLag.minor +
                  p.frameworkLag.patch +
                  p.frameworkLag.upToDate >
                0
              "
              class="row-lag"
              :aria-label="lagAriaLabel(p.frameworkLag)"
            >
              <div class="lag-bar">
                <span
                  v-for="seg in lagSegments(p.frameworkLag)"
                  :key="seg.key"
                  class="lag-seg"
                  :class="`lag-${seg.key}`"
                  :style="{ flexGrow: seg.weight }"
                  :title="`${seg.count} ${seg.label}`"
                />
              </div>
              <span class="lag-legend">
                <span v-if="p.frameworkLag.major > 0" class="lag-count lag-major">
                  {{ p.frameworkLag.major }}<span class="lag-dot-label">maj</span>
                </span>
                <span v-if="p.frameworkLag.minor > 0" class="lag-count lag-minor">
                  {{ p.frameworkLag.minor }}<span class="lag-dot-label">min</span>
                </span>
                <span v-if="p.frameworkLag.patch > 0" class="lag-count lag-patch">
                  {{ p.frameworkLag.patch }}<span class="lag-dot-label">pat</span>
                </span>
                <span
                  v-if="
                    p.frameworkLag.major + p.frameworkLag.minor + p.frameworkLag.patch === 0 &&
                    p.vulnerabilitiesCount === 0
                  "
                  class="lag-count lag-ok"
                >
                  {{ t('monitoring.repos.lag.all_ok') }}
                </span>
                <span
                  v-if="p.vulnerabilitiesCount > 0"
                  class="lag-count lag-cve"
                  :title="t('monitoring.repos.detail.row_cve_count', { n: p.vulnerabilitiesCount })"
                >
                  <span class="lag-cve-glyph" aria-hidden="true">⚠</span>
                  {{ p.vulnerabilitiesCount }}<span class="lag-dot-label">cve</span>
                </span>
              </span>
            </div>
            <div
              v-else-if="p.vulnerabilitiesCount > 0"
              class="row-lag row-lag-cve-only"
              :aria-label="
                t('monitoring.repos.detail.row_cve_count', { n: p.vulnerabilitiesCount })
              "
            >
              <span class="lag-legend">
                <span class="lag-count lag-cve">
                  <span class="lag-cve-glyph" aria-hidden="true">⚠</span>
                  {{ p.vulnerabilitiesCount }}<span class="lag-dot-label">cve</span>
                </span>
              </span>
            </div>
          </button>

          <div v-if="filtered.length === 0" class="empty">
            <template v-if="filtering">
              {{ t('monitoring.repos.empty_filtered', { query }) }}
            </template>
            <template v-else>
              {{ t('monitoring.repos.empty') }}
            </template>
          </div>
        </div>
      </aside>

      <section v-if="selected" :key="selected.id" class="detail-pane">
        <div class="detail-header">
          <div class="detail-identity">
            <div class="detail-crumb">
              <span class="host-icon">{{ hostGlyph(hostOf(selected.repositoryUrl)) }}</span>
              <span class="detail-slug-prefix">{{ selected.slug }}/</span>
              <span
                v-if="projectKind(selected)"
                class="kind-tag"
                :class="`kind-${projectKind(selected)}`"
              >
                {{ t(`monitoring.repos.kind.${projectKind(selected)}`) }}
              </span>
            </div>
            <h2 class="detail-name">{{ selected.name }}</h2>
            <p v-if="selected.description" class="detail-desc">{{ selected.description }}</p>
            <div class="detail-meta-line">
              <span>
                {{ t('monitoring.repos.detail.last_commit') }} ·
                {{ formatRelative(selected.lastActivityAt) }}
              </span>
              <span class="meta-sep">·</span>
              <BranchPicker
                :branches="currentBranches"
                :default-branch="selected.defaultBranch"
                :loading="currentBranchLoading"
                :model-value="currentBranch"
                @update:model-value="onBranchSelected"
              />
              <span v-if="selected.lastCommitSha" class="meta-sep">·</span>
              <span v-if="selected.lastCommitSha" class="sha-pill">
                {{ shortSha(selected.lastCommitSha) }}
              </span>
            </div>
          </div>
          <div class="version-badge" :title="t('monitoring.repos.detail.version_unknown')">—</div>
        </div>

        <div class="metrics-grid">
          <div class="metric-card">
            <div class="metric-label">{{ t('monitoring.repos.detail.metric_commits') }}</div>
            <div class="metric-value">{{ selected.commitsLast30d }}</div>
            <div
              v-if="selected.commitsLast30d > 0 && selected.commitsDailySeries.length > 0"
              class="mini-bars"
              aria-hidden="true"
            >
              <span
                v-for="(v, idx) in selected.commitsDailySeries"
                :key="idx"
                class="mini-bar"
                :style="{
                  height: `${miniBarHeight(v, Math.max(1, ...selected.commitsDailySeries))}%`,
                  opacity: miniBarOpacity(v, Math.max(1, ...selected.commitsDailySeries)),
                }"
              />
            </div>
            <div v-else class="metric-hint">
              {{ t('monitoring.repos.detail.metric_wait_sync') }}
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-label">{{ t('monitoring.repos.detail.metric_coverage') }}</div>
            <div class="metric-value">{{ formatCoverage(resolveCoverage(selected)) }}</div>
            <div
              v-if="coverageTone(resolveCoverage(selected)) !== null"
              class="coverage-bar"
              :aria-label="`coverage ${Math.round(resolveCoverage(selected) ?? 0)}%`"
            >
              <span
                class="coverage-bar-fill"
                :class="`tone-${coverageTone(resolveCoverage(selected))}`"
                :style="{ width: `${Math.round(resolveCoverage(selected) ?? 0)}%` }"
              />
            </div>
            <div v-else class="metric-hint">
              <template v-if="isApplicative(projectKind(selected))">
                {{ t('monitoring.repos.detail.metric_hint_no_run') }}
              </template>
              <template v-else>{{ t('monitoring.repos.detail.metric_wait_sync') }}</template>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-label">{{ t('monitoring.repos.detail.metric_activity') }}</div>
            <div class="metric-value">
              <span
                class="activity-dot"
                :class="`level-${activityLevel(selected.commitsLast30d)}`"
                aria-hidden="true"
              />
              {{
                t(
                  `monitoring.repos.detail.activity_level_${activityLevel(selected.commitsLast30d)}`,
                )
              }}
            </div>
            <div class="metric-hint">
              <template v-if="selected.lastActivityAt">
                {{ formatRelative(selected.lastActivityAt) }}
              </template>
              <template v-else>{{ t('monitoring.repos.detail.metric_wait_sync') }}</template>
            </div>
          </div>
        </div>

        <div class="detail-section">
          <div class="section-label">
            {{ t('monitoring.repos.detail.stacks') }}
            <template v-if="groupedParts(selected).length > 1">
              ·
              {{ t('monitoring.repos.detail.parts_count', { n: groupedParts(selected).length }) }}
            </template>
            <template v-else-if="selected.techStacks.length > 0">
              · {{ selected.techStacks.length }}
            </template>
          </div>
          <div v-if="groupedParts(selected).length > 0" class="parts-list">
            <article
              v-for="part in groupedParts(selected)"
              :key="part.role"
              class="stack-part"
              :style="{ '--part-hue': PART_META[part.role].hue }"
            >
              <span class="part-rail" aria-hidden="true" />
              <div class="part-content">
                <div class="part-header">
                  <div class="part-tag">
                    <span class="part-role-badge">
                      {{ t(`monitoring.repos.detail.stack_role.${part.role}`) }}
                    </span>
                    <span class="part-label">{{ part.primaryLabel }}</span>
                  </div>
                  <span v-if="part.primaryRuntime" class="part-runtime-inline">
                    <span class="part-runtime-caption">
                      {{ t('monitoring.repos.detail.stack_runtime') }}
                    </span>
                    <span class="part-runtime-value">{{ part.primaryRuntime }}</span>
                  </span>
                </div>
                <div class="part-chips">
                  <span
                    v-for="lang in part.languages"
                    :key="`lang-${lang}`"
                    class="part-lang-chip"
                    :style="{ '--lang-color': langColor(lang) }"
                  >
                    <span class="part-lang-dot" aria-hidden="true" />
                    {{ lang }}
                  </span>
                  <span
                    v-for="(fw, idx) in part.frameworks"
                    :key="`fw-${fw.framework}-${idx}`"
                    class="part-fw-chip"
                  >
                    {{ fw.framework }}
                    <span v-if="fw.version" class="part-fw-version">{{ fw.version }}</span>
                  </span>
                </div>
              </div>
            </article>
          </div>
          <div v-else class="section-empty">
            {{ t('monitoring.repos.detail.no_stacks') }}
          </div>
        </div>

        <div v-if="selected.runtimes.length > 0" class="detail-section">
          <div class="section-label">
            {{
              selected.runtimes.length > 1
                ? t('monitoring.repos.detail.runtimes_section_multi')
                : t('monitoring.repos.detail.runtimes_section')
            }}
          </div>
          <div class="runtimes-list">
            <div
              v-for="rt in selected.runtimes"
              :key="rt.productKey"
              class="runtime-wrap"
              :style="{ '--part-hue': PART_META[runtimeRole(rt, selected)].hue }"
            >
              <span class="part-tag runtime-part-tag">
                <span class="part-role-badge">
                  {{ t(`monitoring.repos.detail.stack_role.${runtimeRole(rt, selected)}`) }}
                </span>
                <span class="part-label">{{ runtimeLabel(rt, selected) }}</span>
              </span>
              <article class="runtime-card">
                <div class="runtime-head">
                  <div class="runtime-identity">
                    <span class="runtime-eyebrow">
                      {{ t('monitoring.repos.detail.stack_runtime') }}
                    </span>
                    <span class="runtime-name">{{ rt.name }}</span>
                  </div>
                  <span
                    class="runtime-badge"
                    :class="`status-${runtimeStatus(rt)}`"
                    :title="t(`monitoring.repos.detail.runtime_status.${runtimeStatus(rt)}`)"
                  >
                    <span class="runtime-badge-dot" aria-hidden="true" />
                    {{ t(`monitoring.repos.detail.runtime_status.${runtimeStatus(rt)}`) }}
                  </span>
                </div>
                <div class="runtime-timeline">
                  <div class="runtime-track" aria-hidden="true" />
                  <div
                    v-for="marker in runtimeMarkers(rt)"
                    :key="marker.value"
                    class="runtime-marker"
                    :style="{ left: `${marker.position}%`, color: marker.color }"
                  >
                    <span class="runtime-marker-dot" />
                    <div class="runtime-marker-caption" :class="`level-${marker.level}`">
                      <span class="runtime-marker-value">{{ marker.value }}</span>
                      <span class="runtime-marker-label">
                        {{
                          marker.keys
                            .map((k) => t(`monitoring.repos.detail.runtime_label_${k}`))
                            .join(' · ')
                        }}
                      </span>
                    </div>
                  </div>
                </div>
                <div v-if="rt.latestVersion === null" class="runtime-hint">
                  {{ t('monitoring.repos.detail.runtime_wait_sync') }}
                </div>
              </article>
            </div>
          </div>
        </div>

        <div class="detail-section">
          <ProjectDependenciesPanel :project="selected" />
        </div>

        <div v-if="selected.coverageJobs.length > 0" class="detail-section">
          <div class="section-label">
            {{ t('monitoring.repos.detail.coverage_section_jobs') }}
            <span class="section-label-count">· {{ selected.coverageJobs.length }}</span>
          </div>
          <CoverageBreakdown :jobs="selected.coverageJobs" />
        </div>

        <div class="detail-section">
          <div class="section-label">
            {{ t('monitoring.repos.detail.activity_section') }}
          </div>
          <ActivityChart :series="selected.commitsLast30d > 0 ? selected.commitsDailySeries : []" />
        </div>
      </section>

      <section v-else class="detail-pane empty-detail">
        <div class="empty-detail-text">{{ t('monitoring.repos.no_selection') }}</div>
      </section>
    </div>
  </div>
</template>

<style scoped>
.repos {
  display: flex;
  flex-direction: column;
  gap: 20px;
  font-family: var(--hub-font-sans);
}

.loading {
  padding: 48px 0;
  text-align: center;
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-2);
}

.split {
  display: grid;
  grid-template-columns: minmax(340px, 400px) 1fr;
  gap: clamp(24px, 2.5vw, 48px);
  align-items: start;
}
@media (max-width: 1100px) {
  .split {
    grid-template-columns: 1fr;
  }
}
.list-pane {
  position: sticky;
  top: calc(var(--hub-topbar-h, 64px) + 16px);
  max-height: calc(100vh - var(--hub-topbar-h, 64px) - 48px);
  max-height: calc(100svh - var(--hub-topbar-h, 64px) - 48px);
  overflow-y: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.list-pane::-webkit-scrollbar {
  display: none;
}
@media (max-width: 1100px) {
  .list-pane {
    position: static;
    overflow-y: visible;
    max-height: none;
  }
}

/* TOOLBAR */
.toolbar {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-bottom: 10px;
  margin-bottom: 14px;
  border-bottom: 1px solid var(--hub-line);
}
.toolbar-row {
  display: flex;
  align-items: center;
  gap: 10px;
}
.toolbar-row.segs {
  flex-wrap: wrap;
}
.count-label {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-3);
  letter-spacing: 0.12em;
  white-space: nowrap;
}
.search {
  flex: 1;
  position: relative;
  display: flex;
  align-items: center;
}
.search-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  font-family: var(--hub-font-mono);
  font-size: 12px;
  color: var(--hub-fg-3);
  pointer-events: none;
}
.search input {
  flex: 1;
  padding: 7px 28px 7px 28px;
  border-radius: 8px;
  border: 1px solid var(--hub-line);
  background: color-mix(in oklab, var(--hub-bg-1) 60%, transparent);
  color: var(--hub-fg-0);
  font-family: var(--hub-font-mono);
  font-size: 11px;
  outline: none;
  transition:
    border-color 160ms var(--hub-ease),
    background 160ms var(--hub-ease);
}
.search input:focus {
  border-color: var(--hub-accent);
  background: var(--hub-bg-2);
}
.search input::placeholder {
  color: var(--hub-fg-3);
}
.search-clear {
  position: absolute;
  right: 6px;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  border-radius: 4px;
  border: none;
  background: transparent;
  color: var(--hub-fg-3);
  cursor: pointer;
  font-family: var(--hub-font-mono);
  font-size: 13px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 150ms var(--hub-ease);
}
.search-clear:hover {
  background: var(--hub-bg-3);
  color: var(--hub-fg-0);
}

.seg-group {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px;
  border: 1px solid var(--hub-line);
  border-radius: 8px;
}
.seg {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 4px 10px;
  border-radius: 6px;
  border: none;
  background: transparent;
  color: var(--hub-fg-2);
  cursor: pointer;
  transition: all 200ms var(--hub-ease);
}
.seg:hover:not(.active) {
  color: var(--hub-fg-1);
}
.seg.active {
  color: var(--hub-bg-0);
  background: var(--hub-fg-0);
}

/* ROWS */
.rows {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.row {
  position: relative;
  text-align: left;
  padding: 14px 16px;
  border-radius: 10px;
  border: 1px solid var(--hub-line);
  background: color-mix(in oklab, var(--hub-bg-1) 60%, transparent);
  color: var(--hub-fg-1);
  font: inherit;
  display: flex;
  flex-direction: column;
  gap: 10px;
  cursor: pointer;
  animation: row-in 500ms var(--hub-ease) both;
  animation-delay: calc(var(--i, 0) * 40ms);
  transition:
    background 180ms var(--hub-ease),
    border-color 180ms var(--hub-ease);
}
@keyframes row-in {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.row:hover {
  border-color: var(--hub-line-strong);
  background: color-mix(in oklab, var(--hub-bg-2) 90%, transparent);
}
.row.active {
  border-color: var(--hub-line-strong);
  background: var(--hub-bg-2);
}
.row-bar {
  position: absolute;
  left: 0;
  top: 14px;
  bottom: 14px;
  width: 2px;
  background: var(--hub-accent);
  border-radius: 2px;
}
.row-top {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 12px;
}
.row-lead {
  display: flex;
  align-items: baseline;
  gap: 8px;
  min-width: 0;
}
.host-icon {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  padding: 2px 5px;
  border-radius: 4px;
  background: var(--hub-bg-3);
  color: var(--hub-fg-2);
  letter-spacing: 0.06em;
}
.row-name {
  font-family: var(--hub-font-mono);
  font-size: 13px;
  color: var(--hub-fg-0);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.row-branch {
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-2);
  white-space: nowrap;
}

.row-metrics {
  display: grid;
  grid-template-columns: 1fr 80px 56px;
  align-items: center;
  gap: 10px;
}
.lang-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-2);
  min-width: 0;
  overflow: hidden;
}
.lang-dots {
  display: inline-flex;
  align-items: center;
}
.lang-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  border: 1px solid var(--hub-bg-1);
}
.lang-dot.empty {
  background: color-mix(in oklab, var(--hub-fg-3) 40%, transparent);
  box-shadow: none;
}
.row-fallback {
  display: inline-flex;
  align-items: baseline;
  gap: 6px;
  min-width: 0;
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-2);
  overflow: hidden;
  white-space: nowrap;
}
.row-fallback.muted {
  color: var(--hub-fg-3);
}
.row-fallback-label {
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--hub-fg-3);
  font-size: 9px;
}
.row-fallback-value {
  color: var(--hub-fg-1);
}

.row-lag {
  display: flex;
  align-items: center;
  gap: 10px;
}
.lag-bar {
  flex: 1 1 auto;
  min-width: 0;
  display: flex;
  height: 4px;
  border-radius: 2px;
  overflow: hidden;
  background: color-mix(in oklab, var(--hub-line) 60%, transparent);
}
.lag-seg {
  display: block;
  height: 100%;
  min-width: 2px;
  transition: flex-grow 240ms var(--hub-ease);
}
.lag-seg.lag-major {
  background: #ff5c5c;
}
.lag-seg.lag-minor {
  background: #ffb547;
}
.lag-seg.lag-patch {
  background: #ffe066;
}
.lag-seg.lag-ok {
  background: #4ade80;
}
.lag-legend {
  display: flex;
  flex: 0 0 120px;
  justify-content: flex-end;
  align-items: center;
  gap: 6px;
  font-family: var(--hub-font-mono);
  font-size: 9px;
  letter-spacing: 0.08em;
  color: var(--hub-fg-3);
  white-space: nowrap;
}
.lag-count {
  display: inline-flex;
  align-items: baseline;
  gap: 2px;
}
.lag-count.lag-major {
  color: #ff8080;
}
.lag-count.lag-minor {
  color: #ffc87a;
}
.lag-count.lag-patch {
  color: #ffe680;
}
.lag-count.lag-ok {
  color: color-mix(in oklab, #4ade80 85%, white);
  text-transform: uppercase;
  letter-spacing: 0.12em;
}
.lag-count.lag-cve {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 1px 6px;
  border-radius: 999px;
  border: 1px solid #ff5c5c;
  background: color-mix(in oklab, #ff5c5c 18%, transparent);
  color: #ff8080;
}
.lag-cve-glyph {
  font-size: 9px;
}
.row-lag-cve-only {
  justify-content: flex-end;
}
.row-lag-cve-only .lag-legend {
  flex: 0 0 auto;
}
.lag-dot-label {
  margin-left: 2px;
  color: inherit;
  opacity: 0.7;
}
.lang-labels {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.spark {
  display: block;
  justify-self: center;
}
.coverage-empty {
  justify-self: end;
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-3);
}
.coverage-empty.has-value {
  color: var(--hub-fg-1);
}

.kind-tag {
  display: inline-flex;
  align-items: center;
  padding: 1px 6px;
  border-radius: 3px;
  border: 1px solid;
  font-family: var(--hub-font-mono);
  font-size: 8.5px;
  letter-spacing: 0.12em;
  font-weight: 500;
  text-transform: uppercase;
  white-space: nowrap;
}
.kind-tag.kind-frontend {
  color: #3178c6;
  border-color: color-mix(in oklab, #3178c6 45%, transparent);
  background: color-mix(in oklab, #3178c6 12%, transparent);
}
.kind-tag.kind-backend {
  color: #777bb4;
  border-color: color-mix(in oklab, #777bb4 45%, transparent);
  background: color-mix(in oklab, #777bb4 12%, transparent);
}
.kind-tag.kind-fullstack {
  color: var(--hub-accent);
  border-color: color-mix(in oklab, var(--hub-accent) 45%, transparent);
  background: color-mix(in oklab, var(--hub-accent) 12%, transparent);
}
.kind-tag.kind-config {
  color: var(--hub-fg-3);
  border-color: var(--hub-line);
  background: color-mix(in oklab, var(--hub-bg-3) 50%, transparent);
}

.empty {
  padding: 24px 16px;
  text-align: center;
  border: 1px dashed var(--hub-line);
  border-radius: 10px;
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-3);
}

/* DETAIL PANE */
.detail-pane {
  position: sticky;
  top: calc(var(--hub-topbar-h, 64px) + 16px);
  padding: 28px;
  border-radius: 16px;
  border: 1px solid var(--hub-line);
  background: color-mix(in oklab, var(--hub-bg-2) 80%, transparent);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  animation: detail-in 450ms var(--hub-ease);
  max-height: calc(100vh - var(--hub-topbar-h, 64px) - 48px);
  max-height: calc(100svh - var(--hub-topbar-h, 64px) - 48px);
  overflow-y: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.detail-pane::-webkit-scrollbar {
  display: none;
}
@media (max-width: 1100px) {
  .detail-pane {
    position: static;
    max-height: none;
    overflow-y: visible;
  }
}
@keyframes detail-in {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: none;
  }
}
.empty-detail {
  display: grid;
  place-items: center;
  min-height: 240px;
}
.empty-detail-text {
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-3);
  letter-spacing: 0.06em;
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
}
.detail-identity {
  min-width: 0;
  flex: 1;
}
.detail-crumb {
  display: flex;
  align-items: center;
  gap: 10px;
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-2);
}
.detail-crumb .host-icon {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  padding: 2px 5px;
  border-radius: 4px;
  background: var(--hub-bg-3);
  color: var(--hub-fg-2);
  letter-spacing: 0.06em;
}
.detail-slug-prefix {
  color: var(--hub-fg-2);
}
.detail-name {
  margin: 8px 0 0;
  font-family: var(--hub-font-mono);
  font-weight: 500;
  font-size: 30px;
  line-height: 1.1;
  letter-spacing: -0.01em;
  color: var(--hub-fg-0);
  overflow: hidden;
  text-overflow: ellipsis;
}
.detail-desc {
  margin: 8px 0 0;
  color: var(--hub-fg-1);
  font-size: 13px;
  line-height: 1.5;
}
.detail-meta-line {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 10px;
  font-family: var(--hub-font-mono);
  font-size: 12px;
  color: var(--hub-fg-2);
  flex-wrap: wrap;
}
.meta-sep {
  color: var(--hub-fg-3);
}
.branch-pill {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  border-radius: 999px;
  border: 1px solid var(--hub-line);
  background: color-mix(in oklab, var(--hub-bg-1) 80%, transparent);
  color: var(--hub-fg-1);
  font-size: 11px;
}
.version-badge {
  padding: 6px 10px;
  border-radius: 6px;
  border: 1px dashed var(--hub-line-strong);
  background: transparent;
  color: var(--hub-fg-3);
  font-family: var(--hub-font-mono);
  font-size: 13px;
  letter-spacing: 0.04em;
  flex-shrink: 0;
}

/* Metric cards (3) */
.metrics-grid {
  margin-top: 28px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
}
@media (max-width: 780px) {
  .metrics-grid {
    grid-template-columns: 1fr;
  }
}
.metric-card {
  padding: 14px 16px;
  border-radius: 12px;
  border: 1px solid var(--hub-line);
  background: color-mix(in oklab, var(--hub-bg-1) 70%, transparent);
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-height: 88px;
}
.metric-label {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--hub-fg-3);
}
.metric-value {
  display: flex;
  align-items: baseline;
  gap: 8px;
  font-family: var(--hub-font-serif);
  font-style: italic;
  font-weight: 400;
  font-size: 28px;
  line-height: 1;
  color: var(--hub-fg-0);
}
.metric-hint {
  margin-top: auto;
  font-family: var(--hub-font-mono);
  font-size: 9px;
  letter-spacing: 0.06em;
  color: var(--hub-fg-3);
}

.mini-bars {
  display: flex;
  align-items: flex-end;
  gap: 2px;
  margin-top: auto;
  height: 18px;
}
.mini-bar {
  flex: 1;
  min-height: 1px;
  border-radius: 1px;
  background: var(--hub-accent);
  transition: height 240ms var(--hub-ease);
}

.coverage-bar {
  margin-top: auto;
  height: 3px;
  border-radius: 2px;
  background: color-mix(in oklab, var(--hub-line-strong) 80%, transparent);
  overflow: hidden;
}
.coverage-bar-fill {
  display: block;
  height: 100%;
  border-radius: 2px;
  transition: width 320ms var(--hub-ease);
}
.coverage-bar-fill.tone-good {
  background: #4ade80;
}
.coverage-bar-fill.tone-warn {
  background: #ffb547;
}
.coverage-bar-fill.tone-bad {
  background: #ff5c5c;
}

.activity-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
  align-self: center;
  box-shadow: 0 0 8px currentColor;
  color: var(--hub-fg-3);
  background: currentColor;
}
.activity-dot.level-idle {
  color: color-mix(in oklab, var(--hub-fg-3) 60%, transparent);
  box-shadow: none;
}
.activity-dot.level-low {
  color: #ffe066;
}
.activity-dot.level-medium {
  color: #ffb547;
}
.activity-dot.level-high {
  color: #4ade80;
}
.activity-dot.level-very_high {
  color: var(--hub-accent);
}

/* Sections with dashed label */
.detail-section {
  margin-top: 24px;
}
.section-label {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--hub-fg-3);
  padding-bottom: 6px;
  border-bottom: 1px dashed var(--hub-line);
  margin-bottom: 10px;
}

.parts-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.stack-part {
  position: relative;
  padding: 14px 14px 14px 20px;
  border-radius: 12px;
  border: 1px solid var(--hub-line);
  background: color-mix(in oklab, var(--hub-bg-1) 70%, transparent);
  overflow: hidden;
}
.part-rail {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  background: linear-gradient(
    180deg,
    hsla(var(--part-hue), 70%, 60%, 0.85),
    hsla(var(--part-hue), 70%, 60%, 0.2)
  );
}
.part-content {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.part-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  flex-wrap: wrap;
}
.part-tag {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 4px 10px 4px 4px;
  border-radius: 999px;
  border: 1px solid var(--hub-line-strong);
  background: var(--hub-bg-2);
}
.part-role-badge {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  padding: 3px 8px;
  border-radius: 999px;
  color: hsl(var(--part-hue), 70%, 80%);
  background: hsla(var(--part-hue), 60%, 55%, 0.18);
  border: 1px solid hsla(var(--part-hue), 60%, 55%, 0.3);
}
.part-label {
  font-family: var(--hub-font-sans);
  font-size: 12px;
  color: var(--hub-fg-1);
}
.part-runtime-inline {
  display: inline-flex;
  align-items: baseline;
  gap: 6px;
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-2);
}
.part-runtime-caption {
  color: var(--hub-fg-3);
  font-size: 9px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}
.part-runtime-value {
  color: var(--hub-fg-0);
}
.part-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}
.part-lang-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 9px;
  border-radius: 6px;
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: color-mix(in oklab, var(--lang-color) 80%, white 20%);
  border: 1px solid color-mix(in oklab, var(--lang-color) 40%, transparent);
  background: color-mix(in oklab, var(--lang-color) 12%, transparent);
}
.part-lang-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--lang-color);
}
.part-fw-chip {
  display: inline-flex;
  align-items: baseline;
  gap: 6px;
  padding: 5px 9px;
  border-radius: 6px;
  border: 1px solid var(--hub-line-strong);
  background: var(--hub-bg-3);
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-1);
}
.part-fw-version {
  color: var(--hub-fg-3);
  font-size: 10px;
  letter-spacing: 0.04em;
}

.runtimes-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.runtime-wrap {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: flex-start;
}
.runtime-part-tag {
  margin-left: 8px;
}
.runtime-card {
  width: 100%;
  padding: 14px 16px 22px;
  border-radius: 12px;
  border: 1px solid var(--hub-line);
  background: color-mix(in oklab, var(--hub-bg-1) 70%, transparent);
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.runtime-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}
.runtime-identity {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.runtime-eyebrow {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--hub-fg-3);
}
.runtime-name {
  font-family: var(--hub-font-sans);
  font-size: 18px;
  font-weight: 500;
  color: var(--hub-fg-0);
}
.runtime-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 8px;
  border-radius: 999px;
  font-family: var(--hub-font-mono);
  font-size: 9px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  border: 1px solid currentColor;
  background: color-mix(in oklab, currentColor 12%, transparent);
}
.runtime-badge-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: currentColor;
}
.runtime-badge.status-lts {
  color: #4ade80;
}
.runtime-badge.status-behind_lts {
  color: #ffb547;
}
.runtime-badge.status-unknown {
  color: var(--hub-fg-3);
}

.runtime-timeline {
  position: relative;
  padding-top: 38px;
  padding-bottom: 38px;
}
.runtime-track {
  position: relative;
  height: 3px;
  border-radius: 2px;
  background: linear-gradient(90deg, var(--hub-line-strong), var(--hub-accent));
}
.runtime-marker {
  position: absolute;
  top: 50%;
  transform: translate(-50%, -50%);
  width: 10px;
  height: 10px;
}
.runtime-marker-dot {
  display: block;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: currentColor;
  box-shadow: 0 0 0 3px color-mix(in oklab, currentColor 20%, transparent);
}
.runtime-marker-caption {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  pointer-events: none;
}
.runtime-marker-caption.level-bottom {
  top: calc(100% + 8px);
}
.runtime-marker-caption.level-top {
  bottom: calc(100% + 8px);
  flex-direction: column-reverse;
}
.runtime-marker-value {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-1);
  white-space: nowrap;
}
.runtime-marker-label {
  font-family: var(--hub-font-mono);
  font-size: 8px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--hub-fg-3);
  white-space: nowrap;
}
.runtime-hint {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  letter-spacing: 0.08em;
  color: var(--hub-fg-3);
  text-align: center;
}
.sha-pill {
  display: inline-flex;
  align-items: center;
  padding: 2px 6px;
  border-radius: 4px;
  background: color-mix(in oklab, var(--hub-bg-3) 80%, transparent);
  color: var(--hub-fg-2);
  font-family: var(--hub-font-mono);
  font-size: 10px;
  letter-spacing: 0.04em;
}
.section-empty {
  padding: 18px;
  text-align: center;
  border: 1px dashed var(--hub-line);
  border-radius: 10px;
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-3);
  letter-spacing: 0.06em;
}
</style>
