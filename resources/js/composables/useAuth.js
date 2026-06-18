import { useAuthStore } from '../stores/auth.store';

export function useAuth() {
    const store = useAuthStore();

    return {
        token: store.token,
        user: store.user,
        role: store.role,
        loading: store.loading,
        login: store.login,
        fetchMe: store.fetchMe,
        logout: store.logout,
        hardReset: store.hardReset,
    };
}
