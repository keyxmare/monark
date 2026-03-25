<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute } from 'vue-router'

import ProviderIcon from '@/catalog/components/ProviderIcon.vue'
import { humanizeMs, humanizeTimeDiff, isVersionUpToDate, ltsUrgency, msUrgency, patchGap, useFrameworkLts } from '@/catalog/composables/useFrameworkLts'
import { useSyncProgress } from '@/catalog/composables/useSyncProgress'
import { useProjectStore } from '@/catalog/stores/project'
import { useProviderStore } from '@/catalog/stores/provider'
import { useTechStackStore } from '@/catalog/stores/tech-stack'
import { exportTechStacksPdf } from '@/catalog/services/techStackPdfExport'
import ExportDropdown from '@/shared/components/ExportDropdown.vue'
import Pagination from '@/shared/components/Pagination.vue'
import TechBadge from '@/shared/components/TechBadge.vue'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'

const route = useRoute()
const { d, t } = useI18n()
const techStackStore = useTechStackStore()
const projectStore = useProjectStore()
const providerStore = useProviderStore()
const { loadForFrameworks, getLtsInfo, getVersionReleaseDate, getVersionMaintenanceStatus } = useFrameworkLts()
const { track } = useSyncProgress()
const syncing = ref(false)

const projectId = route.query.project_id as string | undefined

const search = ref('')
const filterFramework = ref('')
const filterProvider = ref('')
const filterStatus = ref('')

type GroupBy = 'project' | 'framework' | 'provider'
const groupBy = ref<GroupBy>('project')

type SortField = 'project' | 'framework' | 'frameworkVersion' | 'ltsGap'
const sortField = ref<SortField>('project')
const sortDir = ref<'asc' | 'desc'>('asc')

function toggleSort(field: SortField) {
  if (sortField.value === field) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDir.value = 'asc'
  }
}

function sortIndicator(field: SortField): string {
  if (sortField.value !== field) return ''
  return sortDir.value === 'asc' ? ' ↑' : ' ↓'
}

const projectMap = computed(() => {
  const map = new Map<string, { name: string; providerId: string | null }>()
  for (const p of projectStore.projects) {
    map.set(p.id, { name: p.name, providerId: p.providerId })
  }
  return map
})

const providerMap = computed(() => {
  const map = new Map<string, { name: string; type: string }>()
  for (const p of providerStore.providers) {
    map.set(p.id, { name: p.name, type: p.type })
  }
  return map
})

interface ProviderAggregate {
  id: string
  name: string
  type: string
  projectCount: number
  frameworks: { name: string; min: string; max: string }[]
}

const providerAggregates = computed<ProviderAggregate[]>(() => {
  const agg = new Map<string, { name: string; type: string; projectIds: Set<string>; frameworks: Map<string, string[]> }>()

  for (const ts of techStackStore.techStacks) {
    if (ts.framework === 'none' || !ts.framework) continue

    const proj = projectMap.value.get(ts.projectId)
    if (!proj?.providerId) continue

    const provider = providerMap.value.get(proj.providerId)
    if (!provider) continue

    if (!agg.has(proj.providerId)) {
      agg.set(proj.providerId, { name: provider.name, type: provider.type, projectIds: new Set(), frameworks: new Map() })
    }

    const entry = agg.get(proj.providerId)!
    entry.projectIds.add(ts.projectId)

    if (!entry.frameworks.has(ts.framework)) {
      entry.frameworks.set(ts.framework, [])
    }
    if (ts.frameworkVersion) {
      entry.frameworks.get(ts.framework)!.push(ts.frameworkVersion)
    }
  }

  return [...agg.entries()].map(([id, entry]) => ({
    id,
    name: entry.name,
    type: entry.type,
    projectCount: entry.projectIds.size,
    frameworks: [...entry.frameworks.entries()].map(([name, versions]) => {
      const sorted = [...versions].sort((a, b) => a.localeCompare(b, undefined, { numeric: true }))
      return { name, min: sorted[0] ?? '—', max: sorted[sorted.length - 1] ?? '—' }
    }),
  }))
})

