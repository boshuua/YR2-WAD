export interface ApiResponse<T = any> {
    message?: string;
    data?: T;
    error?: string;
    [key: string]: any; // Allow for other properties like 'user', 'courses' etc until standardized
}

export interface AuthResponse {
    user: import('./user.model').User;
    csrfToken?: string;
    message?: string;
}

export interface CsrfResponse {
    csrfToken: string;
}
