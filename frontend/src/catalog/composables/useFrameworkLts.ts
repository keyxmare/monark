import { ref } from 'vue'

interface EndOfLifeCycle {
  cycle: string
  releaseDate: string
  eol: string | boolean
  latest: string
  latestReleaseDate: string
  lts: boolean
}

export interface LtsInfo {
  latestLts: string
  releaseDate: string
  cycles: EndOfLifeCycle[]
}

export type MaintenanceStatus = 'active' | 'warning' | 'eol'

export interface MaintenanceInfo {
  status: MaintenanceStatus
  eolDate: string | null
  lastRelease: string | null
}

const FRAMEWORK_MAP: Record<string, string> = {
  Symfony: 'symfony',
  Vue: 'vue',
  Nuxt: 'nuxt',
}

const FRAMEWORK_ALIASES: Record<string, string> = {
  Symfony1: 'Symfony',
  AngularJS: 'Angular',
}

export function resolveFramework(framework: string): string {
  return FRAMEWORK_ALIASES[framework] ?? framework
}

const cache = new Map<string, LtsInfo | null>()
const pending = new Map<string, Promise<LtsInfo | null>>()

async function fetchLtsInfo(framework: string): Promise<LtsInfo | null> {
  const resolved = resolveFramework(framework)
  const slug = FRAMEWORK_MAP[resolved]
  if (!slug) return null

  if (cache.has(resolved)) return cache.get(resolved)!
  if (pending.has(resolved)) return pending.get(resolved)!

  const promise = fetch(`https://endoflife.date/api/${slug}.json`)
    .then((res) => {
      if (!res.ok) return null
      return res.json() as Promise<EndOfLifeCycle[]>
    })
    .then((cycles) => {
      if (!cycles) return null

      const ltsCycle = cycles.find(c => c.lts === true)
      const target = ltsCycle ?? cycles[0]
      if (!target) return null

      const info: LtsInfo = {
        latestLts: target.latest,
        releaseDate: target.latestReleaseDate ?? target.releaseDate,
        cycles,
      }

      cache.set(resolved, info)
      return info
    })
    .catch(() => {
      cache.set(resolved, null)
      return null
    })
    .finally(() => {
      pending.delete(resolved)
    })

  pending.set(resolved, promise)
  return promise
}

interface CycleMatch {
  cycle: EndOfLifeCycle
  isFallback: boolean
}

function findCycle(cycles: EndOfLifeCycle[], frameworkVersion: string): CycleMatch | null {
  const major = frameworkVersion.split('.')[0]
  const majorMinor = frameworkVersion.split('.').slice(0, 2).join('.')

  const exact = cycles.find(c => c.cycle === majorMinor)
  if (exact) return { cycle: exact, isFallback: false }

  const byMajor = cycles.find(c => c.cycle === major)
  if (byMajor) return { cycle: byMajor, isFallback: false }

  const oldest = cycles[cycles.length - 1]
  if (oldest) {
    const oldestMajor = Number.parseInt(oldest.cycle.split('.')[0], 10)
    const currentMajor = Number.parseInt(major, 10)
    if (currentMajor < oldestMajor) return { cycle: oldest, isFallback: true }
  }

  return null
}

function findCycleReleaseDate(cycles: EndOfLifeCycle[], frameworkVersion: string): string | null {
  return findCycle(cycles, frameworkVersion)?.cycle.releaseDate ?? null
}

export function getMaintenanceStatus(cycle: EndOfLifeCycle): MaintenanceInfo {
  const now = new Date()

  if (typeof cycle.eol === 'string') {
    const eolDate = new Date(cycle.eol)
    if (eolDate < now) {
      return { status: 'eol', eolDate: cycle.eol, lastRelease: cycle.latestReleaseDate }
    }
  } else if (cycle.eol === true) {
    return { status: 'eol', eolDate: null, lastRelease: cycle.latestReleaseDate }
  }

  if (cycle.latestReleaseDate) {
    const lastRelease = new Date(cycle.latestReleaseDate)
    const sixMonthsAgo = new Date()
    sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6)
    if (lastRelease < sixMonthsAgo) {
      return { status: 'warning', eolDate: typeof cycle.eol === 'string' ? cycle.eol : null, lastRelease: cycle.latestReleaseDate }
    }
  }

  return { status: 'active', eolDate: typeof cycle.eol === 'string' ? cycle.eol : null, lastRelease: cycle.latestReleaseDate }
}

