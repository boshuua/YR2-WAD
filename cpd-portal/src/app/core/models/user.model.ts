export type AccessLevel = 'admin' | 'user';

export interface User {
    id: number;
    email: string;
    first_name: string;
    last_name: string;
    job_title?: string;
    access_level: AccessLevel;
    created_at?: string;
    failed_login_attempts?: number;
    lockout_until?: string | null;
}
