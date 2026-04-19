<template>
    <div
        v-if="mobileOpen"
        class="fixed inset-0 z-30 bg-slate-950/45 backdrop-blur-sm md:hidden"
        @click="$emit('close')"
    ></div>

    <aside
        class="fixed inset-y-0 left-0 z-40 w-64 transition-transform duration-300 md:py-3 md:pl-3"
        :class="[
            collapsed ? 'md:w-20' : 'md:w-64',
            mobileOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
        ]"
    >
        <div class="flex h-full min-h-0 flex-col overflow-hidden rounded-[1.15rem] border border-cyan-900/45 bg-(--bg-dark) text-slate-100 shadow-[0_22px_48px_rgba(5,18,34,.48)] ring-1 ring-cyan-200/10">
            <div class="h-16 shrink-0 px-5 flex items-center gap-2 border-b border-cyan-900/50">
                <img
                    :src="logoUrl"
                    alt="AuxFin logo"
                    class="h-9 w-9 rounded-xl object-cover shadow-[0_8px_16px_rgba(0,0,0,.22)]"
                >
                <h1 class="text-lg font-extrabold tracking-wide" :class="collapsed ? 'md:hidden' : ''">AuxFin</h1>
            </div>

            <nav class="flex-1 min-h-0 overflow-y-auto overscroll-contain p-3">
                <section v-for="(section, index) in orderedSections" :key="section.key" class="space-y-1">
                    <div class="flex items-center justify-between px-3 pt-3 pb-1">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-cyan-200/55" :class="collapsed ? 'md:hidden' : ''">
                            {{ section.title }}
                        </p>

                        <div v-if="orderedSections.length > 1" class="flex items-center gap-1 md:hidden">
                            <button
                                type="button"
                                class="inline-flex h-5 w-5 items-center justify-center rounded border border-white/15 text-[11px] text-cyan-100/80 disabled:opacity-35"
                                :disabled="index === 0"
                                aria-label="Move section up"
                                @click.stop="moveSection(section.key, -1)"
                            >
                                ↑
                            </button>
                            <button
                                type="button"
                                class="inline-flex h-5 w-5 items-center justify-center rounded border border-white/15 text-[11px] text-cyan-100/80 disabled:opacity-35"
                                :disabled="index === (orderedSections.length - 1)"
                                aria-label="Move section down"
                                @click.stop="moveSection(section.key, 1)"
                            >
                                ↓
                            </button>
                        </div>
                    </div>

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
                </section>
            </nav>

            <div class="m-3 shrink-0 rounded-xl border border-cyan-900/45 bg-(--bg-dark-card) p-3 shadow-[0_10px_20px_rgba(0,0,0,.25)]">
                <div class="flex items-center gap-2" :class="collapsed ? 'md:justify-center' : ''">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/14 text-xs font-bold text-cyan-50">
                        {{ initials }}
                    </span>
                    <div :class="collapsed ? 'md:hidden' : ''">
                        <p class="text-xs uppercase tracking-wide text-cyan-200/60">Signed In</p>
                        <p class="mt-0.5 max-w-42 truncate text-sm font-semibold text-white">{{ auth.user?.name ?? 'User' }}</p>
                    </div>
                </div>

                <button
                    type="button"
                    class="mt-2 w-full rounded-lg bg-white/12 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/20"
                    :class="collapsed ? 'md:hidden' : ''"
                    @click="logout"
                >
                    Logout
                </button>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
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
const sectionOrder = ref([]);
const logoUrl = '/images/logo.jpg';

const initials = computed(() => {
    const source = String(auth.user?.name ?? 'U').trim();
    if (!source) {
        return 'U';
    }

    return source
        .split(/\s+/)
        .slice(0, 2)
        .map((chunk) => chunk.charAt(0).toUpperCase())
        .join('');
});

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
                key: 'portal',
                title: 'Portal',
                items: [
                    { label: 'Dashboard', to: '/portal/dashboard' },
                    { label: 'My Salary', to: '/portal/salary' },
                    { label: 'Loans', to: '/portal/loans' },
                    { label: 'Leaves', to: '/portal/leaves' },
                    { label: 'Attendance', to: '/portal/attendance' },
                    { label: 'Inbox', to: '/portal/inbox', withMessageBadge: true },
                    { label: 'Docs Manual', to: '/portal/docs' },
                ],
            },
        ];
    }

    return [
        {
            key: 'operations',
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
            key: 'insights',
            title: 'Insights',
            items: [
                { label: 'Analytics', to: '/admin/analytics' },
                { label: 'Growth', to: '/admin/growth' },
                { label: 'Reports', to: '/admin/reports' },
                { label: 'Accounting', to: '/admin/accounting' },
            ],
        },
        {
            key: 'communication',
            title: 'Communication',
            items: [
                { label: 'Messages', to: '/admin/messages', withMessageBadge: true },
                { label: 'Settings', to: '/admin/settings' },
                { label: 'Docs Manual', to: '/admin/docs' },
            ],
        },
    ];
});

const orderedSections = computed(() => {
    const map = new Map(sections.value.map((section) => [section.key, section]));
    const knownKeys = sections.value.map((section) => section.key);

    const orderedKeys = [
        ...sectionOrder.value.filter((key) => map.has(key)),
        ...knownKeys.filter((key) => !sectionOrder.value.includes(key)),
    ];

    return orderedKeys.map((key) => map.get(key)).filter(Boolean);
});

function sectionOrderStorageKey() {
    return `auxfin_sidebar_section_order_${props.role}`;
}

function normalizeOrder(nextOrder, fallbackKeys) {
    const base = Array.isArray(nextOrder) ? nextOrder : [];

    return [
        ...base.filter((key) => fallbackKeys.includes(key)),
        ...fallbackKeys.filter((key) => !base.includes(key)),
    ];
}

function loadOrderForRole() {
    const fallbackKeys = sections.value.map((section) => section.key);

    try {
        const raw = localStorage.getItem(sectionOrderStorageKey());
        if (!raw) {
            return fallbackKeys;
        }

        const parsed = JSON.parse(raw);
        return normalizeOrder(parsed, fallbackKeys);
    } catch {
        return fallbackKeys;
    }
}

function persistOrderForRole() {
    try {
        localStorage.setItem(sectionOrderStorageKey(), JSON.stringify(sectionOrder.value));
    } catch {
        // Ignore storage errors in restricted browser contexts.
    }
}

function moveSection(key, direction) {
    const current = [...sectionOrder.value];
    const index = current.indexOf(key);

    if (index < 0) {
        return;
    }

    const target = index + direction;
    if (target < 0 || target >= current.length) {
        return;
    }

    [current[index], current[target]] = [current[target], current[index]];
    sectionOrder.value = current;
    persistOrderForRole();
}

watch(
    () => props.role,
    () => {
        sectionOrder.value = loadOrderForRole();
    },
    { immediate: true },
);

watch(
    () => sections.value.map((section) => section.key),
    (keys) => {
        sectionOrder.value = normalizeOrder(sectionOrder.value, keys);
        persistOrderForRole();
    },
);
</script>
