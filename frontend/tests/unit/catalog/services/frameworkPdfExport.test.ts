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
import { exportFrameworksPdf } from '@/apps/monitoring/catalog/services/frameworkPdfExport';

describe('exportFrameworksPdf', () => {
  it('calls autoTable and saves a frameworks.pdf file', () => {
    const rows = [
      {
        project: 'monark',
        name: 'Symfony',
        version: '7.2',
        latestLts: '7.1',
        ltsGap: '',
        status: 'OK',
      },
      {
        project: 'monark',
        name: 'Vue',
        version: '3.5',
        latestLts: '3.4',
        ltsGap: '',
        status: 'OK',
      },
    ];

    exportFrameworksPdf(rows);

    expect(autoTableMock).toHaveBeenCalledTimes(1);
    const docInstance = vi.mocked(jsPDF).mock.results[0]?.value;
    expect(docInstance.save).toHaveBeenCalledWith('frameworks.pdf');
  });

  it('applies status colors via didParseCell hook', () => {
    const rows = [
      { project: 'p', name: 'n', version: '1', latestLts: '1', ltsGap: '', status: 'Non maintenu' },
      { project: 'p', name: 'n', version: '1', latestLts: '1', ltsGap: '', status: 'Inactif' },
      { project: 'p', name: 'n', version: '1', latestLts: '1', ltsGap: '', status: 'OK' },
    ];

    exportFrameworksPdf(rows);

    const options = autoTableMock.mock.calls[autoTableMock.mock.calls.length - 1][1];
    const didParseCell = options.didParseCell;

    const eolData = {
      section: 'body',
      row: { index: 0 },
      column: { index: 5 },
      cell: { raw: 'Non maintenu', styles: { fillColor: [0, 0, 0] } },
    };
    didParseCell(eolData);
    expect(eolData.cell.styles.textColor).toBeDefined();

    const warningData = {
      section: 'body',
      row: { index: 1 },
      column: { index: 5 },
      cell: { raw: 'Inactif', styles: { fillColor: [0, 0, 0] } },
    };
    didParseCell(warningData);
    expect(warningData.cell.styles.textColor).toBeDefined();

    const okData = {
      section: 'body',
      row: { index: 2 },
      column: { index: 5 },
      cell: { raw: 'OK', styles: { fillColor: [0, 0, 0] } },
    };
    didParseCell(okData);
    expect(okData.cell.styles.textColor).toBeDefined();
  });

  it('ignores non-status columns in didParseCell', () => {
    exportFrameworksPdf([
      { project: 'p', name: 'n', version: '1', latestLts: '1', ltsGap: '', status: 'OK' },
    ]);
    const options = autoTableMock.mock.calls[autoTableMock.mock.calls.length - 1][1];
    const didParseCell = options.didParseCell;

    const otherColData = {
      section: 'body',
      row: { index: 0 },
      column: { index: 1 },
      cell: { raw: 'whatever', styles: { fillColor: [0, 0, 0] } },
    };
    didParseCell(otherColData);
    expect(otherColData.cell.styles.textColor).toBeUndefined();
  });
});
