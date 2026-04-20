<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import type { Project } from '@/apps/monitoring/catalog/types/project';
import type { Dependency, PackageManager } from '@/apps/monitoring/dependency/types/dependency';
import type { Vulnerability } from '@/apps/monitoring/dependency/types/vulnerability';

import { dependencyService } from '@/apps/monitoring/dependency/services/dependency.service';
import { vulnerabilityService } from '@/apps/monitoring/dependency/services/vulnerability.service';

type Drift = 'major' | 'minor' | 'ok' | 'patch';
type PartRole = 'back' | 'front' | 'infra' | 'other';
type PmTab = 'all' | PackageManager;
type SortKey = 'drift' | 'name';
type TypeFilter = 'all' | 'dev' | 'outdated' | 'runtime';

const props = defineProps<{ project: Project }>();
const { t } = useI18n();

const PM_ROLE: Record<PackageManager, PartRole> = {
  composer: 'back',
  npm: 'front',
  pip: 'back',
};
const ROLE_HUE: Record<PartRole, number> = {
  back: 200,
  front: 320,
  infra: 55,
  other: 260,
};
const DRIFT_RANK: Record<Drift, number> = { major: 0, minor: 1, ok: 3, patch: 2 };
const PAGE_SIZES = [10, 25, 50, 100];

const deps = ref<Dependency[]>([]);
const loading = ref(false);
const pmTab = ref<PmTab>('all');
const typeFilter = ref<TypeFilter>('all');
const search = ref('');
const sortKey = ref<SortKey>('drift');
const page = ref(1);
const pageSize = ref(25);

const expandedDepId = ref<null | string>(null);
const vulnsByDep = ref<Record<string, Vulnerability[]>>({});
const vulnsLoading = ref<Record<string, boolean>>({});

function cveLink(cveId: string): string {
  const id = cveId.trim();
  if (/^CVE-/i.test(id)) return `https://nvd.nist.gov/vuln/detail/${encodeURIComponent(id)}`;
  if (/^GHSA-/i.test(id)) return `https://github.com/advisories/${encodeURIComponent(id)}`;
  return `https://osv.dev/vulnerability/${encodeURIComponent(id)}`;
}

async function toggleExpand(dep: Dependency): Promise<void> {
  if (dep.vulnerabilityCount === 0) return;
  if (expandedDepId.value === dep.id) {
    expandedDepId.value = null;
    return;
  }
  expandedDepId.value = dep.id;
  if (vulnsByDep.value[dep.id] !== undefined) return;
  vulnsLoading.value[dep.id] = true;
  try {
    const resp = await vulnerabilityService.listByDependency(dep.id);
    vulnsByDep.value[dep.id] = resp.data.items;
  } finally {
    vulnsLoading.value[dep.id] = false;
  }
}

watch(
  () => props.project.id,
  async () => {
    page.value = 1;
    pmTab.value = 'all';
    typeFilter.value = 'all';
    search.value = '';
    expandedDepId.value = null;
    vulnsByDep.value = {};
    vulnsLoading.value = {};
    if (props.project.dependenciesCount === 0) {
      deps.value = [];
      return;
    }
    loading.value = true;
    try {
      const resp = await dependencyService.list(1, 500, props.project.id);
      deps.value = resp.data.items;
    } finally {
      loading.value = false;
    }
  },
  { immediate: true },
);

function driftOf(installed: string, latest: string): Drift {
  if (!installed || !latest) return 'ok';
  const pa = installed.split('.').map((n) => parseInt(n, 10) || 0);
  const pb = latest.split('.').map((n) => parseInt(n, 10) || 0);
  if ((pa[0] ?? 0) !== (pb[0] ?? 0)) return 'major';
  if ((pa[1] ?? 0) !== (pb[1] ?? 0)) return 'minor';
  if ((pa[2] ?? 0) !== (pb[2] ?? 0)) return 'patch';
  return 'ok';
}