const healthScore = computed(() => {
  const stacks = filteredStacks.value
  if (stacks.length === 0) return null

  let active = 0
  let eol = 0
  let warning = 0

  for (const ts of stacks) {
    const status = getVersionMaintenanceStatus(ts.framework, ts.frameworkVersion)
    if (status?.status === 'eol') eol++
    else if (status?.status === 'warning') warning++
    else active++
  }

  return {
    total: stacks.length,
    active,
    eol,
    warning,
    percent: Math.round((active / stacks.length) * 100),
  }
})

const gapStats = computed(() => {
  const gaps: number[] = []

  for (const ts of filteredStacks.value) {
    const info = getLtsInfo(ts.framework)
    if (!info || !ts.frameworkVersion) continue
    if (isVersionUpToDate(ts.frameworkVersion, info.latestLts)) continue

    const vDate = getVersionReleaseDate(ts.framework, ts.frameworkVersion)
    if (!vDate) continue

    const gapMs = Math.abs(new Date(info.releaseDate).getTime() - new Date(vDate).getTime())
    gaps.push(gapMs)
  }

  if (gaps.length === 0) return null

  const sorted = [...gaps].sort((a, b) => a - b)
  const cumulated = gaps.reduce((s, g) => s + g, 0)
  const average = cumulated / gaps.length
  const median = sorted.length % 2 === 0
    ? (sorted[sorted.length / 2 - 1] + sorted[sorted.length / 2]) / 2
    : sorted[Math.floor(sorted.length / 2)]

  return { cumulated, average, median }
})

const availableFrameworks = computed(() => {
  const set = new Set<string>()
  for (const ts of techStackStore.techStacks) {
    if (ts.framework && ts.framework !== 'none') set.add(ts.framework)
  }
  return [...set].sort()
})

const availableProviders = computed(() => {
  const set = new Map<string, string>()
  for (const p of providerStore.providers) {
    set.set(p.id, p.name)
  }
  return [...set.entries()].map(([id, name]) => ({ id, name }))
})

const filteredStacks = computed(() => {
  const filtered = techStackStore.techStacks.filter(ts => {
    if (search.value) {
      const q = search.value.toLowerCase()
      const projName = projectMap.value.get(ts.projectId)?.name ?? ''
      if (!projName.toLowerCase().includes(q) && !ts.framework.toLowerCase().includes(q)) return false
    }
    if (filterFramework.value && ts.framework !== filterFramework.value) return false
    if (filterProvider.value) {
      const proj = projectMap.value.get(ts.projectId)
      if (proj?.providerId !== filterProvider.value) return false
    }
    if (filterStatus.value) {
      const status = getVersionMaintenanceStatus(ts.framework, ts.frameworkVersion)
      if (filterStatus.value === 'eol' && status?.status !== 'eol') return false
      if (filterStatus.value === 'warning' && status?.status !== 'warning') return false
      if (filterStatus.value === 'active' && status?.status !== 'active') return false
    }
    return true
  })
  return filtered
})

interface GroupedStack {
  projectId: string
  projectName: string
  isFirstInGroup: boolean
  groupSize: number
  groupIndex: number
  ts: import('@/catalog/types/tech-stack').TechStack
}

function groupKey(ts: import('@/catalog/types/tech-stack').TechStack): string {
  if (groupBy.value === 'framework') return ts.framework
  if (groupBy.value === 'provider') {
    const proj = projectMap.value.get(ts.projectId)
    return proj?.providerId ?? 'unknown'
  }
  return ts.projectId
}

function groupLabel(key: string): string {
  if (groupBy.value === 'framework') return key
  if (groupBy.value === 'provider') {
    return providerMap.value.get(key)?.name ?? key
  }
  return projectMap.value.get(key)?.name ?? key
}

function worstGapForGroup(stacks: import('@/catalog/types/tech-stack').TechStack[]): number {
  let worst = -1
  for (const ts of stacks) {
    const vDate = getVersionReleaseDate(ts.framework, ts.frameworkVersion)
    const ltsDate = getLtsInfo(ts.framework)?.releaseDate
    if (vDate && ltsDate) {
      const ltsVersion = getLtsInfo(ts.framework)?.latestLts ?? ''
      if (ltsVersion && isVersionUpToDate(ts.frameworkVersion, ltsVersion)) {
        if (worst < 0) worst = 0
      } else {
        const gap = new Date(ltsDate).getTime() - new Date(vDate).getTime()
        if (gap > worst) worst = gap
      }
    }
  }
  return worst
}

