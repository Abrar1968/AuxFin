<template>
    <section class="space-y-5">
        <header>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900">Employee Dashboard</h1>
            <p class="text-sm text-slate-600 mt-1">Track your salary, deductions, loan progress, and attendance impact in one place.</p>
        </header>

        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
            <KpiCard
                title="Current Net Salary"
                :value="number(metrics.current_month_net_salary)"
                :delta="statusLabel"
                delta-type="up"
                :sparkline-data="salarySparkline"
            />
            <KpiCard
                title="Total Earned YTD"
                :value="number(metrics.total_earned_ytd)"
                :delta="'+YTD'"
                delta-type="up"
                :sparkline-data="earnedSparkline"
            />
            <KpiCard
                title="Total Deducted YTD"
                :value="number(metrics.total_deducted_ytd)"
                :delta="'-YTD'"
                delta-type="down"
                :sparkline-data="deductionSparkline"
            />
        </div>

        <div class="grid lg:grid-cols-2 gap-4">
            <LoanStatusCard
                title="Loan Balance"
                status="active"
                :approved-amount="loanApprovedAmount"
                :outstanding="metrics.outstanding_loan_balance"
            />

            <article class="fin-card-panel p-5">
                <h3 class="text-base font-bold text-slate-900">Attendance Summary</h3>
                <p class="text-sm text-slate-600 mt-1">Expected {{ attendance.expected_working_days ?? 0 }} days this month.</p>

                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg bg-slate-100 p-3">Present: <strong>{{ attendance.days_present ?? 0 }}</strong></div>
                    <div class="rounded-lg bg-slate-100 p-3">Absent: <strong>{{ attendance.days_absent ?? 0 }}</strong></div>
                    <div class="rounded-lg bg-amber-100 p-3">Late: <strong>{{ attendance.late_entries ?? 0 }}</strong></div>
                    <div class="rounded-lg bg-rose-100 p-3">Deduction: <strong>{{ number(attendance.late_deduction_applied) }}</strong></div>
                </div>

                <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-200">
                    <div class="h-full rounded-full bg-(image:--color-gradient)" :style="{ width: `${attendanceProgress}%` }"></div>
                </div>

                <p class="text-xs text-slate-600 mt-2">Attendance completion: {{ attendanceProgress.toFixed(0) }}%</p>
            </article>
        </div>

        <AppAlert v-if="attendance.remaining_late_budget_before_next_deduction !== undefined" type="warning">
            Remaining late budget before next deduction: {{ attendance.remaining_late_budget_before_next_deduction }}
        </AppAlert>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import LoanStatusCard from '../../components/domain/loans/LoanStatusCard.vue';
import AppAlert from '../../components/ui/AppAlert.vue';
import KpiCard from '../../components/ui/KpiCard.vue';
import { EmployeeService } from '../../services/employee.service';
import { getApiErrorMessage } from '../../utils/api-error';
import { useToastStore } from '../../stores/toast.store';

const metrics = ref({});
const toast = useToastStore();

const attendance = computed(() => metrics.value.attendance_summary ?? {});
const statusLabel = computed(() => String(metrics.value.current_month_status ?? 'pending').toUpperCase());

const loanApprovedAmount = computed(() => {
    const outstanding = Number(metrics.value.outstanding_loan_balance ?? 0);
    return Math.max(outstanding, outstanding * 1.35);
});

const attendanceProgress = computed(() => {
    const expected = Number(attendance.value.expected_working_days ?? 0);
    const present = Number(attendance.value.days_present ?? 0);
    if (expected <= 0) return 0;
    return Math.max(0, Math.min(100, (present / expected) * 100));
});

const salarySparkline = computed(() => {
    const current = Number(metrics.value.current_month_net_salary ?? 0);
    return [current * 0.82, current * 0.9, current * 0.96, current];
});

const earnedSparkline = computed(() => {
    const total = Number(metrics.value.total_earned_ytd ?? 0);
    return [total * 0.24, total * 0.42, total * 0.7, total];
});

const deductionSparkline = computed(() => {
    const total = Number(metrics.value.total_deducted_ytd ?? 0);
    return [total * 0.2, total * 0.41, total * 0.72, total];
});

onMounted(async () => {
    try {
        const response = await EmployeeService.dashboard();
        metrics.value = response.data;
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load employee dashboard.'));
    }
});

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}
</script>
