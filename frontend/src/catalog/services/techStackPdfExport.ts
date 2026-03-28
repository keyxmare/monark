import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

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

interface GapStatsData {
  cumulated: string;
  average: string;
  median: string;
}

const COLORS = {
  primary: [59, 130, 246] as [number, number, number],
  success: [34, 197, 94] as [number, number, number],
  warning: [234, 179, 8] as [number, number, number],
  danger: [239, 68, 68] as [number, number, number],
  muted: [148, 163, 184] as [number, number, number],
  dark: [30, 41, 59] as [number, number, number],
  bg: [248, 250, 252] as [number, number, number],
};

export function exportTechStacksPdf(
  rows: StackRow[],
  health: HealthData | null,
  providers: ProviderAgg[],
  gapStats?: GapStatsData | null,
): void {
  const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
  const pageWidth = doc.internal.pageSize.getWidth();
  let y = 15;

  doc.setFontSize(20);
  doc.setTextColor(...COLORS.dark);
  doc.text('Monark', 15, y);

  doc.setFontSize(10);
  doc.setTextColor(...COLORS.muted);
  doc.text(
    `Rapport Stacks Techniques — ${new Date().toLocaleDateString('fr-FR')}`,
    pageWidth - 15,
    y,
    { align: 'right' },
  );
  y += 10;

  doc.setDrawColor(...COLORS.primary);
  doc.setLineWidth(0.5);
  doc.line(15, y, pageWidth - 15, y);
  y += 8;

  if (health) {
    const contentWidth = pageWidth - 30;

    doc.setFontSize(12);
    doc.setTextColor(...COLORS.dark);
    doc.text(
      `Score de santé : ${health.percent}% à jour  (${health.active}/${health.total})`,
      15,
      y,
    );
    y += 6;

    const barHeight = 5;
    doc.setFillColor(...COLORS.bg);
    doc.roundedRect(15, y, contentWidth, barHeight, 2, 2, 'F');
    doc.setFillColor(...COLORS.success);
    const filledWidth = contentWidth * (health.percent / 100);
    if (filledWidth > 0) {
      doc.roundedRect(15, y, filledWidth, barHeight, 2, 2, 'F');
    }
    y += barHeight + 3;

    doc.setFontSize(9);
    const badges: string[] = [];
    if (health.eol > 0) badges.push(`${health.eol} non maintenu(s)`);
    if (health.warning > 0) badges.push(`${health.warning} inactif(s)`);
    if (badges.length > 0) {
      doc.setTextColor(...COLORS.danger);
      doc.text(badges.join('   •   '), 15, y);
      y += 5;
    }

    y += 4;
  }

  if (gapStats) {
    const cardW = (pageWidth - 30 - 10) / 3;
    const labels = ['Ecart cumule', 'Moyenne', 'Mediane'];
    const values = [gapStats.cumulated, gapStats.average, gapStats.median];

    for (let i = 0; i < 3; i++) {
      const cx = 15 + i * (cardW + 5);
      doc.setFillColor(...COLORS.bg);
      doc.roundedRect(cx, y, cardW, 14, 3, 3, 'F');
      doc.setDrawColor(220, 220, 220);
      doc.roundedRect(cx, y, cardW, 14, 3, 3, 'S');

      doc.setFontSize(7);
      doc.setTextColor(...COLORS.muted);
      doc.text(labels[i], cx + cardW / 2, y + 5, { align: 'center' });

      doc.setFontSize(11);
      doc.setFont('helvetica', 'bold');
      doc.setTextColor(...COLORS.dark);
      doc.text(values[i], cx + cardW / 2, y + 11, { align: 'center' });
      doc.setFont('helvetica', 'normal');
    }
    y += 20;
  }

  if (providers.length > 0) {
    const contentWidth = pageWidth - 30;
    const cardWidth =
      providers.length === 1
        ? contentWidth
        : Math.min((contentWidth - (providers.length - 1) * 5) / providers.length, 130);

    doc.setFontSize(11);
    doc.setTextColor(...COLORS.dark);
    doc.text('Agrégation par provider', 15, y);
    y += 7;

    let cardX = 15;
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

      doc.setFillColor(...COLORS.bg);
      doc.roundedRect(cardX, cardY, cardWidth, cardHeight, 3, 3, 'F');
      doc.setDrawColor(220, 220, 220);
      doc.roundedRect(cardX, cardY, cardWidth, cardHeight, 3, 3, 'S');

      doc.setFontSize(9);
      doc.setTextColor(...COLORS.primary);
      doc.setFont('helvetica', 'bold');
      doc.text(`${prov.name}`, cardX + 4, innerY);
      doc.setFont('helvetica', 'normal');
      doc.setTextColor(...COLORS.muted);
      doc.text(
        `${prov.type} — ${prov.projectCount} projet(s)`,
        cardX + 4 + doc.getTextWidth(prov.name) + 3,
        innerY,
      );
      innerY += 5;

      doc.setFontSize(8);
      doc.setTextColor(...COLORS.dark);
      for (const line of fwLines) {
        doc.text(line, cardX + 6, innerY);
        innerY += 4.5;
      }

      if (cardHeight > maxCardHeight) maxCardHeight = cardHeight;
      cardX += cardWidth + 5;
    }

    y = cardStartY + maxCardHeight + 6;
  }

  const head = [
    ['Projet', 'Langage', 'Framework', 'Version', 'Dernière LTS', 'Écart', 'Statut', 'Release'],
  ];

  const sortedRows = [...rows].sort((a, b) => a.project.localeCompare(b.project));
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

  const projectBoundaries = new Set<number>();
  for (let i = 1; i < sortedRows.length; i++) {
    if (sortedRows[i].project !== sortedRows[i - 1].project) {
      projectBoundaries.add(i);
    }
  }

  let groupIndex = 0;
  const rowGroupIndex = sortedRows.map((_, i) => {
    if (projectBoundaries.has(i)) groupIndex++;
    return groupIndex;
  });

  autoTable(doc, {
    head,
    body,
    startY: y,
    margin: { left: 15, right: 15 },
    styles: { fontSize: 8, cellPadding: 2 },
    headStyles: { fillColor: COLORS.dark, textColor: [255, 255, 255], fontStyle: 'bold' },
    columnStyles: {
      0: { fontStyle: 'bold' },
      5: { cellWidth: 28 },
      6: { cellWidth: 25 },
    },
    didParseCell(data) {
      if (data.section !== 'body') return;

      if (rowGroupIndex[data.row.index] % 2 === 1) {
        data.cell.styles.fillColor = COLORS.bg;
      }

      if (data.column.index === 6) {
        const val = String(data.cell.raw);
        if (val === 'Non maintenu') data.cell.styles.textColor = COLORS.danger;
        else if (val === 'Inactif') data.cell.styles.textColor = COLORS.warning;
        else if (val === 'OK') data.cell.styles.textColor = COLORS.success;
      }

      if (data.column.index === 5) {
        const val = String(data.cell.raw);
        if (val === 'À jour') {
          data.cell.styles.textColor = COLORS.success;
        } else if (val.includes('patch')) {
          data.cell.styles.textColor = COLORS.warning;
        } else if (val.includes('an') || val.includes('year')) {
          data.cell.styles.textColor = COLORS.danger;
        } else if (val.includes('mois') || val.includes('month')) {
          data.cell.styles.textColor = COLORS.warning;
        } else if (val !== '—') {
          data.cell.styles.textColor = COLORS.success;
        }
      }
    },
    didDrawCell(data) {
      if (data.section !== 'body') return;
      if (projectBoundaries.has(data.row.index)) {
        doc.setDrawColor(200, 200, 200);
        doc.setLineWidth(0.3);
        doc.line(data.cell.x, data.cell.y, data.cell.x + data.cell.width, data.cell.y);
      }
    },
  });

  const pageCount = doc.getNumberOfPages();
  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i);
    doc.setFontSize(8);
    doc.setTextColor(...COLORS.muted);
    doc.text(`Page ${i}/${pageCount}`, pageWidth - 15, doc.internal.pageSize.getHeight() - 8, {
      align: 'right',
    });
    doc.text(
      `Généré le ${new Date().toLocaleString('fr-FR')}`,
      15,
      doc.internal.pageSize.getHeight() - 8,
    );
  }

  doc.save('stacks-techniques.pdf');
}
