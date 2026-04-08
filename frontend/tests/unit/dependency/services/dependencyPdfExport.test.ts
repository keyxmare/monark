import { describe, expect, it, vi } from 'vitest';

vi.mock('jspdf', () => {
  const mockDoc = {
    internal: {
      pageSize: { getWidth: () => 297, getHeight: () => 210 },
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
    save: vi.fn(),
  };
  return {
    default: vi.fn(function () {
      return mockDoc;
    }),
  };
});

const { autoTableMock } = vi.hoisted(() => ({ autoTableMock: vi.fn() }));
vi.mock('jspdf-autotable', () => ({
  default: autoTableMock,
}));

import jsPDF from 'jspdf';
import { exportDependenciesPdf } from '@/dependency/services/dependencyPdfExport';

const baseRow = {
  name: 'lodash',
  project: 'monark',
  currentVersion: '4.17.20',
  latestVersion: '4.17.21',
  gap: '2 mois',
  packageManager: 'npm',
  type: 'runtime',
  status: 'Obsolete',
  vulnerabilities: 0,
};

describe('exportDependenciesPdf', () => {
  it('saves dependances.pdf and calls autoTable once', () => {
    exportDependenciesPdf([baseRow], null);
    expect(autoTableMock).toHaveBeenCalled();
    const doc = vi.mocked(jsPDF).mock.results[0]?.value;
    expect(doc.save).toHaveBeenCalledWith('dependances.pdf');
  });

  it('renders health bar when health data is provided', () => {
    const health = { total: 10, upToDate: 7, outdated: 3, totalVulns: 2, percent: 70 };
    exportDependenciesPdf([baseRow], health);
    const doc = vi.mocked(jsPDF).mock.results[vi.mocked(jsPDF).mock.results.length - 1]?.value;
    const textCalls = vi.mocked(doc.text).mock.calls.map((c) => c[0]);
    expect(textCalls.some((t: string) => t.includes('70%'))).toBe(true);
  });

  it('renders gap stats when provided', () => {
    const gapStats = { average: '3 mois', median: '2 mois', cumulated: '2 ans' };
    exportDependenciesPdf([baseRow], null, gapStats);
    const doc = vi.mocked(jsPDF).mock.results[vi.mocked(jsPDF).mock.results.length - 1]?.value;
    const textCalls = vi.mocked(doc.text).mock.calls.map((c) => c[0]);
    expect(textCalls).toContain('Ecart cumule');
  });

  it('colorizes status, gap and vulnerabilities columns via didParseCell', () => {
    exportDependenciesPdf([baseRow], null);
    const options = autoTableMock.mock.calls[autoTableMock.mock.calls.length - 1][1];
    const didParseCell = options.didParseCell;

    const obsoleteStatus = {
      section: 'body',
      row: { index: 0 },
      column: { index: 7 },
      cell: { raw: 'Obsolete', styles: { fillColor: [0, 0, 0] } },
    };
    didParseCell(obsoleteStatus);
    expect(obsoleteStatus.cell.styles.textColor).toBeDefined();

    const gapYears = {
      section: 'body',
      row: { index: 0 },
      column: { index: 4 },
      cell: { raw: '2 ans', styles: { fillColor: [0, 0, 0] } },
    };
    didParseCell(gapYears);
    expect(gapYears.cell.styles.textColor).toBeDefined();

    const gapMonths = {
      section: 'body',
      row: { index: 0 },
      column: { index: 4 },
      cell: { raw: '5 mois', styles: { fillColor: [0, 0, 0] } },
    };
    didParseCell(gapMonths);
    expect(gapMonths.cell.styles.textColor).toBeDefined();

    const gapAJour = {
      section: 'body',
      row: { index: 0 },
      column: { index: 4 },
      cell: { raw: 'A jour', styles: { fillColor: [0, 0, 0] } },
    };
    didParseCell(gapAJour);
    expect(gapAJour.cell.styles.textColor).toBeDefined();

    const vulnsHigh = {
      section: 'body',
      row: { index: 0 },
      column: { index: 8 },
      cell: { raw: 5, styles: { fillColor: [0, 0, 0] } },
    };
    didParseCell(vulnsHigh);
    expect(vulnsHigh.cell.styles.textColor).toBeDefined();

    const vulnsSome = {
      section: 'body',
      row: { index: 0 },
      column: { index: 8 },
      cell: { raw: 1, styles: { fillColor: [0, 0, 0] } },
    };
    didParseCell(vulnsSome);
    expect(vulnsSome.cell.styles.textColor).toBeDefined();
  });

  it('skips didParseCell logic when section is not body', () => {
    exportDependenciesPdf([baseRow], null);
    const options = autoTableMock.mock.calls[autoTableMock.mock.calls.length - 1][1];
    const head = {
      section: 'head',
      row: { index: 0 },
      column: { index: 7 },
      cell: { raw: 'Obsolete', styles: { fillColor: [0, 0, 0] } },
    };
    options.didParseCell(head);
    expect(head.cell.styles.textColor).toBeUndefined();
  });
});
