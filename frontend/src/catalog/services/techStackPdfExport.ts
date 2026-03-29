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
  PDF_MARGIN,
  type PdfGapStats,
} from '@/shared/utils/pdfExport';

interface StackRow {
  project: string;
  language: string;
  framework: string;
  version: string;
  latestLts: string;
  ltsGap: string;
  status: string;
  releaseDate: string;
}

interface HealthData {
  total: number;
  active: number;
  eol: number;
  warning: number;
  percent: number;
}

interface ProviderAgg {
  name: string;
  type: string;
  projectCount: number;
  frameworks: { name: string; min: string; max: string }[];
}

export function exportTechStacksPdf(
  rows: StackRow[],
  health: HealthData | null,
  providers: ProviderAgg[],
  gapStats?: PdfGapStats | null,
): void {
  const doc = createPdfDocument('landscape');
  const pageWidth = doc.internal.pageSize.getWidth();
  let y = addPdfHeader(doc, 'Rapport Stacks Techniques');

  if (health) {
    const badges: string[] = [];
    if (health.eol > 0) badges.push(`${health.eol} non maintenu(s)`);
    if (health.warning > 0) badges.push(`${health.warning} inactif(s)`);

    y = addPdfHealthBar(
      doc,
      y,
      `Score de santé : ${health.percent}% à jour  (${health.active}/${health.total})`,
      health.percent,
      badges,
    );
  }

  if (gapStats) {
    y = addPdfGapStats(doc, y, gapStats);
  }

  if (providers.length > 0) {
    y = drawProviderCards(doc, y, providers, pageWidth);
  }

  const sortedRows = [...rows].sort((a, b) => a.project.localeCompare(b.project));
  const { boundaries, rowGroupIndex } = buildGroupBoundaries(sortedRows, (r) => r.project);

  const head = [
    ['Projet', 'Langage', 'Framework', 'Version', 'Dernière LTS', 'Écart', 'Statut', 'Release'],
  ];
  const body = sortedRows.map((r, i) => {
    const showProject = i === 0 || sortedRows[i - 1].project !== r.project;
    return [
      showProject ? r.project : '',
      r.language,
      r.framework,
      r.version,
      r.latestLts,
      r.ltsGap,
      r.status,
      r.releaseDate,
    ];
  });

  autoTable(doc, {
    head,
    body,
    startY: y,
    ...getPdfTableStyles(),
    columnStyles: {
      0: { fontStyle: 'bold' },
      5: { cellWidth: 28 },
      6: { cellWidth: 25 },
    },
    didParseCell(data) {
      applyAlternateGroupShading(rowGroupIndex, data);

      if (data.section !== 'body') return;

      if (data.column.index === 6) {
        const val = String(data.cell.raw);
        if (val === 'Non maintenu') data.cell.styles.textColor = PDF_COLORS.danger;
        else if (val === 'Inactif') data.cell.styles.textColor = PDF_COLORS.warning;
        else if (val === 'OK') data.cell.styles.textColor = PDF_COLORS.success;
      }

      if (data.column.index === 5) {
        const val = String(data.cell.raw);
        if (val === 'À jour') {
          data.cell.styles.textColor = PDF_COLORS.success;
        } else if (val.includes('patch')) {
          data.cell.styles.textColor = PDF_COLORS.warning;
        } else if (val.includes('an') || val.includes('year')) {
          data.cell.styles.textColor = PDF_COLORS.danger;
        } else if (val.includes('mois') || val.includes('month')) {
          data.cell.styles.textColor = PDF_COLORS.warning;
        } else if (val !== '—') {
          data.cell.styles.textColor = PDF_COLORS.success;
        }
      }
    },
    didDrawCell(data) {
      drawGroupBoundary(doc, boundaries, data);
    },
  });

  addPdfFooter(doc);
  doc.save('stacks-techniques.pdf');
}

function drawProviderCards(
  doc: ReturnType<typeof createPdfDocument>,
  y: number,
  providers: ProviderAgg[],
  pageWidth: number,
): number {
  const contentWidth = pageWidth - PDF_MARGIN * 2;
  const cardWidth =
    providers.length === 1
      ? contentWidth
      : Math.min((contentWidth - (providers.length - 1) * 5) / providers.length, 130);

  doc.setFontSize(11);
  doc.setTextColor(...PDF_COLORS.dark);
  doc.text('Agrégation par provider', PDF_MARGIN, y);
  y += 7;

  let cardX = PDF_MARGIN;
  const cardStartY = y;
  let maxCardHeight = 0;

  for (const prov of providers) {
    const cardY = cardStartY;
    let innerY = cardY + 6;

    const fwLines = prov.frameworks.map((fw) => {
      const range = fw.min === fw.max ? fw.min : `${fw.min} -> ${fw.max}`;
      return `${fw.name}:  ${range}`;
    });
    const cardHeight = 12 + fwLines.length * 4.5;

    doc.setFillColor(...PDF_COLORS.bg);
    doc.roundedRect(cardX, cardY, cardWidth, cardHeight, 3, 3, 'F');
    doc.setDrawColor(220, 220, 220);
    doc.roundedRect(cardX, cardY, cardWidth, cardHeight, 3, 3, 'S');

    doc.setFontSize(9);
    doc.setTextColor(...PDF_COLORS.primary);
    doc.setFont('helvetica', 'bold');
    doc.text(`${prov.name}`, cardX + 4, innerY);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(...PDF_COLORS.muted);
    doc.text(
      `${prov.type} — ${prov.projectCount} projet(s)`,
      cardX + 4 + doc.getTextWidth(prov.name) + 3,
      innerY,
    );
    innerY += 5;

    doc.setFontSize(8);
    doc.setTextColor(...PDF_COLORS.dark);
    for (const line of fwLines) {
      doc.text(line, cardX + 6, innerY);
      innerY += 4.5;
    }

    if (cardHeight > maxCardHeight) maxCardHeight = cardHeight;
    cardX += cardWidth + 5;
  }

  return cardStartY + maxCardHeight + 6;
}
