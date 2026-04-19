import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

vi.mock('../services/auth.service', () => ({
    AuthService: {
        login: vi.fn(),
        me: vi.fn(),
        logout: vi.fn(),
    },
}));

import { useAuthStore } from '../stores/auth.store';
import { AuthService } from '../services/auth.service';

describe('auth store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('stores token and user on successful login', async () => {
        AuthService.login.mockResolvedValueOnce({
            data: {
                token: 'token-123',
                user: { id: 1, name: 'Admin User', role: 'admin' },
            },
        });

        const store = useAuthStore();
        await store.login('admin@test.local', 'Pass#1');

        expect(store.token).toBe('token-123');
        expect(store.user?.name).toBe('Admin User');
        expect(localStorage.getItem('finerp_token')).toBe('token-123');
    });

    it('fetchMe sets user and hard resets on failure', async () => {
        const store = useAuthStore();
        store.token = 'existing';

        AuthService.me.mockRejectedValueOnce(new Error('401'));

        await expect(store.fetchMe()).rejects.toThrow();
        expect(store.token).toBeNull();
        expect(store.user).toBeNull();
    });

    it('logout clears auth state even if api fails', async () => {
        const store = useAuthStore();
        store.token = 'token';
        store.user = { id: 3, role: 'employee' };

        AuthService.logout.mockRejectedValueOnce(new Error('network'));

        await expect(store.logout()).rejects.toThrow('network');

        expect(store.token).toBeNull();
        expect(store.user).toBeNull();
    });
});
