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
  async startSync(projectId?: string): Promise<StartSyncResponse> {
    const BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '/api/v1';
    const headers: Record<string, string> = {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    };
    const token = localStorage.getItem(STORAGE_KEYS.AUTH_TOKEN);
    if (token) headers.Authorization = `Bearer ${token}`;

    const body = projectId ? JSON.stringify({ projectId }) : undefined;
    const res = await fetch(`${BASE_URL}/sync`, { method: 'POST', headers, body });
    if (res.status === 409) throw new Error('sync_already_running');
    if (!res.ok) throw new Error('sync_start_failed');
    const data = await res.json();
    return (data.data ?? data) as StartSyncResponse;
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
