import jsPDF from 'jspdf';
import autoTable, { type UserOptions } from 'jspdf-autotable';

export type RGB = [number, number, number];

export const PDF_COLORS = {
  primary: [59, 130, 246] as RGB,
  success: [34, 197, 94] as RGB,
  warning: [234, 179, 8] as RGB,
  danger: [239, 68, 68] as RGB,
  muted: [148, 163, 184] as RGB,
  dark: [30, 41, 59] as RGB,
  bg: [248, 250, 252] as RGB,
};

export const PDF_MARGIN = 15;

export interface PdfGapStats {
  cumulated: string;
  average: string;
  median: string;
}

export function createPdfDocument(orientation: 'landscape' | 'portrait' = 'landscape'): jsPDF {
  return new jsPDF({ orientation, unit: 'mm', format: 'a4' });
}

export function addPdfHeader(doc: jsPDF, subtitle: string): number {
  const pageWidth = doc.internal.pageSize.getWidth();
  let y = 15;

  doc.setFontSize(20);
  doc.setTextColor(...PDF_COLORS.dark);
  doc.text('Monark', PDF_MARGIN, y);

  doc.setFontSize(10);
  doc.setTextColor(...PDF_COLORS.muted);
  doc.text(`${subtitle} — ${new Date().toLocaleDateString('fr-FR')}`, pageWidth - PDF_MARGIN, y, {
    align: 'right',
  });
  y += 10;

  doc.setDrawColor(...PDF_COLORS.primary);
  doc.setLineWidth(0.5);
  doc.line(PDF_MARGIN, y, pageWidth - PDF_MARGIN, y);
  y += 8;

  return y;
}

export function addPdfHealthBar(
  doc: jsPDF,
  y: number,
  label: string,
  percent: number,
  badges: string[],
): number {
  const pageWidth = doc.internal.pageSize.getWidth();
  const contentWidth = pageWidth - PDF_MARGIN * 2;

  doc.setFontSize(12);
  doc.setTextColor(...PDF_COLORS.dark);
  doc.text(label, PDF_MARGIN, y);
  y += 6;

  const barHeight = 5;
  doc.setFillColor(...PDF_COLORS.bg);
  doc.roundedRect(PDF_MARGIN, y, contentWidth, barHeight, 2, 2, 'F');
  doc.setFillColor(...PDF_COLORS.success);
  const filledWidth = contentWidth * (percent / 100);
  if (filledWidth > 0) {
    doc.roundedRect(PDF_MARGIN, y, filledWidth, barHeight, 2, 2, 'F');
  }
  y += barHeight + 3;

  doc.setFontSize(9);
  if (badges.length > 0) {
    doc.setTextColor(...PDF_COLORS.danger);
    doc.text(badges.join('   •   '), PDF_MARGIN, y);
    y += 5;
  }
  y += 4;

  return y;
}

export function addPdfGapStats(doc: jsPDF, y: number, gapStats: PdfGapStats): number {
  const pageWidth = doc.internal.pageSize.getWidth();
  const cardW = (pageWidth - PDF_MARGIN * 2 - 10) / 3;
  const labels = ['Ecart cumule', 'Moyenne', 'Mediane'];
  const values = [gapStats.cumulated, gapStats.average, gapStats.median];

  for (let i = 0; i < 3; i++) {
    const cx = PDF_MARGIN + i * (cardW + 5);
    doc.setFillColor(...PDF_COLORS.bg);
    doc.roundedRect(cx, y, cardW, 14, 3, 3, 'F');
    doc.setDrawColor(220, 220, 220);
    doc.roundedRect(cx, y, cardW, 14, 3, 3, 'S');

    doc.setFontSize(7);
    doc.setTextColor(...PDF_COLORS.muted);
    doc.text(labels[i], cx + cardW / 2, y + 5, { align: 'center' });

    doc.setFontSize(11);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(...PDF_COLORS.dark);
    doc.text(values[i], cx + cardW / 2, y + 11, { align: 'center' });
    doc.setFont('helvetica', 'normal');
  }

  return y + 20;
}

export function addPdfFooter(doc: jsPDF): void {
  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const pageCount = doc.getNumberOfPages();

  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i);
    doc.setFontSize(8);
    doc.setTextColor(...PDF_COLORS.muted);
    doc.text(`Page ${i}/${pageCount}`, pageWidth - PDF_MARGIN, pageHeight - 8, {
      align: 'right',
    });
    doc.text(`Généré le ${new Date().toLocaleString('fr-FR')}`, PDF_MARGIN, pageHeight - 8);
  }
}

export function getPdfTableStyles(): Pick<UserOptions, 'margin' | 'styles' | 'headStyles'> {
  return {
    margin: { left: PDF_MARGIN, right: PDF_MARGIN },
    styles: { fontSize: 8, cellPadding: 2 },
    headStyles: {
      fillColor: PDF_COLORS.dark,
      textColor: [255, 255, 255],
      fontStyle: 'bold',
    },
  };
}

export function buildGroupBoundaries<T>(
  sortedRows: T[],
  keyFn: (row: T) => string,
): { boundaries: Set<number>; rowGroupIndex: number[] } {
  const boundaries = new Set<number>();
  for (let i = 1; i < sortedRows.length; i++) {
    if (keyFn(sortedRows[i]) !== keyFn(sortedRows[i - 1])) {
      boundaries.add(i);
    }
  }

  let groupIndex = 0;
  const rowGroupIndex = sortedRows.map((_, i) => {
    if (boundaries.has(i)) groupIndex++;
    return groupIndex;
  });

  return { boundaries, rowGroupIndex };
}

export function drawGroupBoundary(
  doc: jsPDF,
  boundaries: Set<number>,
  data: { section: string; row: { index: number }; cell: { x: number; y: number; width: number } },
): void {
  if (data.section !== 'body') return;
  if (boundaries.has(data.row.index)) {
    doc.setDrawColor(200, 200, 200);
    doc.setLineWidth(0.3);
    doc.line(data.cell.x, data.cell.y, data.cell.x + data.cell.width, data.cell.y);
  }
}

export function applyAlternateGroupShading(
  rowGroupIndex: number[],
  data: { section: string; row: { index: number }; cell: { styles: { fillColor: RGB | number[] } } },
): void {
  if (data.section !== 'body') return;
  if (rowGroupIndex[data.row.index] % 2 === 1) {
    data.cell.styles.fillColor = PDF_COLORS.bg;
  }
}

export { autoTable };