const pmTabs = computed(() => {
  const counts = new Map<PackageManager, { drift: number; total: number }>();
  for (const d of deps.value) {
    const c = counts.get(d.packageManager) ?? { drift: 0, total: 0 };
    c.total++;
    if (d.isOutdated) c.drift++;
    counts.set(d.packageManager, c);
  }
  return [...counts.entries()].map(([pm, c]) => ({
    hue: ROLE_HUE[PM_ROLE[pm] ?? 'other'],
    pm,
    role: PM_ROLE[pm] ?? 'other',
    ...c,
  }));
});

const totals = computed(() => ({
  all: deps.value.length,
  dev: deps.value.filter((d) => d.type === 'dev').length,
  outdated: deps.value.filter((d) => d.isOutdated).length,
  prod: deps.value.filter((d) => d.type === 'runtime').length,
}));

const filtered = computed<Dependency[]>(() => {
  let list = deps.value;
  if (pmTab.value !== 'all') list = list.filter((d) => d.packageManager === pmTab.value);
  if (typeFilter.value === 'runtime') list = list.filter((d) => d.type === 'runtime');
  if (typeFilter.value === 'dev') list = list.filter((d) => d.type === 'dev');
  if (typeFilter.value === 'outdated') list = list.filter((d) => d.isOutdated);
  if (search.value.trim()) {
    const q = search.value.trim().toLowerCase();
    list = list.filter((d) => d.name.toLowerCase().includes(q));
  }
  const copy = [...list];
  if (sortKey.value === 'name') copy.sort((a, b) => a.name.localeCompare(b.name));
  if (sortKey.value === 'drift') {
    copy.sort(
      (a, b) =>
        DRIFT_RANK[driftOf(a.currentVersion, a.latestVersion)] -
        DRIFT_RANK[driftOf(b.currentVersion, b.latestVersion)],
    );
  }
  return copy;
});

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / pageSize.value)));
const safePage = computed(() => Math.min(page.value, totalPages.value));
const startIdx = computed(() => (safePage.value - 1) * pageSize.value);
const endIdx = computed(() => Math.min(startIdx.value + pageSize.value, filtered.value.length));
const visible = computed(() => filtered.value.slice(startIdx.value, endIdx.value));

watch([pmTab, typeFilter, search, sortKey, pageSize], () => {
  page.value = 1;
});

function condensedPages(current: number, total: number): ('…' | number)[] {
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
  const s = new Set([1, 2, current, current + 1, current - 1, total, total - 1]);
  const arr = [...s].filter((n) => n >= 1 && n <= total).sort((a, b) => a - b);
  const out: ('…' | number)[] = [];
  for (let i = 0; i < arr.length; i++) {
    if (i > 0 && arr[i] - arr[i - 1] > 1) out.push('…');
    out.push(arr[i]);
  }
  return out;
}

function shortVersion(v: null | string): string {
  if (!v) return '—';
  return v.length > 20 ? `${v.slice(0, 18)}…` : v;
}
</script>

