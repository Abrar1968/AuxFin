<template>
    <header class="h-16 bg-white border-b border-(--border) flex items-center justify-between px-4 md:px-6">
        <h2 class="text-lg font-semibold text-(--text-primary)">{{ title }}</h2>

        <div class="flex items-center gap-3">
            <NotificationBell />
            <span class="hidden sm:inline-flex px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
                {{ auth.role ?? 'guest' }}
            </span>
            <button
                type="button"
                class="px-3 py-1.5 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-700"
                @click="onLogout"
            >
                Logout
            </button>
        </div>
    </header>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth.store';
import NotificationBell from './NotificationBell.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const title = computed(() => {
    const map = {
        'admin.dashboard': 'Admin Dashboard',
        'admin.employees': 'Employee Management',
        'admin.payroll': 'Payroll Center',
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
        'admin.messages': 'Message Center',
        'employee.dashboard': 'Employee Dashboard',
        'employee.salary': 'Salary History',
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
