import { describe, it, expect } from 'vitest';
import type { FormState, HealthScore } from '../dependency';

describe('FormState', () => {
  it('narrows correctly', () => {
    const s: FormState<string> = { status: 'success', data: 'ok' };
    if (s.status === 'success') {
      expect(s.data).toBe('ok');
    }
  });
});

describe('HealthScore', () => {
  it('has required shape', () => {
    const h: HealthScore = { total: 10, upToDate: 8, outdated: 2, totalVulns: 1, percent: 80 };
    expect(h.percent).toBe(80);
  });
});
