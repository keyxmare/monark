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
    default: vi.fn(() => mockDoc),
  };
});

vi.mock('jspdf-autotable', () => ({
  default: vi.fn(),
}));

import {
  PDF_COLORS,
  PDF_MARGIN,
  addPdfHeader,
  buildGroupBoundaries,
  createPdfDocument,
  getPdfTableStyles,
} from '@/shared/utils/pdfExport';

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
});
