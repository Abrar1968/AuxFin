<template>
    <aside class="hidden md:flex md:flex-col md:w-64 bg-(--bg-dark) text-slate-100">
        <div class="h-16 px-5 flex items-center border-b border-slate-800">
            <h1 class="text-lg font-extrabold tracking-wide">FinERP</h1>
        </div>

        <nav class="p-4 space-y-1 flex-1 overflow-y-auto">
            <RouterLink
                v-for="item in items"
                :key="item.to"
                :to="item.to"
                class="block px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                :class="isActive(item.to) ? 'bg-white/12 text-white' : 'text-slate-300 hover:bg-white/8 hover:text-white'"
            >
                <span class="inline-flex w-full items-center justify-between gap-2">
                    <span>{{ item.label }}</span>
                    <span
                        v-if="item.withMessageBadge && notifications.unreadMessageCount > 0"
                        class="inline-flex min-w-5 h-5 px-1 rounded-full bg-rose-600 text-white text-[10px] font-bold items-center justify-center"
                    >
                        {{ notifications.unreadMessageCount > 99 ? '99+' : notifications.unreadMessageCount }}
                    </span>
                </span>
            </RouterLink>
        </nav>
    </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { useNotificationStore } from '../../stores/notification.store';

const props = defineProps({
    role: {
        type: String,
        required: true,
    },
});

const route = useRoute();
const notifications = useNotificationStore();

function isActive(path) {
    return route.path === path || route.path.startsWith(`${path}/`);
}

const items = computed(() => {
    if (props.role === 'employee') {
        return [
            { label: 'Dashboard', to: '/portal/dashboard' },
            { label: 'My Salary', to: '/portal/salary' },
            { label: 'Loans', to: '/portal/loans' },
            { label: 'Leaves', to: '/portal/leaves' },
            { label: 'Attendance', to: '/portal/attendance' },
            { label: 'Inbox', to: '/portal/inbox', withMessageBadge: true },
        ];
    }

    return [
        { label: 'Dashboard', to: '/admin/dashboard' },
        { label: 'Employees', to: '/admin/employees' },
        { label: 'Payroll', to: '/admin/payroll' },
        { label: 'Loans', to: '/admin/loans' },
        { label: 'Projects', to: '/admin/projects' },
        { label: 'Expenses', to: '/admin/expenses' },
        { label: 'Liabilities', to: '/admin/liabilities' },
        { label: 'Assets', to: '/admin/assets' },
        { label: 'Leaves', to: '/admin/leaves' },
        { label: 'Attendance', to: '/admin/attendance' },
        { label: 'Settings', to: '/admin/settings' },
        { label: 'Analytics', to: '/admin/analytics' },
        { label: 'Growth', to: '/admin/growth' },
        { label: 'Messages', to: '/admin/messages', withMessageBadge: true },
    ];
});
</script>
