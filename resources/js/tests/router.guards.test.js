import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useAuthStore } from '../stores/auth.store';
import { applyAuthGuards } from '../router/guards';

describe('router guards', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('redirects unauthenticated users to login for protected routes', async () => {
        let guard;
        const mockRouter = { beforeEach: (fn) => { guard = fn; } };
        applyAuthGuards(mockRouter);

        const result = await guard({ meta: { requiresAuth: true }, path: '/admin/dashboard' });
        expect(result).toBe('/login');
    });

    it('redirects authenticated employee away from admin routes', async () => {
        let guard;
        const mockRouter = { beforeEach: (fn) => { guard = fn; } };
        applyAuthGuards(mockRouter);

        const auth = useAuthStore();
        auth.token = 'abc';
        auth.user = { id: 2, role: 'employee' };

        const result = await guard({ meta: { requiresAuth: true, role: 'admin' }, path: '/admin/payroll' });
        expect(result).toBe('/portal/dashboard');
    });

    it('redirects authenticated users from public login route', async () => {
        let guard;
        const mockRouter = { beforeEach: (fn) => { guard = fn; } };
        applyAuthGuards(mockRouter);

        const auth = useAuthStore();
        auth.token = 'abc';
        auth.user = { id: 9, role: 'admin' };

        const result = await guard({ meta: { public: true }, path: '/login' });
        expect(result).toBe('/admin/dashboard');
    });

    it('allows public login route when stored token is stale', async () => {
        let guard;
        const mockRouter = { beforeEach: (fn) => { guard = fn; } };
        applyAuthGuards(mockRouter);

        const auth = useAuthStore();
        auth.token = 'stale-token';
        auth.user = null;
        auth.fetchMe = vi.fn().mockRejectedValue(new Error('Unauthorized'));

        const result = await guard({ meta: { public: true }, path: '/login' });
        expect(result).toBe(true);
    });
});
