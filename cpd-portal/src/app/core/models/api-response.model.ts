export interface ApiResponse<T = unknown> {
  message?: string;
  data?: T;
  error?: string;
  [key: string]: unknown; // Allow for other properties like 'user', 'courses' etc until standardized
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    total: number;
    page: number;
    limit: number;
    last_page: number;
  };
}

export interface AuthResponse {
  user: import('./user.model').User;
  csrfToken?: string;
  message?: string;
}

export interface CsrfResponse {
  csrfToken: string;
}
