import {
  addPdfFooter,
  addPdfHeader,
  applyAlternateGroupShading,
  autoTable,
  buildGroupBoundaries,
  createPdfDocument,
  drawGroupBoundary,
  getPdfTableStyles,
  PDF_COLORS,
} from '@/shared/utils/pdfExport';

interface FrameworkRow {
  project: string;
  name: string;
  version: string;
  latestLts: string;
  ltsGap: string;
  status: string;
}

export function exportFrameworksPdf(rows: FrameworkRow[]): void {
  const doc = createPdfDocument('landscape');
  const y = addPdfHeader(doc, 'Rapport Frameworks');

  const sortedRows = [...rows].sort((a, b) => a.project.localeCompare(b.project));
  const { boundaries, rowGroupIndex } = buildGroupBoundaries(sortedRows, (r) => r.project);

  const head = [['Projet', 'Framework', 'Version', 'Derniere LTS', 'Ecart LTS', 'Statut']];
  const body = sortedRows.map((r, i) => {
    const showProject = i === 0 || sortedRows[i - 1].project !== r.project;
    return [showProject ? r.project : '', r.name, r.version, r.latestLts, r.ltsGap, r.status];
  });

  const baseStyles = getPdfTableStyles();

  autoTable(doc, {
    head,
    body,
    startY: y,
    ...baseStyles,
    styles: { fontSize: 8, cellPadding: 2 },
    columnStyles: {
      0: { fontStyle: 'bold', cellWidth: 50 },
      5: { cellWidth: 30, halign: 'center' },
    },
    didParseCell(data) {
      applyAlternateGroupShading(rowGroupIndex, data);

      if (data.section !== 'body') return;

      if (data.column.index === 5) {
        const val = String(data.cell.raw);
        if (val === 'Non maintenu') data.cell.styles.textColor = PDF_COLORS.danger;
        else if (val === 'Inactif') data.cell.styles.textColor = PDF_COLORS.warning;
        else if (val === 'OK') data.cell.styles.textColor = PDF_COLORS.success;
      }
    },
    didDrawCell(data) {
      drawGroupBoundary(doc, boundaries, data);
    },
  });

  addPdfFooter(doc);
  doc.save('frameworks.pdf');
}
