import {
  createPdfDocument,
  addPdfHeader,
  addPdfHealthBar,
  addPdfGapStats,
  addPdfFooter,
  getPdfTableStyles,
  buildGroupBoundaries,
  drawGroupBoundary,
  applyAlternateGroupShading,
  autoTable,
  PDF_COLORS,
  type PdfGapStats,
} from '@/shared/utils/pdfExport';

interface DepRow {
  name: string;
  project: string;
  currentVersion: string;
  latestVersion: string;
  gap: string;
  packageManager: string;
  type: string;
  status: string;
  vulnerabilities: number;
}

interface HealthData {
  total: number;
  upToDate: number;
  outdated: number;
  totalVulns: number;
  percent: number;
}

export function exportDependenciesPdf(
  rows: DepRow[],
  health: HealthData | null,
  gapStats?: PdfGapStats | null,
): void {
  const doc = createPdfDocument('landscape');
  let y = addPdfHeader(doc, 'Rapport Dependances');

  if (health) {
    const badges: string[] = [];
    if (health.outdated > 0) badges.push(`${health.outdated} obsolete(s)`);
    if (health.totalVulns > 0) badges.push(`${health.totalVulns} vulnerabilite(s)`);

    y = addPdfHealthBar(
      doc,
      y,
      `Score de sante : ${health.percent}% a jour  (${health.upToDate}/${health.total})`,
      health.percent,
      badges,
    );
  }

  if (gapStats) {
    y = addPdfGapStats(doc, y, gapStats);
  }

  const sortedRows = [...rows].sort((a, b) => a.name.localeCompare(b.name));
  const { boundaries, rowGroupIndex } = buildGroupBoundaries(sortedRows, (r) => r.name);

  const head = [
    [
      'Dependance',
      'Projet',
      'Version',
      'Derniere',
      'Ecart',
      'Pkg Manager',
      'Type',
      'Statut',
      'Vulns',
    ],
  ];
  const body = sortedRows.map((r, i) => {
    const showName = i === 0 || sortedRows[i - 1].name !== r.name;
    return [
      showName ? r.name : '',
      r.project,
      r.currentVersion,
      r.latestVersion,
      r.gap,
      r.packageManager,
      r.type,
      r.status,
      String(r.vulnerabilities),
    ];
  });

  const baseStyles = getPdfTableStyles();

  autoTable(doc, {
    head,
    body,
    startY: y,
    ...baseStyles,
    styles: { fontSize: 7, cellPadding: 1.5 },
    columnStyles: {
      0: { fontStyle: 'bold', cellWidth: 40 },
      4: { cellWidth: 22 },
      8: { cellWidth: 12, halign: 'center' },
    },
    didParseCell(data) {
      applyAlternateGroupShading(rowGroupIndex, data);

      if (data.section !== 'body') return;

      if (data.column.index === 7) {
        const val = String(data.cell.raw);
        if (val === 'Obsolete') data.cell.styles.textColor = PDF_COLORS.danger;
        else if (val === 'A jour') data.cell.styles.textColor = PDF_COLORS.success;
      }

      if (data.column.index === 4) {
        const val = String(data.cell.raw);
        if (val === 'A jour') data.cell.styles.textColor = PDF_COLORS.success;
        else if (val.includes('an') || val.includes('year'))
          data.cell.styles.textColor = PDF_COLORS.danger;
        else if (val.includes('mois') || val.includes('month'))
          data.cell.styles.textColor = PDF_COLORS.warning;
        else if (val !== '-') data.cell.styles.textColor = PDF_COLORS.success;
      }

      if (data.column.index === 8) {
        const val = Number(data.cell.raw);
        if (val > 3) data.cell.styles.textColor = PDF_COLORS.danger;
        else if (val > 0) data.cell.styles.textColor = PDF_COLORS.warning;
      }
    },
    didDrawCell(data) {
      drawGroupBoundary(doc, boundaries, data);
    },
  });

  addPdfFooter(doc);
  doc.save('dependances.pdf');
}
