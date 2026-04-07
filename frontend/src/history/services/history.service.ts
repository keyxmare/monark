import type { ApiResponse } from '@/shared/types';
import { api } from '@/shared/utils/api';

import type { BackfillRequest, DebtTimelinePoint } from '@/history/types/history';

export const historyService = {
  getTimeline(
    projectId: string,
    from?: string,
    to?: string,
  ): Promise<ApiResponse<DebtTimelinePoint[]>> {
    const params = new URLSearchParams();
    if (from) params.set('from', from);
    if (to) params.set('to', to);
    const query = params.toString();
    const suffix = query.length > 0 ? `?${query}` : '';
    return api.get<ApiResponse<DebtTimelinePoint[]>>(
      `/history/projects/${projectId}/timeline${suffix}`,
    );
  },

  triggerBackfill(
    projectId: string,
    payload: BackfillRequest,
  ): Promise<ApiResponse<{ scheduled: boolean }>> {
    return api.post<ApiResponse<{ scheduled: boolean }>>(
      `/history/projects/${projectId}/backfill`,
      payload,
    );
  },
};