<template>
  <div class="deps-panel">
    <div class="deps-header">
      <div class="section-label">
        {{ t('monitoring.repos.detail.deps_section') }}
        <template v-if="project.dependenciesCount > 0">
          · {{ t('monitoring.repos.detail.packages_total', { n: project.dependenciesCount }) }}
        </template>
      </div>
      <div v-if="project.vulnerabilitiesCount > 0" class="cve-chips">
        <span
          v-if="project.vulnerabilitiesBySeverity.critical > 0"
          class="cve-chip severity-critical"
        >
          {{ project.vulnerabilitiesBySeverity.critical }}
          <span class="cve-chip-label">
            {{ t('monitoring.repos.detail.severity_critical') }}
          </span>
        </span>
        <span v-if="project.vulnerabilitiesBySeverity.high > 0" class="cve-chip severity-high">
          {{ project.vulnerabilitiesBySeverity.high }}
          <span class="cve-chip-label">
            {{ t('monitoring.repos.detail.severity_high') }}
          </span>
        </span>
        <span v-if="project.vulnerabilitiesBySeverity.medium > 0" class="cve-chip severity-medium">
          {{ project.vulnerabilitiesBySeverity.medium }}
          <span class="cve-chip-label">
            {{ t('monitoring.repos.detail.severity_medium') }}
          </span>
        </span>
        <span v-if="project.vulnerabilitiesBySeverity.low > 0" class="cve-chip severity-low">
          {{ project.vulnerabilitiesBySeverity.low }}
          <span class="cve-chip-label">
            {{ t('monitoring.repos.detail.severity_low') }}
          </span>
        </span>
      </div>
    </div>

    <div v-if="project.dependenciesCount === 0" class="section-empty">
      {{ t('monitoring.repos.detail.no_deps') }}
    </div>

    <template v-else>
      <div v-if="pmTabs.length > 1" class="pm-tabs">
        <button
          type="button"
          class="pm-tab"
          :class="{ active: pmTab === 'all' }"
          @click="pmTab = 'all'"
        >
          <span class="pm-tab-label">
            {{ t('monitoring.repos.detail.deps_tab_all') }}
          </span>
          <span class="pm-tab-count">{{ totals.all }}</span>
        </button>
        <button
          v-for="tab in pmTabs"
          :key="tab.pm"
          type="button"
          class="pm-tab"
          :class="{ active: pmTab === tab.pm }"
          :style="{ '--pm-hue': tab.hue }"
          @click="pmTab = tab.pm"
        >
          <span class="pm-tab-dot" aria-hidden="true" />
          <span class="pm-tab-role">
            {{ t(`monitoring.repos.detail.stack_role.${tab.role}`) }}
          </span>
          <span class="pm-tab-label">{{ tab.pm }}</span>
          <span class="pm-tab-count">
            {{ tab.total }}<span v-if="tab.drift > 0" class="pm-tab-drift">·{{ tab.drift }}▲</span>
          </span>
        </button>
      </div>

      <div class="deps-toolbar">
        <div class="seg-group" role="radiogroup">
          <button
            v-for="f in [
              { key: 'all', count: totals.all },
              { key: 'runtime', count: totals.prod },
              { key: 'dev', count: totals.dev },
              { key: 'outdated', count: totals.outdated },
            ]"
            :key="f.key"
            type="button"
            class="seg"
            :class="{ active: typeFilter === f.key }"
            @click="typeFilter = f.key as TypeFilter"
          >
            {{ t(`monitoring.repos.detail.deps_filter_${f.key}`) }} · {{ f.count }}
          </button>
        </div>
        <div class="deps-search">
          <span class="search-icon" aria-hidden="true">⌕</span>
          <input
            v-model="search"
            type="search"
            :placeholder="t('monitoring.repos.detail.deps_search_placeholder')"
            :aria-label="t('monitoring.repos.detail.deps_search_placeholder')"
          />
          <button
            v-if="search"
            type="button"
            class="search-clear"
            :aria-label="t('common.actions.clear')"
            @click="search = ''"
          >
            ×
          </button>
        </div>
        <div class="seg-group" role="radiogroup">
          <button
            type="button"
            class="seg"
            :class="{ active: sortKey === 'drift' }"
            @click="sortKey = 'drift'"
          >
            {{ t('monitoring.repos.detail.deps_sort_drift') }}
          </button>
          <button
            type="button"
            class="seg"
            :class="{ active: sortKey === 'name' }"
            @click="sortKey = 'name'"
          >
            {{ t('monitoring.repos.detail.deps_sort_name') }}
          </button>
        </div>
      </div>

      <div v-if="loading" class="deps-loading">
        {{ t('common.actions.loading') }}
      </div>

      <div v-else class="deps-table">
        <div class="deps-table-head">
          <span>{{ t('monitoring.repos.detail.col_package') }}</span>
          <span>{{ t('monitoring.repos.detail.col_installed') }}</span>
          <span>{{ t('monitoring.repos.detail.col_latest') }}</span>
          <span>{{ t('monitoring.repos.detail.col_status') }}</span>
          <span class="text-right">{{ t('monitoring.repos.detail.col_type') }}</span>
        </div>

        <template v-for="d in visible" :key="d.id">
          <!-- eslint-disable-next-line vuejs-accessibility/no-static-element-interactions -->
          <div
            class="deps-row"
            :class="[
              `drift-${driftOf(d.currentVersion, d.latestVersion)}`,
              {
                'has-cve': d.vulnerabilityCount > 0,
                expanded: expandedDepId === d.id,
                clickable: d.vulnerabilityCount > 0,
              },
            ]"
            :role="d.vulnerabilityCount > 0 ? 'button' : undefined"
            :tabindex="d.vulnerabilityCount > 0 ? 0 : -1"
            :aria-expanded="d.vulnerabilityCount > 0 ? expandedDepId === d.id : undefined"
            @click="toggleExpand(d)"
            @keydown.enter.prevent="toggleExpand(d)"
            @keydown.space.prevent="toggleExpand(d)"
          >
            <span class="dep-name">
              <span class="dep-pm">{{ d.packageManager }}</span>
              {{ d.name }}
              <span v-if="d.vulnerabilityCount > 0" class="dep-cve-flag">
                <span class="cve-flag-icon" aria-hidden="true">⚠</span>
                <span class="cve-flag-count">{{ d.vulnerabilityCount }}</span>
                <span class="cve-flag-label"> CVE{{ d.vulnerabilityCount > 1 ? 's' : '' }} </span>
                <span
                  class="cve-flag-caret"
                  :class="{ open: expandedDepId === d.id }"
                  aria-hidden="true"
                >
                  ▾
                </span>
              </span>
            </span>
            <span class="dep-version installed">{{ shortVersion(d.currentVersion) }}</span>
            <span class="dep-version latest">{{ shortVersion(d.latestVersion) }}</span>
            <span class="dep-status">
              <span class="status-dot" aria-hidden="true" />
              {{ t(`monitoring.repos.detail.drift_${driftOf(d.currentVersion, d.latestVersion)}`) }}
            </span>
            <span class="dep-type text-right">
              {{ t(`monitoring.repos.detail.type_${d.type}`) }}
            </span>
          </div>

          <div v-if="expandedDepId === d.id" class="dep-cve-panel">
            <div v-if="vulnsLoading[d.id]" class="cve-loading">
              {{ t('common.actions.loading') }}
            </div>
            <ul v-else-if="(vulnsByDep[d.id] ?? []).length > 0" class="cve-list">
              <li
                v-for="v in vulnsByDep[d.id]"
                :key="v.id"
                class="cve-item"
                :class="`severity-${v.severity}`"
              >
                <div class="cve-head">
                  <a
                    :href="cveLink(v.cveId)"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="cve-id"
                  >
                    {{ v.cveId }}
                    <span class="cve-link-glyph" aria-hidden="true">↗</span>
                  </a>
                  <span class="cve-severity">
                    <span class="cve-severity-dot" aria-hidden="true" />
                    {{ t(`monitoring.repos.detail.severity_${v.severity}`) }}
                  </span>
                  <span v-if="v.status" class="cve-status" :class="`status-${v.status}`">
                    {{ v.status }}
                  </span>
                  <span v-if="v.patchedVersion" class="cve-patch">
                    {{ t('monitoring.repos.detail.cve_patched_in') }} {{ v.patchedVersion }}
                  </span>
                </div>
                <div v-if="v.title" class="cve-title">{{ v.title }}</div>
                <div v-if="v.description" class="cve-description">{{ v.description }}</div>
              </li>
            </ul>
            <div v-else class="cve-empty">
              {{ t('monitoring.repos.detail.cve_empty') }}
            </div>
          </div>
        </template>

        <div v-if="visible.length === 0" class="deps-empty">
          <template v-if="search">
            {{ t('monitoring.repos.detail.deps_empty_search', { query: search }) }}
          </template>
          <template v-else>
            {{ t('monitoring.repos.detail.deps_empty_filter') }}
          </template>
        </div>
      </div>

      <div v-if="filtered.length > 0" class="deps-pagination">
        <span class="deps-range">
          {{ startIdx + 1 }}–{{ endIdx }}
          <span class="muted">{{ t('monitoring.repos.detail.pagination_of') }}</span>
          <span class="fg-strong">{{ filtered.length }}</span>
        </span>
        <div class="deps-pagination-controls">
          <div class="seg-group small">
            <button
              v-for="s in PAGE_SIZES"
              :key="s"
              type="button"
              class="seg"
              :class="{ active: pageSize === s }"
              @click="pageSize = s"
            >
              {{ s }}
            </button>
          </div>
          <div class="page-buttons">
            <button
              type="button"
              class="page-btn"
              :disabled="safePage === 1"
              @click="page = Math.max(1, safePage - 1)"
            >
              ←
            </button>
            <template v-for="(p, i) in condensedPages(safePage, totalPages)" :key="`p-${i}`">
              <span v-if="p === '…'" class="page-ellipsis">…</span>
              <button
                v-else
                type="button"
                class="page-btn"
                :class="{ active: p === safePage }"
                @click="page = p"
              >
                {{ p }}
              </button>
            </template>
            <button
              type="button"
              class="page-btn"
              :disabled="safePage === totalPages"
              @click="page = Math.min(totalPages, safePage + 1)"
            >
              →
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.deps-panel {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.deps-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
  padding-bottom: 6px;
  border-bottom: 1px dashed var(--hub-line);
}
.section-label {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--hub-fg-3);
}

