import { describe, expect, it } from 'vitest';

import { formatDate, formatDateTime, formatRelative } from '@/shared/utils/dateFormat';

describe('dateFormat', () => {
  const iso = '2026-03-15T14:30:00Z';

  describe('formatDate', () => {
    it('formats date in French locale', () => {
      const result = formatDate(iso, 'fr');
      expect(result).toContain('mars');
      expect(result).toContain('2026');
    });

    it('formats date in English locale', () => {
      const result = formatDate(iso, 'en');
      expect(result).toContain('Mar');
      expect(result).toContain('2026');
    });

    it('defaults to French', () => {
      const result = formatDate(iso);
      expect(result).toContain('2026');
    });
  });

  describe('formatDateTime', () => {
    it('includes time', () => {
      const result = formatDateTime(iso, 'en');
      expect(result).toContain('2026');
    });
  });

  describe('formatRelative', () => {
    it('returns relative time for recent dates', () => {
      const recent = new Date(Date.now() - 3600 * 1000).toISOString();
      const result = formatRelative(recent);
      expect(typeof result).toBe('string');
      expect(result.length).toBeGreaterThan(0);
    });

    it('returns relative time for old dates', () => {
      const old = new Date(Date.now() - 86400 * 1000 * 5).toISOString();
      const result = formatRelative(old);
      expect(typeof result).toBe('string');
    });
  });
});
