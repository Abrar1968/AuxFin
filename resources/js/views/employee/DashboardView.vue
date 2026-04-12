<template>
    <section class="space-y-4">
        <div class="grid md:grid-cols-3 lg:grid-cols-6 gap-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Current Net Salary</p>
                <p class="text-xl font-bold mt-1">{{ number(metrics.current_month_net_salary) }}</p>
                <span class="text-xs text-slate-500">{{ metrics.current_month_status }}</span>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Total Earned YTD</p>
                <p class="text-xl font-bold mt-1">{{ number(metrics.total_earned_ytd) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Total Deducted YTD</p>
                <p class="text-xl font-bold mt-1">{{ number(metrics.total_deducted_ytd) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Outstanding Loan</p>
                <p class="text-xl font-bold mt-1">{{ number(metrics.outstanding_loan_balance) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Late Entries (Month)</p>
                <p class="text-xl font-bold mt-1">{{ metrics.attendance_summary?.late_entries ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Late Deduction (Month)</p>
                <p class="text-xl font-bold mt-1">{{ number(metrics.attendance_summary?.late_deduction_applied) }}</p>
            </article>
        </div>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../../services/api.service';
import { getApiErrorMessage } from '../../utils/api-error';
import { useToastStore } from '../../stores/toast.store';

const metrics = ref({});
const toast = useToastStore();

onMounted(async () => {
    try {
        const response = await api.get('/employee/dashboard');
        metrics.value = response.data;
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load employee dashboard.'));
    }
});

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}
</script>
