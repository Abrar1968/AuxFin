<template>
    <div
        v-if="mobileOpen"
        class="fixed inset-0 z-30 bg-slate-950/45 backdrop-blur-sm md:hidden"
        @click="$emit('close')"
    ></div>

    <aside
        class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-(--bg-dark) text-slate-100 shadow-[0_28px_60px_rgba(5,18,34,.42)] transition-transform duration-300 md:static md:z-auto"
        :class="[
            collapsed ? 'md:w-20' : 'md:w-64',
            mobileOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
        ]"
    >
        <div class="h-16 px-5 flex items-center border-b border-cyan-900/50">
            <h1 class="text-lg font-extrabold tracking-wide" :class="collapsed ? 'md:hidden' : ''">FinERP</h1>
            <span class="hidden md:inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/12 text-sm font-black shadow-[0_8px_16px_rgba(0,0,0,.22)]" :class="collapsed ? '' : 'md:hidden'">
                F
            </span>
        </div>

        <nav class="p-3 space-y-1 flex-1 overflow-y-auto">
            <template v-for="section in sections" :key="section.title">
                <p class="px-3 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-cyan-200/55" :class="collapsed ? 'md:hidden' : ''">
                    {{ section.title }}
                </p>

                <RouterLink
                    v-for="item in section.items"
                    :key="item.to"
                    :to="item.to"
                    class="block rounded-xl px-3 py-2.5 text-sm font-semibold transition-all duration-200"
                    :class="isActive(item.to)
                        ? 'bg-cyan-300/18 text-cyan-100 shadow-[inset_0_0_0_1px_rgba(103,232,249,.45),0_10px_20px_rgba(8,145,178,.22)]'
                        : 'text-slate-300 hover:-translate-y-px hover:bg-white/10 hover:text-white'"
                    @click="$emit('close')"
                >
                    <span class="inline-flex w-full items-center justify-between gap-2">
                        <span :class="collapsed ? 'md:hidden' : ''">{{ item.label }}</span>
                        <span
                            v-if="item.withMessageBadge && notifications.unreadMessageCount > 0"
                            class="inline-flex min-w-5 h-5 px-1 rounded-full bg-amber-500 text-slate-900 text-[10px] font-bold items-center justify-center shadow-[0_6px_12px_rgba(245,158,11,.45)]"
                        >
                            {{ notifications.unreadMessageCount > 99 ? '99+' : notifications.unreadMessageCount }}
                        </span>
                    </span>
                </RouterLink>
            </template>
        </nav>

        <div class="m-3 rounded-xl border border-cyan-900/45 bg-(--bg-dark-card) p-3 shadow-[0_10px_20px_rgba(0,0,0,.25)]">
            <p class="text-xs uppercase tracking-wide text-cyan-200/60" :class="collapsed ? 'md:hidden' : ''">Signed In</p>
            <p class="mt-1 text-sm font-semibold text-white truncate" :class="collapsed ? 'md:hidden' : ''">{{ auth.user?.name ?? 'User' }}</p>
            <button
                type="button"
                class="mt-2 w-full rounded-lg bg-white/12 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/20"
                :class="collapsed ? 'md:hidden' : ''"
                @click="logout"
            >
                Logout
            </button>
        </div>
    </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useAuthStore } from '../../stores/auth.store';
import { useNotificationStore } from '../../stores/notification.store';

defineEmits(['close']);

const props = defineProps({
    role: {
        type: String,
        required: true,
    },
    collapsed: {
        type: Boolean,
        default: false,
    },
    mobileOpen: {
        type: Boolean,
        default: false,
    },
});

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const notifications = useNotificationStore();

function isActive(path) {
    return route.path === path || route.path.startsWith(`${path}/`);
}

async function logout() {
    await auth.logout();
    router.push('/login');
}

const sections = computed(() => {
    if (props.role === 'employee') {
        return [
            {
                title: 'Portal',
                items: [
                    { label: 'Dashboard', to: '/portal/dashboard' },
                    { label: 'My Salary', to: '/portal/salary' },
                    { label: 'Loans', to: '/portal/loans' },
                    { label: 'Leaves', to: '/portal/leaves' },
                    { label: 'Attendance', to: '/portal/attendance' },
                    { label: 'Inbox', to: '/portal/inbox', withMessageBadge: true },
                ],
            },
        ];
    }

    return [
        {
            title: 'Operations',
            items: [
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
            ],
        },
        {
            title: 'Insights',
            items: [
                { label: 'Analytics', to: '/admin/analytics' },
                { label: 'Growth', to: '/admin/growth' },
                { label: 'Reports', to: '/admin/reports' },
            ],
        },
        {
            title: 'Communication',
            items: [
                { label: 'Messages', to: '/admin/messages', withMessageBadge: true },
                { label: 'Settings', to: '/admin/settings' },
            ],
        },
    ];
});
</script>