.cve-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}
.cve-chip {
  display: inline-flex;
  align-items: baseline;
  gap: 4px;
  padding: 2px 7px;
  border-radius: 999px;
  font-family: var(--hub-font-mono);
  font-size: 10px;
  border: 1px solid currentColor;
  background: color-mix(in oklab, currentColor 14%, transparent);
}
.cve-chip-label {
  font-size: 8px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  opacity: 0.85;
}
.cve-chip.severity-critical {
  color: #ff5c5c;
}
.cve-chip.severity-high {
  color: #ff8f3d;
}
.cve-chip.severity-medium {
  color: #ffb547;
}
.cve-chip.severity-low {
  color: #9ca3af;
}

.pm-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}
.pm-tab {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 10px;
  border-radius: 8px;
  border: 1px solid var(--hub-line);
  background: var(--hub-bg-2);
  cursor: pointer;
  color: var(--hub-fg-2);
  transition: all 180ms var(--hub-ease);
}
.pm-tab:hover:not(.active) {
  border-color: var(--hub-line-strong);
  color: var(--hub-fg-1);
}
.pm-tab.active {
  border-color: hsla(var(--pm-hue, 260), 60%, 55%, 0.5);
  background: hsla(var(--pm-hue, 260), 60%, 55%, 0.1);
  color: var(--hub-fg-0);
}
.pm-tab-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: hsl(var(--pm-hue, 260), 70%, 65%);
  box-shadow: 0 0 8px hsla(var(--pm-hue, 260), 70%, 65%, 0.45);
}
.pm-tab-role {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: hsl(var(--pm-hue, 260), 70%, 80%);
}
.pm-tab-label {
  font-family: var(--hub-font-sans);
  font-size: 12px;
}
.pm-tab-count {
  font-family: var(--hub-font-mono);
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 4px;
  background: var(--hub-bg-3);
  color: var(--hub-fg-3);
}
.pm-tab-drift {
  margin-left: 4px;
  color: #ffb547;
}

