import type { ApiResponse } from '@/shared/types'
import { api } from '@/shared/utils/api'

export interface DashboardMetric {
  label: string
  value: number | string
  change?: number
}

export interface DashboardData {
  metrics: DashboardMetric[]
}

export const dashboardService = {
  getDashboard(): Promise<ApiResponse<DashboardData>> {
    return api.get<ApiResponse<DashboardData>>('/activity/dashboard')
  },
}
