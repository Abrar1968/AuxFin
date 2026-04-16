import { createRouter, createWebHistory } from 'vue-router';
import { applyAuthGuards } from './guards';

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
            { path: 'employees/create', name: 'admin.employee.create', component: () => import('../views/admin/employees/EmployeeCreateView.vue') },
            { path: 'employees/:id', name: 'admin.employee.detail', component: () => import('../views/admin/employees/EmployeeDetailView.vue') },
            { path: 'payroll', name: 'admin.payroll', component: () => import('../views/admin/payroll/PayrollView.vue') },
            { path: 'payroll/:id/payslip', name: 'admin.payroll.payslip', component: () => import('../views/admin/payroll/PayslipView.vue') },
            { path: 'loans', name: 'admin.loans', component: () => import('../views/admin/loans/LoanManagementView.vue') },
            { path: 'projects', name: 'admin.projects', component: () => import('../views/admin/projects/ProjectListView.vue') },
            { path: 'projects/:id/invoices', name: 'admin.project.invoices', component: () => import('../views/admin/projects/InvoiceView.vue') },
            { path: 'expenses', name: 'admin.expenses', component: () => import('../views/admin/expenses/ExpenseView.vue') },
            { path: 'liabilities', name: 'admin.liabilities', component: () => import('../views/admin/liabilities/LiabilityView.vue') },
            { path: 'assets', name: 'admin.assets', component: () => import('../views/admin/assets/AssetView.vue') },
            { path: 'leaves', name: 'admin.leaves', component: () => import('../views/admin/leaves/LeaveManagementView.vue') },
            { path: 'attendance', name: 'admin.attendance', component: () => import('../views/admin/attendance/AttendanceManagementView.vue') },
            { path: 'settings', name: 'admin.settings', component: () => import('../views/admin/settings/SettingsView.vue') },
            { path: 'analytics', name: 'admin.analytics', component: () => import('../views/admin/analytics/AnalyticsOverviewView.vue') },
            { path: 'growth', name: 'admin.growth', component: () => import('../views/admin/analytics/GrowthDashboardView.vue') },
            { path: 'reports', name: 'admin.reports', component: () => import('../views/admin/reports/ReportsView.vue') },
            { path: 'accounting', name: 'admin.accounting', component: () => import('../views/admin/accounting/AccountingView.vue') },
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
            { path: 'salary/:month', name: 'employee.salary.payslip', component: () => import('../views/employee/salary/PayslipDetailView.vue') },
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

applyAuthGuards(router);

export default router;
