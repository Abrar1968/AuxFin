import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth.store';

const routes = [
    {
        path: '/login',
        component: () => import('../layouts/AuthLayout.vue'),
        children: [
            {
                path: '',
                name: 'login',
                component: () => import('../views/auth/LoginView.vue'),
                meta: { public: true },
            },
        ],
    },
    {
        path: '/admin',
        component: () => import('../layouts/AdminLayout.vue'),
        meta: { requiresAuth: true, role: 'admin' },
        children: [
            { path: 'dashboard', name: 'admin.dashboard', component: () => import('../views/admin/DashboardView.vue') },
            { path: 'employees', name: 'admin.employees', component: () => import('../views/admin/employees/EmployeeListView.vue') },
            { path: 'payroll', name: 'admin.payroll', component: () => import('../views/admin/payroll/PayrollView.vue') },
            { path: 'loans', name: 'admin.loans', component: () => import('../views/admin/loans/LoanManagementView.vue') },
            { path: 'projects', name: 'admin.projects', component: () => import('../views/admin/projects/ProjectListView.vue') },
            { path: 'projects/:id/invoices', name: 'admin.project.invoices', component: () => import('../views/admin/projects/InvoiceView.vue') },
            { path: 'expenses', name: 'admin.expenses', component: () => import('../views/admin/expenses/ExpenseView.vue') },
            { path: 'liabilities', name: 'admin.liabilities', component: () => import('../views/admin/liabilities/LiabilityView.vue') },
            { path: 'assets', name: 'admin.assets', component: () => import('../views/admin/assets/AssetView.vue') },
            { path: 'leaves', name: 'admin.leaves', component: () => import('../views/admin/leaves/LeaveManagementView.vue') },
            { path: 'attendance', name: 'admin.attendance', component: () => import('../views/admin/attendance/AttendanceManagementView.vue') },
            { path: 'settings', name: 'admin.settings', component: () => import('../views/admin/settings/PayrollSettingsView.vue') },
            { path: 'analytics', name: 'admin.analytics', component: () => import('../views/admin/analytics/AnalyticsOverviewView.vue') },
            { path: 'growth', name: 'admin.growth', component: () => import('../views/admin/analytics/GrowthDashboardView.vue') },
            { path: 'messages', name: 'admin.messages', component: () => import('../views/admin/messages/MessageCenterView.vue') },
        ],
    },
    {
        path: '/portal',
        component: () => import('../layouts/EmployeeLayout.vue'),
        meta: { requiresAuth: true, role: 'employee' },
        children: [
            { path: 'dashboard', name: 'employee.dashboard', component: () => import('../views/employee/DashboardView.vue') },
            { path: 'salary', name: 'employee.salary', component: () => import('../views/employee/salary/SalaryHistoryView.vue') },
            { path: 'loans', name: 'employee.loans', component: () => import('../views/employee/loans/LoanStatusView.vue') },
            { path: 'loans/apply', name: 'employee.loan.apply', component: () => import('../views/employee/loans/LoanApplyView.vue') },
            { path: 'leaves', name: 'employee.leaves', component: () => import('../views/employee/leaves/LeaveView.vue') },
            { path: 'attendance', name: 'employee.attendance', component: () => import('../views/employee/attendance/AttendanceView.vue') },
            { path: 'inbox', name: 'employee.inbox', component: () => import('../views/employee/messages/InboxView.vue') },
        ],
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/login',
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (auth.token && !auth.user) {
        try {
            await auth.fetchMe();
        } catch {
            return '/login';
        }
    }

    if (to.meta.public) {
        if (auth.token && auth.role) {
            return auth.role === 'employee' ? '/portal/dashboard' : '/admin/dashboard';
        }

        return true;
    }

    if (to.meta.requiresAuth && !auth.token) {
        return '/login';
    }

    if (to.meta.role === 'admin' && auth.role === 'employee') {
        return '/portal/dashboard';
    }

    if (to.meta.role === 'employee' && auth.role !== 'employee') {
        return '/admin/dashboard';
    }

    return true;
});

export default router;
