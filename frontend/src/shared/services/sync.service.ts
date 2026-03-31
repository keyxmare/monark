import type { GlobalSyncState } from '@/shared/types/globalSync';
import { api } from '@/shared/utils/api';
import { STORAGE_KEYS } from '@/shared/constants';

interface StartSyncResponse {
  syncId: string;
  status: string;
  currentStep: number;
}

interface CurrentSyncResponse {
  data: GlobalSyncState | null;
}

export const syncService = {
  async startSync(): Promise<StartSyncResponse> {
    const BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '/api/v1';
    const headers: Record<string, string> = {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    };
    const token = localStorage.getItem(STORAGE_KEYS.AUTH_TOKEN);
    if (token) headers.Authorization = `Bearer ${token}`;

    const res = await fetch(`${BASE_URL}/sync`, { method: 'POST', headers });
    if (res.status === 409) throw new Error('sync_already_running');
    if (!res.ok) throw new Error('sync_start_failed');
    const body = await res.json();
    return (body.data ?? body) as StartSyncResponse;
  },

  async getCurrentSync(): Promise<GlobalSyncState | null> {
    try {
      const body = await api.get<CurrentSyncResponse>('/sync/current');
      return body.data;
    } catch {
      return null;
    }
  },
};
