import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

interface DepRow {
  name: string
  project: string
  currentVersion: string
  latestVersion: string
  gap: string
  packageManager: string
  type: string
  status: string
  vulnerabilities: number
}

interface HealthData {
  total: number
  upToDate: number
  outdated: number
  totalVulns: number
  percent: number
}

interface GapStatsData {
  cumulated: string
  average: string
  median: string
}

const COLORS = {
  primary: [59, 130, 246] as [number, number, number],
  success: [34, 197, 94] as [number, number, number],
  warning: [234, 179, 8] as [number, number, number],
  danger: [239, 68, 68] as [number, number, number],
  muted: [148, 163, 184] as [number, number, number],
  dark: [30, 41, 59] as [number, number, number],
  bg: [248, 250, 252] as [number, number, number],
}

export function exportDependenciesPdf(
  rows: DepRow[],
  health: HealthData | null,
  gapStats?: GapStatsData | null,
): void {
  const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' })
  const pageWidth = doc.internal.pageSize.getWidth()
  let y = 15

  doc.setFontSize(20)
  doc.setTextColor(...COLORS.dark)
  doc.text('Monark', 15, y)

  doc.setFontSize(10)
  doc.setTextColor(...COLORS.muted)
  doc.text(`Rapport Dependances - ${new Date().toLocaleDateString('fr-FR')}`, pageWidth - 15, y, { align: 'right' })
  y += 10

  doc.setDrawColor(...COLORS.primary)
  doc.setLineWidth(0.5)
  doc.line(15, y, pageWidth - 15, y)
  y += 8

  if (health) {
    const contentWidth = pageWidth - 30

    doc.setFontSize(12)
    doc.setTextColor(...COLORS.dark)
    doc.text(`Score de sante : ${health.percent}% a jour  (${health.upToDate}/${health.total})`, 15, y)
    y += 6

    const barHeight = 5
    doc.setFillColor(...COLORS.bg)
    doc.roundedRect(15, y, contentWidth, barHeight, 2, 2, 'F')
    doc.setFillColor(...COLORS.success)
    const filledWidth = contentWidth * (health.percent / 100)
    if (filledWidth > 0) {
      doc.roundedRect(15, y, filledWidth, barHeight, 2, 2, 'F')
    }
    y += barHeight + 3

    doc.setFontSize(9)
    const badges: string[] = []
    if (health.outdated > 0) badges.push(`${health.outdated} obsolete(s)`)
    if (health.totalVulns > 0) badges.push(`${health.totalVulns} vulnerabilite(s)`)
    if (badges.length > 0) {
      doc.setTextColor(...COLORS.danger)
      doc.text(badges.join('   -   '), 15, y)
      y += 5
    }
    y += 4
  }

  if (gapStats) {
    const cardW = (pageWidth - 30 - 10) / 3
    const labels = ['Ecart cumule', 'Moyenne', 'Mediane']
    const values = [gapStats.cumulated, gapStats.average, gapStats.median]

    for (let i = 0; i < 3; i++) {
      const cx = 15 + i * (cardW + 5)
      doc.setFillColor(...COLORS.bg)
      doc.roundedRect(cx, y, cardW, 14, 3, 3, 'F')
      doc.setDrawColor(220, 220, 220)
      doc.roundedRect(cx, y, cardW, 14, 3, 3, 'S')

      doc.setFontSize(7)
      doc.setTextColor(...COLORS.muted)
      doc.text(labels[i], cx + cardW / 2, y + 5, { align: 'center' })

      doc.setFontSize(11)
      doc.setFont('helvetica', 'bold')
      doc.setTextColor(...COLORS.dark)
      doc.text(values[i], cx + cardW / 2, y + 11, { align: 'center' })
      doc.setFont('helvetica', 'normal')
    }
    y += 20
  }

  const sortedRows = [...rows].sort((a, b) => a.name.localeCompare(b.name))
  const head = [['Dependance', 'Projet', 'Version', 'Derniere', 'Ecart', 'Pkg Manager', 'Type', 'Statut', 'Vulns']]
  const body = sortedRows.map((r, i) => {
    const showName = i === 0 || sortedRows[i - 1].name !== r.name
    return [showName ? r.name : '', r.project, r.currentVersion, r.latestVersion, r.gap, r.packageManager, r.type, r.status, String(r.vulnerabilities)]
  })

  const nameBoundaries = new Set<number>()
  for (let i = 1; i < sortedRows.length; i++) {
    if (sortedRows[i].name !== sortedRows[i - 1].name) nameBoundaries.add(i)
  }

  let groupIndex = 0
  const rowGroupIndex = sortedRows.map((_, i) => {
    if (nameBoundaries.has(i)) groupIndex++
    return groupIndex
  })

  autoTable(doc, {
    head,
    body,
    startY: y,
    margin: { left: 15, right: 15 },
    styles: { fontSize: 7, cellPadding: 1.5 },
    headStyles: { fillColor: COLORS.dark, textColor: [255, 255, 255], fontStyle: 'bold' },
    columnStyles: {
      0: { fontStyle: 'bold', cellWidth: 40 },
      4: { cellWidth: 22 },
      8: { cellWidth: 12, halign: 'center' },
    },
    didParseCell(data) {
      if (data.section !== 'body') return

      if (rowGroupIndex[data.row.index] % 2 === 1) {
        data.cell.styles.fillColor = COLORS.bg
      }

      if (data.column.index === 7) {
        const val = String(data.cell.raw)
        if (val === 'Obsolete') data.cell.styles.textColor = COLORS.danger
        else if (val === 'A jour') data.cell.styles.textColor = COLORS.success
      }

      if (data.column.index === 4) {
        const val = String(data.cell.raw)
        if (val === 'A jour') data.cell.styles.textColor = COLORS.success
        else if (val.includes('an') || val.includes('year')) data.cell.styles.textColor = COLORS.danger
        else if (val.includes('mois') || val.includes('month')) data.cell.styles.textColor = COLORS.warning
        else if (val !== '-') data.cell.styles.textColor = COLORS.success
      }

      if (data.column.index === 8) {
        const val = Number(data.cell.raw)
        if (val > 3) data.cell.styles.textColor = COLORS.danger
        else if (val > 0) data.cell.styles.textColor = COLORS.warning
      }
    },
    didDrawCell(data) {
      if (data.section !== 'body') return
      if (nameBoundaries.has(data.row.index)) {
        doc.setDrawColor(200, 200, 200)
        doc.setLineWidth(0.3)
        doc.line(data.cell.x, data.cell.y, data.cell.x + data.cell.width, data.cell.y)
      }
    },
  })

  const pageCount = doc.getNumberOfPages()
  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i)
    doc.setFontSize(8)
    doc.setTextColor(...COLORS.muted)
    doc.text(`Page ${i}/${pageCount}`, pageWidth - 15, doc.internal.pageSize.getHeight() - 8, { align: 'right' })
    doc.text(`Genere le ${new Date().toLocaleString('fr-FR')}`, 15, doc.internal.pageSize.getHeight() - 8)
  }

  doc.save('dependances.pdf')
}
