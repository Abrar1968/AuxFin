<template>
    <header class="sticky top-0 z-20 h-16 border-b border-sky-100/80 bg-white/70 backdrop-blur-xl">
        <div class="h-full flex items-center justify-between gap-3 px-4 md:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <button
                    type="button"
                    class="fin-focus-ring inline-flex h-10 w-10 items-center justify-center rounded-xl border border-sky-200/80 bg-white/80 text-slate-700 shadow-sm transition hover:-translate-y-px hover:bg-white"
                    @click="$emit('toggle-sidebar')"
                >
                    <span class="text-base">≡</span>
                </button>

                <div class="min-w-0">
                    <p class="text-[11px] uppercase tracking-[0.12em] text-sky-700/80">FinERP Workspace</p>
                    <h2 class="truncate text-lg font-bold text-(--text-primary)">{{ title }}</h2>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <NotificationBell />
                <div class="hidden sm:flex items-center gap-2 rounded-full border border-sky-200/80 bg-white/90 px-2 py-1 shadow-sm">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-(image:--color-gradient) text-xs font-bold text-white shadow-[0_6px_14px_rgba(2,132,199,.35)]">
                        {{ initials }}
                    </span>
                    <div class="pr-1">
                        <p class="text-xs font-semibold text-slate-800 leading-none">{{ auth.user?.name ?? 'Guest' }}</p>
                        <p class="mt-0.5 text-[11px] uppercase text-sky-700/70">{{ auth.role ?? 'guest' }}</p>
                    </div>
                </div>

                <button
                    type="button"
                    class="hidden sm:inline-flex rounded-xl border border-sky-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:-translate-y-px hover:bg-sky-50"
                    @click="onLogout"
                >
                    Logout
                </button>
            </div>
        </div>
    </header>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth.store';
import NotificationBell from './NotificationBell.vue';

defineEmits(['toggle-sidebar']);

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

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

const title = computed(() => {
    const map = {
        'admin.dashboard': 'Admin Dashboard',
        'admin.employees': 'Employee Management',
        'admin.employee.create': 'Create Employee',
        'admin.employee.detail': 'Employee Profile',
        'admin.payroll': 'Payroll Center',
        'admin.payroll.payslip': 'Payslip Detail',
        'admin.loans': 'Loan Management',
        'admin.projects': 'Projects & Clients',
        'admin.project.invoices': 'Project Invoices',
        'admin.expenses': 'Expense Management',
        'admin.liabilities': 'Liability Registry',
        'admin.assets': 'Asset Registry',
        'admin.leaves': 'Leave Management',
        'admin.attendance': 'Attendance Management',
        'admin.settings': 'Payroll Settings',
        'admin.analytics': 'Analytics Overview',
        'admin.growth': 'Growth Analytics',
        'admin.reports': 'Financial Reports',
        'admin.messages': 'Message Center',
        'employee.dashboard': 'Employee Dashboard',
        'employee.salary': 'Salary History',
        'employee.salary.payslip': 'Payslip Detail',
        'employee.loans': 'Loan Status',
        'employee.loan.apply': 'Apply Loan',
        'employee.leaves': 'Leave Requests',
        'employee.attendance': 'Attendance',
        'employee.inbox': 'Inbox',
    };

    return map[route.name] ?? 'FinERP';
});

async function onLogout() {
    await auth.logout();
    router.push('/login');
}
</script>