function sortValueForGroup(stacks: import('@/catalog/types/tech-stack').TechStack[], key: string): string | number {
  switch (sortField.value) {
    case 'project':
      return groupLabel(key).toLowerCase()
    case 'framework':
      return stacks[0]?.framework?.toLowerCase() ?? ''
    case 'frameworkVersion':
      return stacks[0]?.frameworkVersion ?? ''
    case 'ltsGap':
      return worstGapForGroup(stacks)
    default:
      return 0
  }
}

const groupedStacks = computed<GroupedStack[]>(() => {
  const groups = new Map<string, import('@/catalog/types/tech-stack').TechStack[]>()
  for (const ts of filteredStacks.value) {
    const key = groupKey(ts)
    if (!groups.has(key)) {
      groups.set(key, [])
    }
    groups.get(key)!.push(ts)
  }

  const dir = sortDir.value === 'asc' ? 1 : -1
  const sortedEntries = [...groups.entries()].sort(([keyA, stacksA], [keyB, stacksB]) => {
    const valA = sortValueForGroup(stacksA, keyA)
    const valB = sortValueForGroup(stacksB, keyB)

    if (sortField.value === 'ltsGap') {
      const gapA = valA as number
      const gapB = valB as number
      if (gapA === -1 && gapB === -1) return 0
      if (gapA === -1) return 1
      if (gapB === -1) return -1
      return (gapB - gapA) * dir
    }

    return String(valA).localeCompare(String(valB), undefined, { numeric: true }) * dir
  })

  const result: GroupedStack[] = []
  let groupIndex = 0
  for (const [key, stacks] of sortedEntries) {
    const label = groupLabel(key)
    stacks.forEach((ts, i) => {
      result.push({
        projectId: ts.projectId,
        projectName: label,
        isFirstInGroup: i === 0,
        groupSize: stacks.length,
        groupIndex,
        ts,
      })
    })
    groupIndex++
  }
  return result
})

onMounted(async () => {
  await Promise.all([
    techStackStore.fetchAll(1, 1000, projectId),
    projectStore.fetchAll(1, 200),
    providerStore.fetchAll(1, 50),
  ])
  const frameworks = techStackStore.techStacks
    .map(ts => ts.framework)
    .filter(f => f && f !== 'none')
  await loadForFrameworks(frameworks)
})

function changePage(page: number) {
  techStackStore.fetchAll(page, 1000, projectId)
}

async function handleSyncAll() {
  syncing.value = true
  try {
    const result = await providerStore.syncAllGlobal()
    track(result.id, result.projectsCount)
  } catch {
  } finally {
    syncing.value = false
  }
}

function handleExport(format: 'csv' | 'pdf') {
  if (format === 'csv') exportCsv()
  else exportPdf()
}

function exportPdf() {
  const rows = filteredStacks.value.map(ts => {
    const projName = projectMap.value.get(ts.projectId)?.name ?? ts.projectId
    const lts = getLtsInfo(ts.framework)?.latestLts ?? ''
    const releaseDate = getVersionReleaseDate(ts.framework, ts.frameworkVersion) ?? ''
    const info = getLtsInfo(ts.framework)
    let gap = '—'
    let status = 'OK'

    if (info && ts.frameworkVersion) {
      if (isVersionUpToDate(ts.frameworkVersion, info.latestLts)) {
        gap = 'À jour'
      } else {
        const pg = patchGap(ts.frameworkVersion, info.latestLts)
        if (pg !== null) {
          gap = `${pg} patch(es)`
        } else {
          const vDate = getVersionReleaseDate(ts.framework, ts.frameworkVersion)
          if (vDate) gap = humanizeTimeDiff(vDate, info.releaseDate)
        }
      }
    }

    const maintenance = getVersionMaintenanceStatus(ts.framework, ts.frameworkVersion)
    if (maintenance?.status === 'eol') status = 'Non maintenu'
    else if (maintenance?.status === 'warning') status = 'Inactif'

    return { project: projName, language: ts.language, framework: ts.framework, version: ts.frameworkVersion, latestLts: lts, ltsGap: gap, status, releaseDate }
  })

  const gapData = gapStats.value ? {
    cumulated: humanizeMs(gapStats.value.cumulated),
    average: humanizeMs(gapStats.value.average),
    median: humanizeMs(gapStats.value.median),
  } : null
  exportTechStacksPdf(rows, healthScore.value, providerAggregates.value, gapData)
}

