import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { AuthService } from '../services/auth.service';

const TOKEN_KEY = 'auxfin_token';

export const useAuthStore = defineStore('auth', () => {
    const token = ref(localStorage.getItem(TOKEN_KEY));
    const user = ref(null);
    const loading = ref(false);

    const role = computed(() => user.value?.role ?? null);

    async function login(email, passkey) {
        loading.value = true;

        try {
            const response = await AuthService.login(email, passkey);
            token.value = response.data.token;
            user.value = response.data.user;
            localStorage.setItem(TOKEN_KEY, token.value);

            if (typeof window.configureEchoAuth === 'function') {
                window.configureEchoAuth(token.value);
            }

            return response.data;
        } finally {
            loading.value = false;
        }
    }

    async function fetchMe() {
        if (!token.value) {
            return null;
        }

        try {
            const response = await AuthService.me();
            user.value = response.data.user;
            return user.value;
        } catch (error) {
            hardReset();
            throw error;
        }
    }

    async function logout() {
        try {
            if (token.value) {
                await AuthService.logout();
            }
        } finally {
            hardReset();
        }
    }

    function hardReset() {
        token.value = null;
        user.value = null;
        localStorage.removeItem(TOKEN_KEY);

        if (typeof window.configureEchoAuth === 'function') {
            window.configureEchoAuth(null);
        }
    }

    return {
        token,
        user,
        role,
        loading,
        login,
        fetchMe,
        logout,
        hardReset,
    };
});
