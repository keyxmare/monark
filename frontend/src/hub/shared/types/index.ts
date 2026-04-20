export interface ApiResponse<T> {
  data: T;
  message?: string;
  status: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: PaginationMeta;
}

export interface PaginationMeta {
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
}

export interface ApiError {
  errors?: Record<string, string[]>;
  message: string;
  status: number;
}

export interface SelectOption {
  disabled?: boolean;
  label: string;
  value: string;
}
