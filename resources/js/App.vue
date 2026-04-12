<template>
    <ToastStack />
    <RouterView />
</template>

<script setup>
import { onBeforeUnmount, watch } from 'vue';
import { RouterView } from 'vue-router';
import { useAuthStore } from './stores/auth.store';
import { useRealTime } from './composables/useRealTime';
import ToastStack from './components/layout/ToastStack.vue';

const auth = useAuthStore();
const realtime = useRealTime();

watch(
    () => [auth.token, auth.user?.role, auth.user?.employee?.id],
    () => {
        realtime.unsubscribeAll();

        if (!auth.token || !auth.user) {
            return;
        }

        if (auth.user.role === 'employee' && auth.user.employee?.id) {
            realtime.subscribeEmployee(auth.user.employee.id, auth.token);
            return;
        }

        if (auth.user.role === 'admin' || auth.user.role === 'super_admin') {
            realtime.subscribeAdmin(auth.token);
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    realtime.unsubscribeAll();
});
</script>
