import { useAuthStore } from '../stores/auth.store';

export function applyAuthGuards(router) {
    router.beforeEach(async (to) => {
        const auth = useAuthStore();

        if (to.meta.public) {
            if (auth.token && !auth.user) {
                try {
                    await auth.fetchMe();
                } catch {
                    // Allow login page when token is stale/invalid.
                    return true;
                }
            }

            if (auth.token && auth.role) {
                return auth.role === 'employee' ? '/portal/dashboard' : '/admin/dashboard';
            }

            return true;
        }

        if (auth.token && !auth.user) {
            try {
                await auth.fetchMe();
            } catch {
                return '/login';
            }
        }

        if (to.meta.requiresAuth && !auth.token) {
            return '/login';
        }

        if (to.meta.role === 'admin' && auth.role === 'employee') {
            return '/portal/dashboard';
        }

        if (to.meta.role === 'employee' && auth.role !== 'employee') {
            return '/admin/dashboard';
        }

        return true;
    });
}
