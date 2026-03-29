import { afterEach, describe, expect, it } from 'vitest';

import { createApiMock, resetApiMock } from '../../helpers';

describe('createApiMock', () => {
  afterEach(() => {
    resetApiMock();
  });

  it('mocks GET requests', async () => {
    createApiMock({
      'GET /catalog/projects': [{ id: 1, name: 'Alpha' }],
    });

    const res = await fetch('/api/catalog/projects');
    const data = await res.json();

    expect(res.ok).toBe(true);
    expect(data).toEqual([{ id: 1, name: 'Alpha' }]);
  });

  it('mocks POST requests', async () => {
    createApiMock({
      'POST /catalog/projects': { id: 2, name: 'Beta' },
    });

    const res = await fetch('/api/catalog/projects', { method: 'POST' });
    const data = await res.json();

    expect(res.ok).toBe(true);
    expect(data).toEqual({ id: 2, name: 'Beta' });
  });

  it('returns 404 for unmocked routes', async () => {
    createApiMock({
      'GET /catalog/projects': [],
    });

    const res = await fetch('/api/identity/users');

    expect(res.status).toBe(404);
  });

  it('strips query params for route matching', async () => {
    createApiMock({
      'GET /catalog/projects': [{ id: 1 }],
    });

    const res = await fetch('/api/catalog/projects?page=1&limit=10');
    const data = await res.json();

    expect(res.ok).toBe(true);
    expect(data).toEqual([{ id: 1 }]);
  });

  it('tries exact match with query first', async () => {
    createApiMock({
      'GET /catalog/projects?active=true': [{ id: 99 }],
      'GET /catalog/projects': [{ id: 1 }],
    });

    const resExact = await fetch('/api/catalog/projects?active=true');
    const dataExact = await resExact.json();
    expect(dataExact).toEqual([{ id: 99 }]);

    const resBase = await fetch('/api/catalog/projects');
    const dataBase = await resBase.json();
    expect(dataBase).toEqual([{ id: 1 }]);
  });

  it('defaults to GET when no method is specified in fetch', async () => {
    createApiMock({
      'GET /catalog/projects': [{ id: 1 }],
    });

    const res = await fetch('/api/catalog/projects');
    expect(res.ok).toBe(true);
  });
});