export function humanizeTimeDiff(fromDate: string, toDate: string): string {
  const from = new Date(fromDate)
  const to = new Date(toDate)
  const diffMs = Math.abs(to.getTime() - from.getTime())
  const days = Math.floor(diffMs / (1000 * 60 * 60 * 24))

  if (days < 1) return '< 1 jour'
  if (days < 30) return `${days} jour${days > 1 ? 's' : ''}`
  const months = Math.floor(days / 30)
  if (months < 12) return `${months} mois`
  const years = Math.floor(months / 12)
  const remainingMonths = months % 12
  if (remainingMonths === 0) return `${years} an${years > 1 ? 's' : ''}`
  return `${years} an${years > 1 ? 's' : ''} ${remainingMonths} mois`
}

export function humanizeMs(ms: number): string {
  const days = Math.floor(ms / (1000 * 60 * 60 * 24))
  if (days < 1) return '< 1 jour'
  if (days < 30) return `${days} jour${days > 1 ? 's' : ''}`
  const months = Math.floor(days / 30)
  if (months < 12) return `${months} mois`
  const years = Math.floor(months / 12)
  const remainingMonths = months % 12
  if (remainingMonths === 0) return `${years} an${years > 1 ? 's' : ''}`
  return `${years} an${years > 1 ? 's' : ''} ${remainingMonths} mois`
}

export function msUrgency(ms: number): 'fresh' | 'moderate' | 'outdated' {
  const months = ms / (1000 * 60 * 60 * 24 * 30)
  if (months < 6) return 'fresh'
  if (months < 24) return 'moderate'
  return 'outdated'
}

export function isUpToDateOrAhead(versionReleaseDate: string, ltsReleaseDate: string): boolean {
  return new Date(versionReleaseDate).getTime() >= new Date(ltsReleaseDate).getTime()
}

export function isVersionUpToDate(frameworkVersion: string, latestLts: string): boolean {
  const installedParts = frameworkVersion.split('.').map(Number)
  const ltsParts = latestLts.split('.').map(Number)
  const maxLen = Math.max(installedParts.length, ltsParts.length)

  for (let i = 0; i < maxLen; i++) {
    const installed = installedParts[i] ?? 0
    const lts = ltsParts[i] ?? 0
    if (installed > lts) return true
    if (installed < lts) return false
  }

  return true
}


export function patchGap(frameworkVersion: string, latestLts: string): number | null {
  const iParts = frameworkVersion.split('.').map(Number)
  const ltsParts = latestLts.split('.').map(Number)

  if (iParts[0] !== ltsParts[0]) return null
  if ((iParts[1] ?? 0) !== (ltsParts[1] ?? 0)) return null

  const iPatch = iParts[2] ?? 0
  const ltsPatch = ltsParts[2] ?? 0

  return ltsPatch - iPatch
}

export function ltsUrgency(fromDate: string, toDate: string): 'fresh' | 'moderate' | 'outdated' {
  const diffMs = Math.abs(new Date(toDate).getTime() - new Date(fromDate).getTime())
  const months = diffMs / (1000 * 60 * 60 * 24 * 30)
  if (months < 6) return 'fresh'
  if (months < 24) return 'moderate'
  return 'outdated'
}

export function useFrameworkLts() {
  const ltsData = ref<Map<string, LtsInfo | null>>(new Map())
  const loading = ref(false)

  async function loadForFrameworks(frameworks: string[]) {
    const resolved = [...new Set(frameworks.map(resolveFramework).filter(f => FRAMEWORK_MAP[f]))]
    if (resolved.length === 0) return

    loading.value = true
    const results = await Promise.all(resolved.map(f => fetchLtsInfo(f).then(info => [f, info] as const)))
    for (const [framework, info] of results) {
      ltsData.value.set(framework, info)
    }
    loading.value = false
  }

  function getLtsInfo(framework: string): LtsInfo | null {
    const resolved = resolveFramework(framework)
    return ltsData.value.get(resolved) ?? null
  }

  function getVersionReleaseDate(framework: string, frameworkVersion: string): string | null {
    const info = getLtsInfo(framework)
    if (!info) return null
    return findCycleReleaseDate(info.cycles, frameworkVersion)
  }

  function getVersionMaintenanceStatus(framework: string, frameworkVersion: string): MaintenanceInfo | null {
    const info = getLtsInfo(framework)
    if (!info) return null
    const match = findCycle(info.cycles, frameworkVersion)
    if (!match) return null

    if (match.isFallback) {
      return { status: 'eol', eolDate: null, lastRelease: match.cycle.latestReleaseDate }
    }

    return getMaintenanceStatus(match.cycle)
  }

  return { ltsData, loading, loadForFrameworks, getLtsInfo, getVersionReleaseDate, getVersionMaintenanceStatus }
}
