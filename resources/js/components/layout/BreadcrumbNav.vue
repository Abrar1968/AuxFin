<template>
    <nav class="fin-glass flex items-center gap-1 overflow-x-auto rounded-xl px-3 py-2 text-xs text-slate-600 shadow-sm">
        <RouterLink to="/" class="whitespace-nowrap font-semibold text-sky-700/70 hover:text-sky-800">Home</RouterLink>
        <template v-for="(crumb, idx) in crumbs" :key="crumb.to + idx">
            <span class="text-slate-400">/</span>
            <RouterLink
                v-if="idx < crumbs.length - 1"
                :to="crumb.to"
                class="whitespace-nowrap font-medium text-slate-600 hover:text-slate-900"
            >
                {{ crumb.label }}
            </RouterLink>
            <span v-else class="whitespace-nowrap font-semibold text-slate-900">{{ crumb.label }}</span>
        </template>
    </nav>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, RouterLink } from 'vue-router';

const route = useRoute();

const titleMap = {
    admin: 'Admin',
    portal: 'Portal',
    dashboard: 'Dashboard',
    employees: 'Employees',
    create: 'Create',
    payroll: 'Payroll',
    payslip: 'Payslip',
    loans: 'Loans',
    projects: 'Projects',
    expenses: 'Expenses',
    liabilities: 'Liabilities',
    assets: 'Assets',
    leaves: 'Leaves',
    attendance: 'Attendance',
    settings: 'Settings',
    analytics: 'Analytics',
    growth: 'Growth',
    reports: 'Reports',
    messages: 'Messages',
    inbox: 'Inbox',
    salary: 'Salary',
};

const crumbs = computed(() => {
    const parts = route.path.split('/').filter(Boolean);
    const result = [];

    for (let i = 0; i < parts.length; i += 1) {
        const path = `/${parts.slice(0, i + 1).join('/')}`;
        const key = parts[i];
        result.push({
            to: path,
            label: titleMap[key] ?? key.charAt(0).toUpperCase() + key.slice(1),
        });
    }

    return result;
});
</script>
