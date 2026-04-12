<template>
    <section>
        <h1 class="text-2xl font-extrabold">Sign In To FinERP</h1>
        <p class="text-sm text-slate-500 mt-1">Use your admin-issued passkey.</p>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <label class="block">
                <span class="text-sm font-semibold">Email</span>
                <input
                    v-model="form.email"
                    type="email"
                    required
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-[var(--color-primary)]"
                >
            </label>

            <label class="block">
                <span class="text-sm font-semibold">Passkey</span>
                <input
                    v-model="form.passkey"
                    type="password"
                    required
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-[var(--color-primary)]"
                >
            </label>

            <button
                type="submit"
                class="w-full rounded-lg py-2.5 text-white font-semibold bg-[image:var(--color-gradient)] disabled:opacity-60"
                :disabled="auth.loading"
            >
                {{ auth.loading ? 'Signing In...' : 'Sign In' }}
            </button>

            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        </form>
    </section>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth.store';

const router = useRouter();
const auth = useAuthStore();
const error = ref('');

const form = reactive({
    email: '',
    passkey: '',
});

async function submit() {
    error.value = '';

    try {
        const data = await auth.login(form.email, form.passkey);
        if (data.user.role === 'employee') {
            router.push('/portal/dashboard');
        } else {
            router.push('/admin/dashboard');
        }
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Unable to sign in.';
    }
}
</script>
