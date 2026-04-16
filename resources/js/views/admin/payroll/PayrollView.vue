<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Compensation Ops</p>
                <h1 class="text-2xl font-black text-slate-900">Payroll Command Center</h1>
                <p class="mt-1 text-sm text-slate-600">Review salary outputs, control monthly run execution, and complete payment actions.</p>
            </div>

            <div class="rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-right">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-indigo-700">Active Payroll Month</p>
                <p class="mt-1 text-lg font-black text-indigo-900">{{ monthLabel }}</p>
            </div>
        </header>

        <article class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Month</label>
                        <input
                            v-model="month"
                            type="date"
                            class="mt-1 block rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                        >
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="load">
                        Refresh Payroll
                    </button>
                    <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" @click="bulk">
                        Bulk Process Month
                    </button>
                </div>
            </div>
        </article>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Total Gross</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ number(totalGross) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Total Deductions</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ number(totalDeductions) }}</p>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-emerald-700">Total Net Payable</p>
                <p class="mt-2 text-2xl font-black text-emerald-800">{{ number(totalNetPayable) }}</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-amber-700">Pending Payments</p>
                <p class="mt-2 text-2xl font-black text-amber-800">{{ pendingCount }}</p>
                <p class="mt-1 text-xs text-amber-700">{{ paidCount }} paid records</p>
            </article>
        </div>

        <article class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="p-3 text-left">Employee</th>
                        <th class="p-3 text-left">Gross</th>
                        <th class="p-3 text-left">Deductions</th>
                        <th class="p-3 text-left">Net</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100 hover:bg-slate-50/70">
                        <td class="p-3 font-semibold text-slate-900">{{ row.employee?.user?.name || '-' }}</td>
                        <td class="p-3">{{ number(row.gross_earnings) }}</td>
                        <td class="p-3">{{ number(row.total_deductions) }}</td>
                        <td class="p-3 font-semibold text-slate-900">{{ number(row.net_payable) }}</td>
                        <td class="p-3">
                            <span
                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize"
                                :class="statusClass(row.status)"
                            >
                                {{ row.status }}
                            </span>
                        </td>
                        <td class="space-x-3 p-3 text-right">
                            <button class="text-xs font-semibold text-indigo-700 hover:text-indigo-900" @click="openPayslip(row.id)">Payslip</button>
                            <button
                                class="text-xs font-semibold text-emerald-700 hover:text-emerald-900 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="String(row.status).toLowerCase() === 'paid'"
                                @click="markPaid(row.id)"
                            >
                                Mark Paid
                            </button>
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td colspan="6" class="p-4 text-center text-slate-500">No payroll rows found for this month.</td>
                    </tr>
                </tbody>
            </table>
        </article>
    </section>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { PayrollService } from '../../../services/payroll.service';
import { getApiErrorMessage } from '../../../utils/api-error';
import { useToastStore } from '../../../stores/toast.store';

const month = ref(new Date().toISOString().slice(0, 10));
const rows = ref([]);
const router = useRouter();
const toast = useToastStore();

const totalGross = computed(() => rows.value.reduce((sum, row) => sum + Number(row.gross_earnings ?? 0), 0));
const totalDeductions = computed(() => rows.value.reduce((sum, row) => sum + Number(row.total_deductions ?? 0), 0));
const totalNetPayable = computed(() => rows.value.reduce((sum, row) => sum + Number(row.net_payable ?? 0), 0));

const paidCount = computed(() => rows.value.filter((row) => String(row.status).toLowerCase() === 'paid').length);
const pendingCount = computed(() => rows.value.length - paidCount.value);

const monthLabel = computed(() => {
    if (!month.value) {
        return 'Not selected';
    }

    const [year, monthValue] = month.value.split('-');
    if (!year || !monthValue) {
        return month.value;
    }

    const date = new Date(Number(year), Number(monthValue) - 1, 1);
    return date.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
});

async function load() {
    try {
        const response = await PayrollService.getMonth(month.value);
        rows.value = response.data;
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load payroll month.'));
    }
}

async function bulk() {
    try {
        await PayrollService.bulkProcess(month.value);
        toast.success('Bulk payroll processing completed.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to process payroll in bulk.'));
    }
}

async function markPaid(id) {
    try {
        await PayrollService.markPaid(id);
        toast.success('Salary marked as paid.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to mark salary as paid.'));
    }
}

function openPayslip(id) {
    router.push({
        name: 'admin.payroll.payslip',
        params: { id },
    });
}

function statusClass(status) {
    const value = String(status ?? '').toLowerCase();

    if (value === 'paid') {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (value === 'processed') {
        return 'bg-indigo-100 text-indigo-700';
    }

    if (value === 'pending') {
        return 'bg-amber-100 text-amber-700';
    }

    return 'bg-slate-100 text-slate-700';
}

load();
</script>