.deps-toolbar {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.seg-group {
  display: inline-flex;
  gap: 2px;
  padding: 2px;
  border: 1px solid var(--hub-line);
  border-radius: 8px;
}
.seg-group.small {
  padding: 2px;
}
.seg {
  padding: 4px 10px;
  border-radius: 6px;
  border: none;
  background: transparent;
  color: var(--hub-fg-2);
  font-family: var(--hub-font-mono);
  font-size: 10px;
  letter-spacing: 0.06em;
  cursor: pointer;
  transition: all 180ms var(--hub-ease);
  white-space: nowrap;
}
.seg:hover:not(.active) {
  color: var(--hub-fg-1);
}
.seg.active {
  background: var(--hub-fg-0);
  color: var(--hub-bg-0);
}

.deps-search {
  flex: 1 1 200px;
  min-width: 180px;
  position: relative;
  display: flex;
  align-items: center;
}
.search-icon {
  position: absolute;
  left: 10px;
  font-family: var(--hub-font-mono);
  font-size: 12px;
  color: var(--hub-fg-3);
  pointer-events: none;
}
.deps-search input {
  flex: 1;
  padding: 7px 28px;
  border-radius: 8px;
  border: 1px solid var(--hub-line);
  background: var(--hub-bg-2);
  color: var(--hub-fg-0);
  font-family: var(--hub-font-mono);
  font-size: 11px;
  outline: none;
}
.deps-search input:focus {
  border-color: var(--hub-accent);
}
.search-clear {
  position: absolute;
  right: 6px;
  width: 20px;
  height: 20px;
  border-radius: 4px;
  border: none;
  background: transparent;
  color: var(--hub-fg-3);
  cursor: pointer;
  font-size: 13px;
}
.search-clear:hover {
  background: var(--hub-bg-3);
  color: var(--hub-fg-0);
}

.deps-loading {
  padding: 24px;
  text-align: center;
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-3);
}

