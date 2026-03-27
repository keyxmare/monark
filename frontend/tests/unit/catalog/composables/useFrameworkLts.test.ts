import { describe, expect, it } from 'vitest'
import { getMaintenanceStatus, humanizeTimeDiff, isVersionUpToDate, ltsUrgency, resolveFramework, useFrameworkLts } from '@/catalog/composables/useFrameworkLts'

describe('humanizeTimeDiff', () => {
  it('returns < 1 jour for same day', () => {
    expect(humanizeTimeDiff('2026-03-18', '2026-03-18')).toBe('< 1 jour')
  })

  it('returns days for short periods', () => {
    expect(humanizeTimeDiff('2026-03-01', '2026-03-15')).toBe('14 jours')
  })

  it('returns singular jour', () => {
    expect(humanizeTimeDiff('2026-03-01', '2026-03-02')).toBe('1 jour')
  })

  it('returns months for medium periods', () => {
    expect(humanizeTimeDiff('2026-01-01', '2026-04-01')).toBe('3 mois')
  })

  it('returns years for long periods', () => {
    const result = humanizeTimeDiff('2020-01-01', '2026-01-01')
    expect(result).toContain('6 an')
  })

  it('returns years and months for mixed periods', () => {
    expect(humanizeTimeDiff('2024-01-01', '2026-07-01')).toBe('2 ans 6 mois')
  })

  it('returns singular an', () => {
    expect(humanizeTimeDiff('2025-01-01', '2026-01-01')).toBe('1 an')
  })

  it('works regardless of date order', () => {
    expect(humanizeTimeDiff('2026-03-15', '2026-03-01')).toBe('14 jours')
  })
})

describe('ltsUrgency', () => {
  it('returns fresh for < 6 months', () => {
    expect(ltsUrgency('2026-01-01', '2026-04-01')).toBe('fresh')
  })

  it('returns moderate for 6-24 months', () => {
    expect(ltsUrgency('2025-01-01', '2026-01-01')).toBe('moderate')
  })

  it('returns outdated for > 24 months', () => {
    expect(ltsUrgency('2020-01-01', '2026-01-01')).toBe('outdated')
  })
})

describe('isVersionUpToDate', () => {
  it('returns true when same version', () => {
    expect(isVersionUpToDate('3.5.13', '3.5.13')).toBe(true)
  })

  it('returns false when same major.minor but lower patch', () => {
    expect(isVersionUpToDate('3.5.0', '3.5.13')).toBe(false)
  })

  it('returns true when same major.minor.patch', () => {
    expect(isVersionUpToDate('3.5.13', '3.5.13')).toBe(true)
  })

  it('returns true when higher minor', () => {
    expect(isVersionUpToDate('3.6.0', '3.5.13')).toBe(true)
  })

  it('returns true when higher major', () => {
    expect(isVersionUpToDate('8.0.3', '7.4.7')).toBe(true)
  })

  it('returns false when lower major', () => {
    expect(isVersionUpToDate('6.4.0', '7.4.7')).toBe(false)
  })

  it('returns false when lower minor same major', () => {
    expect(isVersionUpToDate('3.4.0', '3.5.13')).toBe(false)
  })

  it('returns false when version without minor vs LTS with minor', () => {
    expect(isVersionUpToDate('3', '3.5.13')).toBe(false)
  })

  it('returns true when version without minor matches LTS major', () => {
    expect(isVersionUpToDate('4', '3.5.13')).toBe(true)
  })
})

describe('resolveFramework', () => {
  it('resolves Symfony1 to Symfony', () => {
    expect(resolveFramework('Symfony1')).toBe('Symfony')
  })

  it('resolves AngularJS to Angular', () => {
    expect(resolveFramework('AngularJS')).toBe('Angular')
  })

  it('returns framework as-is when no alias exists', () => {
    expect(resolveFramework('Vue')).toBe('Vue')
  })

  it('returns unknown frameworks unchanged', () => {
    expect(resolveFramework('SomeUnknown')).toBe('SomeUnknown')
  })
})