function exportCsv() {
  const headers = ['Projet', 'Langage', 'Framework', 'Version', 'Dernière LTS', 'Écart LTS', 'Statut']
  const rows = filteredStacks.value.map(ts => {
    const projName = projectMap.value.get(ts.projectId)?.name ?? ts.projectId
    const lts = getLtsInfo(ts.framework)?.latestLts ?? ''
    const releaseDate = getVersionReleaseDate(ts.framework, ts.frameworkVersion)
    const ltsDate = getLtsInfo(ts.framework)?.releaseDate
    const gap = releaseDate && ltsDate ? humanizeTimeDiff(releaseDate, ltsDate) : ''
    const status = getVersionMaintenanceStatus(ts.framework, ts.frameworkVersion)
    const statusLabel = status?.status === 'eol' ? 'Non maintenu' : status?.status === 'warning' ? 'Inactif' : 'OK'
    return [projName, ts.language, ts.framework, ts.frameworkVersion, lts, gap, statusLabel]
  })

  const csv = [headers, ...rows].map(r => r.map(c => `"${c}"`).join(',')).join('\n')
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'tech-stacks.csv'
  a.click()
  URL.revokeObjectURL(url)
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="tech-stack-list-page">
      <nav
        class="mb-6 flex items-center gap-1 text-sm text-text-muted"
        data-testid="tech-stack-list-breadcrumb"
      >
        <span class="font-medium text-text">
          {{ t('catalog.techStacks.title') }}
        </span>
      </nav>

      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text">
          {{ t('catalog.techStacks.title') }}
          <span
            v-if="filteredStacks.length > 0"
            class="text-lg font-normal text-text-muted"
          >({{ filteredStacks.length }})</span>
        </h2>
        <div class="flex items-center gap-3">
          <ExportDropdown @export="handleExport" />
          <button
            :disabled="syncing"
            class="rounded-lg border border-primary bg-transparent px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary hover:text-white disabled:opacity-50"
            data-testid="tech-stack-sync-all"
            @click="handleSyncAll"
          >
            {{ syncing ? t('catalog.providers.syncing') : t('catalog.providers.syncAll') }}
          </button>
        </div>
      </div>

      <div
        v-if="techStackStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="tech-stack-list-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="techStackStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="tech-stack-list-error"
      >
        {{ techStackStore.error }}
      </div>

      <template v-else>
        <!-- Health score -->
        <div
          v-if="healthScore"
          class="mb-6 flex flex-wrap items-center gap-4 rounded-xl border border-border bg-surface p-4"
          data-testid="health-score"
        >
          <div class="flex-1">
            <div class="mb-1 flex items-center justify-between text-sm">
              <span class="font-medium text-text">{{ t('catalog.techStacks.healthScore', { percent: healthScore.percent }) }}</span>
              <span class="text-text-muted">{{ healthScore.active }}/{{ healthScore.total }}</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-surface-muted">
              <div
                class="h-full rounded-full bg-success transition-all"
                :style="{ width: `${healthScore.percent}%` }"
              />
            </div>
          </div>
          <div
            v-if="healthScore.eol > 0"
            class="rounded-full bg-danger/10 px-3 py-1 text-sm font-medium text-danger"
          >
            {{ t('catalog.techStacks.healthEol', { count: healthScore.eol }) }}
          </div>
          <div
            v-if="healthScore.warning > 0"
            class="rounded-full bg-warning/10 px-3 py-1 text-sm font-medium text-warning"
          >
            {{ t('catalog.techStacks.healthWarning', { count: healthScore.warning }) }}
          </div>
        </div>

        <!-- Gap stats -->
        <div
          v-if="gapStats"
          class="mb-6 grid grid-cols-3 gap-4"
          data-testid="gap-stats"
        >
          <div class="rounded-xl border border-border bg-surface p-4 text-center">
            <p class="text-xs text-text-muted">
              {{ t('catalog.techStacks.gapCumulated') }}
            </p>
            <p
              :class="{
                'text-success': msUrgency(gapStats.cumulated) === 'fresh',
                'text-warning': msUrgency(gapStats.cumulated) === 'moderate',
                'text-danger': msUrgency(gapStats.cumulated) === 'outdated',
              }"
              class="mt-1 text-lg font-bold"
            >
              {{ humanizeMs(gapStats.cumulated) }}
            </p>
          </div>
          <div class="rounded-xl border border-border bg-surface p-4 text-center">
            <p class="text-xs text-text-muted">
              {{ t('catalog.techStacks.gapAverage') }}
            </p>
            <p
              :class="{
                'text-success': msUrgency(gapStats.average) === 'fresh',
                'text-warning': msUrgency(gapStats.average) === 'moderate',
                'text-danger': msUrgency(gapStats.average) === 'outdated',
              }"
              class="mt-1 text-lg font-bold"
            >
              {{ humanizeMs(gapStats.average) }}
            </p>
          </div>
          <div class="rounded-xl border border-border bg-surface p-4 text-center">
            <p class="text-xs text-text-muted">
              {{ t('catalog.techStacks.gapMedian') }}
            </p>
            <p
              :class="{
                'text-success': msUrgency(gapStats.median) === 'fresh',
                'text-warning': msUrgency(gapStats.median) === 'moderate',
                'text-danger': msUrgency(gapStats.median) === 'outdated',
              }"
              class="mt-1 text-lg font-bold"
            >
              {{ humanizeMs(gapStats.median) }}
            </p>
          </div>
        </div>

        <!-- Provider aggregates -->
        <div
          v-if="providerAggregates.length > 0"
          class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
          data-testid="provider-aggregates"
        >
          <div
            v-for="agg in providerAggregates"
            :key="agg.name"
            class="rounded-xl border border-border bg-surface p-4"
            data-testid="provider-aggregate-card"
          >
            <div class="mb-3 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <ProviderIcon
                  :type="(agg.type as any)"
                  :size="20"
                />
                <RouterLink
                  :to="{ name: 'catalog-providers-detail', params: { id: agg.id } }"
                  class="text-sm font-semibold text-primary hover:text-primary-dark"
                >{{ agg.name }}</RouterLink>
              </div>
              <span class="text-xs text-text-muted">
                {{ t('catalog.techStacks.projectCount', { count: agg.projectCount }) }}
              </span>
            </div>
            <div class="space-y-1.5">
              <div
                v-for="fw in agg.frameworks"
                :key="fw.name"
                class="flex items-center justify-between text-sm"
              >
                <span class="font-medium text-text">{{ fw.name }}</span>
                <span class="inline-flex items-center gap-1.5 tabular-nums text-text-muted">
                  <template v-if="fw.min === fw.max">
                    {{ fw.min }}
                  </template>
                  <template v-else>
                    {{ fw.min }} → {{ fw.max }}
                  </template>
                  <span
                    v-if="getVersionMaintenanceStatus(fw.name, fw.min)?.status === 'eol'"
                    class="rounded-full bg-danger/10 px-1.5 py-0.5 text-xs font-medium text-danger"
                  >
                    {{ t('catalog.techStacks.unmaintained') }}
                  </span>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div
          class="mb-4 flex flex-wrap items-center gap-3"
          data-testid="tech-stack-filters"
        >
          <div class="relative flex-1">
            <svg
              class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
              />
            </svg>
            <input
              v-model="search"
              type="search"
              :aria-label="t('catalog.techStacks.searchPlaceholder')"
              :placeholder="t('catalog.techStacks.searchPlaceholder')"
              class="w-full rounded-lg border border-border bg-surface py-2 pl-9 pr-3 text-sm text-text placeholder:text-text-muted focus:border-primary focus:outline-none"
              data-testid="tech-stack-search"
            >
          </div>
          <select
            v-model="filterFramework"
            :aria-label="t('catalog.techStacks.allFrameworks')"
            class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
            data-testid="tech-stack-filter-framework"
          >
            <option value="">
              {{ t('catalog.techStacks.allFrameworks') }}
            </option>
            <option
              v-for="fw in availableFrameworks"
              :key="fw"
              :value="fw"
            >
              {{ fw }}
            </option>
          </select>
          <select
            v-model="filterProvider"
            :aria-label="t('catalog.techStacks.allProviders')"
            class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
            data-testid="tech-stack-filter-provider"
          >
            <option value="">
              {{ t('catalog.techStacks.allProviders') }}
            </option>
            <option
              v-for="prov in availableProviders"
              :key="prov.id"
              :value="prov.id"
            >
              {{ prov.name }}
            </option>
          </select>
          <select
            v-model="filterStatus"
            :aria-label="t('catalog.techStacks.allStatuses')"
            class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
            data-testid="tech-stack-filter-status"
          >
            <option value="">
              {{ t('catalog.techStacks.allStatuses') }}
            </option>
            <option value="active">
              {{ t('catalog.techStacks.statusActive') }}
            </option>
            <option value="eol">
              {{ t('catalog.techStacks.statusUnmaintained') }}
            </option>
            <option value="warning">
              {{ t('catalog.techStacks.statusInactive') }}
            </option>
          </select>
        </div>

        <!-- Group toggle -->
        <div
          class="mb-4 flex items-center gap-1"
          data-testid="tech-stack-group-toggle"
        >
          <button
            v-for="mode in (['project', 'framework', 'provider'] as const)"
            :key="mode"
            :class="groupBy === mode ? 'border-primary bg-primary/10 text-primary' : 'border-border text-text-muted hover:border-primary/50'"
            class="rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors"
            @click="groupBy = mode"
          >
            {{ t(`catalog.techStacks.groupBy${mode.charAt(0).toUpperCase() + mode.slice(1)}`) }}
          </button>
        </div>

        <!-- Tech stacks table -->
        <div class="overflow-hidden rounded-xl border border-border bg-surface">
          <table
            class="w-full"
            data-testid="tech-stack-list-table"
          >
            <thead>
              <tr class="border-b border-border bg-surface-muted">
                <th
                  class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
                  @click="toggleSort('project')"
                >
                  {{ t('catalog.techStacks.project') }}{{ sortIndicator('project') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.techStacks.language') }}
                </th>
                <th
                  class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
                  @click="toggleSort('framework')"
                >
                  {{ t('catalog.techStacks.framework') }}{{ sortIndicator('framework') }}
                </th>
                <th
                  class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
                  @click="toggleSort('frameworkVersion')"
                >
                  {{ t('catalog.techStacks.frameworkVersion') }}{{ sortIndicator('frameworkVersion') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.techStacks.latestLts') }}
                </th>
                <th
                  class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
                  @click="toggleSort('ltsGap')"
                >
                  {{ t('catalog.techStacks.ltsGap') }}{{ sortIndicator('ltsGap') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.techStacks.releasedAt') }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in groupedStacks"
                :key="row.ts.id"
                :class="[
                  row.isFirstInGroup ? 'border-t border-border first:border-0' : '',
                  row.groupIndex % 2 === 1 ? 'bg-surface-muted/50' : '',
                ]"
                data-testid="tech-stack-list-row"
              >
                <td
                  v-if="row.isFirstInGroup"
                  :rowspan="row.groupSize"
                  class="px-4 py-3 text-sm align-top"
                >
                  <RouterLink
                    v-if="groupBy === 'project'"
                    :to="{ name: 'catalog-projects-detail', params: { id: row.projectId } }"
                    class="font-medium text-primary hover:text-primary-dark"
                  >
                    {{ row.projectName }}
                  </RouterLink>
                  <span
                    v-else
                    class="font-medium text-text"
                  >{{ row.projectName }}</span>
                </td>
                <td class="px-4 py-3 text-sm text-text">
                  {{ row.ts.language }}
                </td>
                <td class="px-4 py-3 text-sm text-text">
                  <TechBadge
                    :name="row.ts.framework"
                    size="sm"
                  />
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">
                  <span class="inline-flex items-center gap-1.5">
                    {{ row.ts.frameworkVersion || '—' }}
                    <span
                      v-if="getVersionMaintenanceStatus(row.ts.framework, row.ts.frameworkVersion)?.status === 'eol'"
                      class="rounded-full bg-danger/10 px-1.5 py-0.5 text-xs font-medium text-danger"
                      :title="getVersionMaintenanceStatus(row.ts.framework, row.ts.frameworkVersion)?.eolDate
                        ? t('catalog.techStacks.unmaintainedSince', { date: getVersionMaintenanceStatus(row.ts.framework, row.ts.frameworkVersion)!.eolDate! })
                        : t('catalog.techStacks.unmaintainedNoDate')"
                    >
                      {{ t('catalog.techStacks.unmaintained') }}
                    </span>
                    <span
                      v-else-if="getVersionMaintenanceStatus(row.ts.framework, row.ts.frameworkVersion)?.status === 'warning'"
                      class="rounded-full bg-warning/10 px-1.5 py-0.5 text-xs font-medium text-warning"
                      :title="getVersionMaintenanceStatus(row.ts.framework, row.ts.frameworkVersion)?.lastRelease
                        ? t('catalog.techStacks.inactiveSince', { duration: humanizeTimeDiff(getVersionMaintenanceStatus(row.ts.framework, row.ts.frameworkVersion)!.lastRelease!, new Date().toISOString()) })
                        : t('catalog.techStacks.inactive')"
                    >
                      {{ t('catalog.techStacks.inactive') }}
                    </span>
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">
                  {{ getLtsInfo(row.ts.framework)?.latestLts ?? '—' }}
                </td>
                <td class="px-4 py-3 text-sm">
                  <template v-if="getLtsInfo(row.ts.framework) && row.ts.frameworkVersion && getVersionReleaseDate(row.ts.framework, row.ts.frameworkVersion)">
                    <span
                      v-if="isVersionUpToDate(row.ts.frameworkVersion, getLtsInfo(row.ts.framework)!.latestLts)"
                      class="text-success"
                    >
                      {{ t('catalog.techStacks.upToDate') }}
                    </span>
                    <span
                      v-else-if="patchGap(row.ts.frameworkVersion, getLtsInfo(row.ts.framework)!.latestLts) !== null"
                      class="text-warning"
                    >
                      {{ t('catalog.techStacks.patchesBehind', { count: patchGap(row.ts.frameworkVersion, getLtsInfo(row.ts.framework)!.latestLts) }) }}
                    </span>
                    <span
                      v-else
                      :class="{
                        'text-success': ltsUrgency(getVersionReleaseDate(row.ts.framework, row.ts.frameworkVersion)!, getLtsInfo(row.ts.framework)!.releaseDate) === 'fresh',
                        'text-warning': ltsUrgency(getVersionReleaseDate(row.ts.framework, row.ts.frameworkVersion)!, getLtsInfo(row.ts.framework)!.releaseDate) === 'moderate',
                        'text-danger': ltsUrgency(getVersionReleaseDate(row.ts.framework, row.ts.frameworkVersion)!, getLtsInfo(row.ts.framework)!.releaseDate) === 'outdated',
                      }"
                    >
                      {{ humanizeTimeDiff(getVersionReleaseDate(row.ts.framework, row.ts.frameworkVersion)!, getLtsInfo(row.ts.framework)!.releaseDate) }}
                    </span>
                  </template>
                  <span
                    v-else
                    class="text-text-muted"
                  >—</span>
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">
                  {{ getVersionReleaseDate(row.ts.framework, row.ts.frameworkVersion) ?? '—' }}
                </td>
              </tr>
            </tbody>
          </table>

          <div
            v-if="groupedStacks.length === 0 && techStackStore.techStacks.length > 0"
            class="py-8 text-center text-text-muted"
            data-testid="tech-stack-list-no-match"
          >
            {{ t('catalog.techStacks.noMatchingStacks') }}
          </div>
          <div
            v-else-if="techStackStore.techStacks.length === 0"
            class="flex flex-col items-center py-12"
            data-testid="tech-stack-list-empty"
          >
            <svg
              class="mb-4 h-12 w-12 text-text-muted/50"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0L12 16.5l-5.571-2.25m11.142 0L21.75 16.5 12 21.75 2.25 16.5l4.179-2.25"
              />
            </svg>
            <p class="mb-1 text-sm font-medium text-text">
              {{ t('catalog.techStacks.noTechStacks') }}
            </p>
            <p class="mb-4 text-sm text-text-muted">
              {{ t('catalog.techStacks.noTechStacksHint') }}
            </p>
            <RouterLink
              :to="{ name: 'catalog-providers-list' }"
              class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
              data-testid="tech-stack-empty-providers-link"
            >
              {{ t('catalog.providers.title') }}
            </RouterLink>
          </div>
        </div>

        <Pagination
          v-if="techStackStore.totalPages > 1"
          :page="techStackStore.currentPage"
          :total-pages="techStackStore.totalPages"
          data-testid="tech-stack-list-pagination"
          @update:page="changePage"
        />
      </template>
    </div>
  </DashboardLayout>
</template>
