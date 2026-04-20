import { describe, expect, it, vi } from 'vitest';

vi.mock('jspdf', () => {
  const mockDoc = {
    internal: {
      pageSize: {
        getWidth: () => 297,
        getHeight: () => 210,
      },
    },
    setFontSize: vi.fn(),
    setTextColor: vi.fn(),
    text: vi.fn(),
    setDrawColor: vi.fn(),
    setLineWidth: vi.fn(),
    line: vi.fn(),
    setFillColor: vi.fn(),
    roundedRect: vi.fn(),
    setFont: vi.fn(),
    setPage: vi.fn(),
    getNumberOfPages: vi.fn(() => 1),
  };

  return {
    default: vi.fn(function () {
      return mockDoc;
    }),
  };
});

vi.mock('jspdf-autotable', () => ({
  default: vi.fn(),
}));

import {
  PDF_COLORS,
  PDF_MARGIN,
  addPdfFooter,
  addPdfGapStats,
  addPdfHeader,
  addPdfHealthBar,
  applyAlternateGroupShading,
  buildGroupBoundaries,
  createPdfDocument,
  drawGroupBoundary,
  getPdfTableStyles,
} from '@/hub/shared/utils/pdfExport';

describe('pdfExport', () => {
  describe('createPdfDocument', () => {
    it('returns a jsPDF document', () => {
      const doc = createPdfDocument();
      expect(doc).toBeDefined();
      expect(doc.text).toBeDefined();
      expect(doc.setFontSize).toBeDefined();
    });

    it('accepts portrait orientation', () => {
      const doc = createPdfDocument('portrait');
      expect(doc).toBeDefined();
    });

    it('defaults to landscape orientation', () => {
      const doc = createPdfDocument();
      expect(doc).toBeDefined();
    });
  });

  describe('addPdfHeader', () => {
    it('returns a y position greater than initial offset', () => {
      const doc = createPdfDocument();
      const y = addPdfHeader(doc, 'Test Report');
      expect(y).toBeGreaterThan(15);
    });

    it('calls text with title and subtitle', () => {
      const doc = createPdfDocument();
      addPdfHeader(doc, 'My Subtitle');
      expect(doc.text).toHaveBeenCalledWith('Monark', PDF_MARGIN, 15);
    });

    it('returns y = 33 (15 + 10 + 8)', () => {
      const doc = createPdfDocument();
      const y = addPdfHeader(doc, 'Sub');
      expect(y).toBe(33);
    });
  });

  describe('buildGroupBoundaries', () => {
    it('returns empty boundaries for single-group data', () => {
      const rows = [{ cat: 'A' }, { cat: 'A' }, { cat: 'A' }];
      const result = buildGroupBoundaries(rows, (r) => r.cat);
      expect(result.boundaries.size).toBe(0);
      expect(result.rowGroupIndex).toEqual([0, 0, 0]);
    });

    it('identifies boundaries between different groups', () => {
      const rows = [{ cat: 'A' }, { cat: 'A' }, { cat: 'B' }, { cat: 'B' }, { cat: 'C' }];
      const result = buildGroupBoundaries(rows, (r) => r.cat);
      expect(result.boundaries.has(2)).toBe(true);
      expect(result.boundaries.has(4)).toBe(true);
      expect(result.boundaries.size).toBe(2);
    });

    it('assigns correct group indices', () => {
      const rows = [{ cat: 'X' }, { cat: 'Y' }, { cat: 'Y' }, { cat: 'Z' }];
      const result = buildGroupBoundaries(rows, (r) => r.cat);
      expect(result.rowGroupIndex).toEqual([0, 1, 1, 2]);
    });

    it('handles empty array', () => {
      const result = buildGroupBoundaries([], () => '');
      expect(result.boundaries.size).toBe(0);
      expect(result.rowGroupIndex).toEqual([]);
    });

    it('handles all-different rows', () => {
      const rows = [{ id: '1' }, { id: '2' }, { id: '3' }];
      const result = buildGroupBoundaries(rows, (r) => r.id);
      expect(result.boundaries.size).toBe(2);
      expect(result.rowGroupIndex).toEqual([0, 1, 2]);
    });
  });

  describe('getPdfTableStyles', () => {
    it('returns margin, styles, and headStyles', () => {
      const config = getPdfTableStyles();
      expect(config.margin).toEqual({ left: PDF_MARGIN, right: PDF_MARGIN });
      expect(config.styles).toEqual({ fontSize: 8, cellPadding: 2 });
      expect(config.headStyles).toBeDefined();
    });

    it('uses dark color for head fill', () => {
      const config = getPdfTableStyles();
      expect(config.headStyles!.fillColor).toEqual(PDF_COLORS.dark);
    });

    it('uses white text for head', () => {
      const config = getPdfTableStyles();
      expect(config.headStyles!.textColor).toEqual([255, 255, 255]);
    });

    it('uses bold font for head', () => {
      const config = getPdfTableStyles();
      expect(config.headStyles!.fontStyle).toBe('bold');
    });
  });

  describe('PDF_COLORS', () => {
    it('has expected color keys', () => {
      expect(PDF_COLORS.primary).toEqual([59, 130, 246]);
      expect(PDF_COLORS.dark).toEqual([30, 41, 59]);
      expect(PDF_COLORS.bg).toEqual([248, 250, 252]);
    });
  });

  describe('PDF_MARGIN', () => {
    it('equals 15', () => {
      expect(PDF_MARGIN).toBe(15);
    });
  });

  describe('addPdfHealthBar', () => {
    it('returns y position greater than starting y', () => {
      const doc = createPdfDocument();
      const y = addPdfHealthBar(doc, 40, 'Health score', 75, []);
      expect(y).toBeGreaterThan(40);
    });

    it('does not draw filled bar when percent is 0', () => {
      const doc = createPdfDocument();
      const y = addPdfHealthBar(doc, 40, 'Health', 0, []);
      expect(y).toBeGreaterThan(40);
    });

    it('draws filled bar when percent > 0', () => {
      const doc = createPdfDocument();
      const y = addPdfHealthBar(doc, 40, 'Health', 50, []);
      expect(y).toBeGreaterThan(40);
    });

    it('writes badges line when provided', () => {
      const doc = createPdfDocument();
      const y = addPdfHealthBar(doc, 40, 'Health', 50, ['3 obsolete(s)', '2 vulnerabilite(s)']);
      expect(doc.text).toHaveBeenCalledWith(
        '3 obsolete(s)   •   2 vulnerabilite(s)',
        PDF_MARGIN,
        expect.any(Number),
      );
      expect(y).toBeGreaterThan(40);
    });
  });

  describe('addPdfGapStats', () => {
    it('returns y position after 3 cards', () => {
      const doc = createPdfDocument();
      const gapStats = { cumulated: '3y', average: '6mo', median: '5mo' };
      const y = addPdfGapStats(doc, 50, gapStats);
      expect(y).toBe(70);
    });

    it('draws labels and values for the three cards', () => {
      const doc = createPdfDocument();
      const gapStats = { cumulated: '3y', average: '6mo', median: '5mo' };
      addPdfGapStats(doc, 50, gapStats);
      const calls = vi.mocked(doc.text).mock.calls.flat();
      expect(calls).toContain('Ecart cumule');
      expect(calls).toContain('Moyenne');
      expect(calls).toContain('Mediane');
      expect(calls).toContain('3y');
      expect(calls).toContain('6mo');
      expect(calls).toContain('5mo');
    });
  });

  describe('addPdfFooter', () => {
    it('writes page number and generated date on every page', () => {
      const doc = createPdfDocument();
      vi.mocked(doc.getNumberOfPages).mockReturnValueOnce(2);
      vi.mocked(doc.text).mockClear();
      addPdfFooter(doc);
      expect(doc.setPage).toHaveBeenCalledWith(1);
      expect(doc.setPage).toHaveBeenCalledWith(2);
      const calls = vi.mocked(doc.text).mock.calls.map((c) => c[0]);
      expect(calls).toContain('Page 1/2');
      expect(calls).toContain('Page 2/2');
    });
  });

  describe('drawGroupBoundary', () => {
    it('draws line when row is a boundary in body section', () => {
      const doc = createPdfDocument();
      vi.mocked(doc.line).mockClear();
      drawGroupBoundary(doc, new Set([2]), {
        section: 'body',
        row: { index: 2 },
        cell: { x: 10, y: 20, width: 30 },
      });
      expect(doc.line).toHaveBeenCalledWith(10, 20, 40, 20);
    });

    it('does not draw when section is not body', () => {
      const doc = createPdfDocument();
      vi.mocked(doc.line).mockClear();
      drawGroupBoundary(doc, new Set([0]), {
        section: 'head',
        row: { index: 0 },
        cell: { x: 0, y: 0, width: 10 },
      });
      expect(doc.line).not.toHaveBeenCalled();
    });

    it('does not draw when row index not in boundaries', () => {
      const doc = createPdfDocument();
      vi.mocked(doc.line).mockClear();
      drawGroupBoundary(doc, new Set([1, 3]), {
        section: 'body',
        row: { index: 2 },
        cell: { x: 0, y: 0, width: 10 },
      });
      expect(doc.line).not.toHaveBeenCalled();
    });
  });

  describe('applyAlternateGroupShading', () => {
    it('applies bg color to odd-indexed groups in body section', () => {
      const data = {
        section: 'body',
        row: { index: 1 },
        cell: { styles: { fillColor: [0, 0, 0] } },
      };
      applyAlternateGroupShading([0, 1, 1], data);
      expect(data.cell.styles.fillColor).toEqual(PDF_COLORS.bg);
    });

    it('leaves even-indexed groups unchanged', () => {
      const data = {
        section: 'body',
        row: { index: 0 },
        cell: { styles: { fillColor: [5, 5, 5] } },
      };
      applyAlternateGroupShading([0, 1, 1], data);
      expect(data.cell.styles.fillColor).toEqual([5, 5, 5]);
    });

    it('does nothing for non-body sections', () => {
      const data = {
        section: 'head',
        row: { index: 1 },
        cell: { styles: { fillColor: [1, 2, 3] } },
      };
      applyAlternateGroupShading([0, 1, 1], data);
      expect(data.cell.styles.fillColor).toEqual([1, 2, 3]);
    });
  });
});