describe('useFrameworkLts with Angular', () => {
  it('loads Angular LTS data from endoflife.date', async () => {
    const { loadForFrameworks, getLtsInfo } = useFrameworkLts()
    await loadForFrameworks(['Angular'])
  })

  it('resolves AngularJS alias to Angular for LTS lookup', () => {
    expect(resolveFramework('AngularJS')).toBe('Angular')
  })
})

describe('getMaintenanceStatus', () => {
  it('returns eol when eol date is in the past', () => {
    const cycle = {
      cycle: '1.4',
      releaseDate: '2009-11-30',
      eol: '2012-11-30',
      latest: '1.4.20',
      latestReleaseDate: '2012-10-15',
      lts: false,
    }
    const result = getMaintenanceStatus(cycle)
    expect(result.status).toBe('eol')
    expect(result.eolDate).toBe('2012-11-30')
  })

  it('returns eol when eol is boolean true', () => {
    const cycle = {
      cycle: '2.0',
      releaseDate: '2015-01-01',
      eol: true,
      latest: '2.0.5',
      latestReleaseDate: '2016-03-01',
      lts: false,
    }
    const result = getMaintenanceStatus(cycle)
    expect(result.status).toBe('eol')
    expect(result.eolDate).toBeNull()
  })

  it('returns warning when last release is older than 6 months', () => {
    const sevenMonthsAgo = new Date()
    sevenMonthsAgo.setMonth(sevenMonthsAgo.getMonth() - 7)

    const cycle = {
      cycle: '3.0',
      releaseDate: '2024-01-01',
      eol: '2028-01-01',
      latest: '3.0.10',
      latestReleaseDate: sevenMonthsAgo.toISOString().split('T')[0],
      lts: false,
    }
    const result = getMaintenanceStatus(cycle)
    expect(result.status).toBe('warning')
  })

  it('returns active when eol is in the future and recent release', () => {
    const recentDate = new Date()
    recentDate.setMonth(recentDate.getMonth() - 1)

    const cycle = {
      cycle: '7.4',
      releaseDate: '2025-11-30',
      eol: '2029-11-30',
      latest: '7.4.7',
      latestReleaseDate: recentDate.toISOString().split('T')[0],
      lts: true,
    }
    const result = getMaintenanceStatus(cycle)
    expect(result.status).toBe('active')
    expect(result.eolDate).toBe('2029-11-30')
  })

  it('returns eol for version older than any tracked cycle (fallback)', () => {
    const { getVersionMaintenanceStatus, ltsData } = useFrameworkLts()
    ltsData.value.set('Symfony', {
      latestLts: '7.4.7',
      releaseDate: '2026-02-01',
      cycles: [
        { cycle: '7.4', releaseDate: '2025-11-30', eol: '2029-11-30', latest: '7.4.7', latestReleaseDate: '2026-02-01', lts: true },
        { cycle: '6.4', releaseDate: '2023-11-29', eol: '2027-11-30', latest: '6.4.20', latestReleaseDate: '2026-01-15', lts: true },
        { cycle: '2.0', releaseDate: '2011-09-01', eol: '2013-11-30', latest: '2.0.25', latestReleaseDate: '2013-10-01', lts: false },
      ],
    })

    const result = getVersionMaintenanceStatus('Symfony', '1.4.9')
    expect(result).not.toBeNull()
    expect(result!.status).toBe('eol')
  })

  it('returns active when eol is boolean false and recent release', () => {
    const recentDate = new Date()
    recentDate.setMonth(recentDate.getMonth() - 2)

    const cycle = {
      cycle: '3.5',
      releaseDate: '2025-06-01',
      eol: false,
      latest: '3.5.13',
      latestReleaseDate: recentDate.toISOString().split('T')[0],
      lts: false,
    }
    const result = getMaintenanceStatus(cycle)
    expect(result.status).toBe('active')
    expect(result.eolDate).toBeNull()
  })
})
