<template>
    <section class="space-y-6">
        <header class="space-y-3">
            <p class="inline-flex rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.13em] text-sky-700">
                Enterprise Sign-In
            </p>
            <div class="space-y-2">
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Welcome Back</h1>
                <p class="text-sm leading-relaxed text-slate-600">
                    Sign in with your organization credentials to continue to the AuxFin operations workspace.
                </p>
            </div>
        </header>

        <form class="space-y-4" @submit.prevent="submit">
            <AppInput
                v-model="form.email"
                type="email"
                name="email"
                autocomplete="email"
                label="Work Email"
                placeholder="name@company.com"
            />

            <label class="block space-y-1">
                <span class="text-xs font-semibold uppercase tracking-[0.11em] text-slate-600">Passkey</span>
                <div class="relative">
                    <input
                        v-model="form.passkey"
                        :type="showPasskey ? 'text' : 'password'"
                        name="passkey"
                        autocomplete="current-password"
                        autocapitalize="off"
                        spellcheck="false"
                        placeholder="Enter secure passkey"
                        class="fin-focus-ring w-full rounded-xl border border-sky-200/80 bg-white/90 px-3 py-2.5 pr-24 text-sm text-slate-800 shadow-[inset_0_1px_0_rgba(255,255,255,.8)] outline-none transition duration-200 placeholder:text-slate-400 focus:border-sky-400 focus:bg-white"
                    >
                    <button
                        type="button"
                        class="absolute right-1.5 top-1.5 rounded-lg border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.08em] text-sky-700 transition hover:bg-sky-100"
                        @click="showPasskey = !showPasskey"
                    >
                        {{ showPasskey ? 'Hide' : 'Show' }}
                    </button>
                </div>
            </label>

            <AppButton type="submit" size="lg" class="w-full" :loading="auth.loading">
                {{ auth.loading ? 'Signing In...' : 'Sign In To Workspace' }}
            </AppButton>

            <p v-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                {{ error }}
            </p>
        </form>

        <aside class="rounded-xl border border-slate-200 bg-slate-50/75 p-3">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-700">Security Notice</p>
            <p class="mt-1 text-xs leading-relaxed text-slate-600">
                Sign-in activity is monitored and logged for security and compliance controls.
            </p>
        </aside>
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
const showPasskey = ref(false);

const form = reactive({
    email: '',
    passkey: '',
});

async function submit(event) {
    if (auth.loading) {
        return;
    }

    error.value = '';
    const submittedForm = event?.target instanceof HTMLFormElement ? event.target : null;
    const formData = submittedForm ? new FormData(submittedForm) : null;

    const email = String(formData?.get('email') ?? form.email)
        .trim()
        .toLowerCase();
    const passkey = String(formData?.get('passkey') ?? form.passkey);

    form.email = email;
    form.passkey = passkey;

    if (!email || !passkey) {
        error.value = 'Email and passkey are required.';
        return;
    }

    try {
        const data = await auth.login(email, passkey);
        const destination = data.user.role === 'employee' ? '/portal/dashboard' : '/admin/dashboard';
        await router.replace(destination);
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Unable to sign in.';
    }
}
</script>