.deps-table {
  border-radius: 10px;
  border: 1px solid var(--hub-line);
  background: var(--hub-bg-1);
  overflow: hidden;
}
.deps-table-head,
.deps-row {
  display: grid;
  grid-template-columns: minmax(160px, 2fr) 1fr 1fr 110px 60px;
  gap: 12px;
  padding: 10px 14px;
  align-items: center;
}
.deps-table-head {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  color: var(--hub-fg-3);
  letter-spacing: 0.12em;
  text-transform: uppercase;
  border-bottom: 1px solid var(--hub-line);
}
.deps-row {
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-1);
  border-bottom: 1px solid color-mix(in oklab, var(--hub-line) 60%, transparent);
}
.deps-row:last-child {
  border-bottom: none;
}
.deps-row:hover {
  background: color-mix(in oklab, var(--hub-bg-2) 50%, transparent);
}
.dep-name {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--hub-fg-0);
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.dep-pm {
  font-size: 9px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--hub-fg-3);
  padding: 1px 5px;
  border-radius: 3px;
  background: var(--hub-bg-3);
}
.dep-cve-flag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-left: auto;
  padding: 2px 7px;
  border-radius: 999px;
  border: 1px solid #ff5c5c;
  background: color-mix(in oklab, #ff5c5c 16%, transparent);
  color: #ff8080;
  font-family: var(--hub-font-mono);
  font-size: 10px;
  letter-spacing: 0.04em;
}
.cve-flag-icon {
  font-size: 10px;
}
.cve-flag-count {
  color: #fff;
  font-weight: 500;
}
.cve-flag-label {
  font-size: 8px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  opacity: 0.85;
}
.cve-flag-caret {
  font-size: 10px;
  transition: transform 160ms var(--hub-ease);
  opacity: 0.7;
}
.cve-flag-caret.open {
  transform: rotate(180deg);
}

