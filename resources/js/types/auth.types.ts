export type UserRole = 'admin' | 'employee';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    is_active: boolean;
}

export interface AuthLoginPayload {
    email: string;
    passkey: string;
}

export interface AuthLoginResponse {
    token: string;
    role: UserRole;
    user: AuthUser;
}

export interface AuthState {
    token: string | null;
    role: UserRole | null;
    user: AuthUser | null;
    loading: boolean;
}
