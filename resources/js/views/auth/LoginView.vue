<template>
    <section>
        <h1 class="text-2xl font-extrabold">Sign In To FinERP</h1>
        <p class="text-sm text-slate-500 mt-1">Use your admin-issued passkey.</p>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <AppInput v-model="form.email" type="email" label="Email" placeholder="you@company.com" />
            <AppInput v-model="form.passkey" type="password" label="Passkey" placeholder="Enter secure passkey" />

            <AppButton type="submit" class="w-full" :loading="auth.loading">
                {{ auth.loading ? 'Signing In...' : 'Sign In' }}
            </AppButton>

            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        </form>
    </section>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import AppButton from '../../components/ui/AppButton.vue';
import AppInput from '../../components/ui/AppInput.vue';
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