.deps-row.clickable {
  cursor: pointer;
}
.deps-row.has-cve {
  background: color-mix(in oklab, #ff5c5c 4%, transparent);
}
.deps-row.expanded {
  background: color-mix(in oklab, #ff5c5c 10%, transparent);
}

.dep-cve-panel {
  padding: 12px 14px 14px 18px;
  background: color-mix(in oklab, var(--hub-bg-2) 60%, transparent);
  border-bottom: 1px solid color-mix(in oklab, var(--hub-line) 60%, transparent);
}
.cve-loading,
.cve-empty {
  padding: 8px;
  text-align: center;
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-3);
  letter-spacing: 0.06em;
}
.cve-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.cve-item {
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid var(--hub-line);
  background: var(--hub-bg-1);
  border-left: 3px solid currentColor;
}
.cve-item.severity-critical {
  color: #ff5c5c;
}
.cve-item.severity-high {
  color: #ff8f3d;
}
.cve-item.severity-medium {
  color: #ffb547;
}
.cve-item.severity-low {
  color: #9ca3af;
}
.cve-head {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.cve-id {
  display: inline-flex;
  align-items: baseline;
  gap: 4px;
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-0);
  text-decoration: none;
  padding: 2px 6px;
  border-radius: 4px;
  background: var(--hub-bg-3);
  transition: all 160ms var(--hub-ease);
}
.cve-id:hover {
  background: var(--hub-accent);
  color: var(--hub-bg-0);
}
.cve-link-glyph {
  font-size: 9px;
  opacity: 0.6;
}
.cve-severity {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-family: var(--hub-font-mono);
  font-size: 9px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}
.cve-severity-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}
.cve-status {
  font-family: var(--hub-font-mono);
  font-size: 8px;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  padding: 2px 6px;
  border-radius: 4px;
  border: 1px solid var(--hub-line);
  color: var(--hub-fg-2);
}
.cve-status.status-fixed {
  border-color: #4ade80;
  color: #4ade80;
}
.cve-status.status-acknowledged {
  border-color: #ffb547;
  color: #ffb547;
}
.cve-status.status-ignored {
  border-color: var(--hub-fg-3);
  color: var(--hub-fg-3);
}
.cve-patch {
  font-family: var(--hub-font-mono);
  font-size: 9px;
  letter-spacing: 0.06em;
  color: var(--hub-fg-2);
  margin-left: auto;
}
.cve-title {
  margin-top: 6px;
  font-family: var(--hub-font-sans);
  font-size: 12px;
  color: var(--hub-fg-1);
  line-height: 1.4;
}
.cve-description {
  margin-top: 4px;
  font-family: var(--hub-font-sans);
  font-size: 11px;
  color: var(--hub-fg-2);
  line-height: 1.5;
}
.dep-version {
  color: var(--hub-fg-2);
}
.dep-version.latest {
  color: var(--hub-fg-1);
}
.dep-status {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 9px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}
.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}
.deps-row.drift-major .status-dot {
  background: #ff5c5c;
}
.deps-row.drift-major .dep-status {
  color: #ff8080;
}
.deps-row.drift-minor .status-dot {
  background: #ffb547;
}
.deps-row.drift-minor .dep-status {
  color: #ffc87a;
}
.deps-row.drift-patch .status-dot {
  background: #ffe066;
}
.deps-row.drift-patch .dep-status {
  color: #ffe680;
}
.deps-row.drift-ok .status-dot {
  background: #4ade80;
}
.deps-row.drift-ok .dep-status {
  color: color-mix(in oklab, #4ade80 85%, white);
}
.dep-type {
  font-size: 9px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--hub-fg-3);
}
.text-right {
  text-align: right;
}

.deps-empty {
  padding: 24px 14px;
  text-align: center;
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-3);
}

.deps-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding: 8px 2px;
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-3);
  letter-spacing: 0.06em;
}
.deps-range .muted {
  color: var(--hub-fg-3);
}
.deps-range .fg-strong {
  color: var(--hub-fg-1);
}
.deps-pagination-controls {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.page-buttons {
  display: flex;
  align-items: center;
  gap: 4px;
}
.page-btn {
  min-width: 26px;
  height: 26px;
  border-radius: 6px;
  border: 1px solid var(--hub-line);
  background: transparent;
  color: var(--hub-fg-1);
  font-family: var(--hub-font-mono);
  font-size: 10px;
  cursor: pointer;
  transition: all 150ms var(--hub-ease);
}
.page-btn:hover:not(:disabled):not(.active) {
  border-color: var(--hub-line-strong);
}
.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.page-btn.active {
  border-color: var(--hub-accent);
  background: color-mix(in oklab, var(--hub-accent) 16%, var(--hub-bg-2));
  color: var(--hub-fg-0);
}
.page-ellipsis {
  padding: 0 4px;
  color: var(--hub-fg-3);
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
