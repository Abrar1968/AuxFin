<template>
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <p class="text-sm text-slate-600">View monthly salary and export payslip PDFs from your browser.</p>
            <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold" @click="load">Refresh</button>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Month</th>
                        <th class="text-left p-3">Gross</th>
                        <th class="text-left p-3">Deductions</th>
                        <th class="text-left p-3">Net</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100">
                        <td class="p-3">{{ row.month }}</td>
                        <td class="p-3">{{ number(row.gross_earnings) }}</td>
                        <td class="p-3">{{ number(row.total_deductions) }}</td>
                        <td class="p-3 font-semibold">{{ number(row.net_payable) }}</td>
                        <td class="p-3">{{ row.status }}</td>
                        <td class="p-3 text-right space-x-3">
                            <button
                                class="text-xs font-semibold text-blue-700"
                                :disabled="busyMonth === row.month"
                                @click="openPayslip(row.month)"
                            >
                                View
                            </button>
                            <button
                                class="text-xs font-semibold text-emerald-700"
                                :disabled="busyMonth === row.month"
                                @click="downloadPdf(row.month)"
                            >
                                PDF
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </article>

        <article v-if="selectedPayslip" class="rounded-2xl border border-slate-200 bg-white p-5 space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-bold">Payslip Summary</h3>
                    <p class="text-sm text-slate-600">{{ selectedPayslip.meta?.month }} | {{ selectedPayslip.meta?.status }}</p>
                </div>
                <div class="text-sm text-slate-700">
                    <p><strong>{{ selectedPayslip.employee?.name }}</strong></p>
                    <p>{{ selectedPayslip.employee?.employee_code }} | {{ selectedPayslip.employee?.designation }}</p>
                    <p>{{ selectedPayslip.employee?.department }}</p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div class="rounded-xl border border-slate-200 overflow-hidden">
                    <div class="bg-slate-100 px-3 py-2 font-semibold text-sm">Earnings</div>
                    <table class="w-full text-sm">
                        <tbody>
                            <tr v-for="(value, key) in selectedPayslip.earnings" :key="`earn-${key}`" class="border-t border-slate-100">
                                <td class="p-2">{{ label(key) }}</td>
                                <td class="p-2 text-right">{{ number(value) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="rounded-xl border border-slate-200 overflow-hidden">
                    <div class="bg-slate-100 px-3 py-2 font-semibold text-sm">Deductions</div>
                    <table class="w-full text-sm">
                        <tbody>
                            <tr v-for="(value, key) in selectedPayslip.deductions" :key="`ded-${key}`" class="border-t border-slate-100">
                                <td class="p-2">{{ label(key) }}</td>
                                <td class="p-2 text-right">{{ number(value) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid sm:grid-cols-3 gap-3 text-sm">
                <div class="rounded-lg bg-slate-100 p-3">Net Payable: <strong>{{ number(selectedPayslip.net_payable) }}</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">MoM Delta: <strong>{{ selectedPayslip.month_over_month_delta_percent }}%</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">Loan Remaining: <strong>{{ number(selectedPayslip.loan?.amount_remaining) }}</strong></div>
            </div>
        </article>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { PayrollService } from '../../../services/payroll.service';
import { getApiErrorMessage } from '../../../utils/api-error';
import { useToastStore } from '../../../stores/toast.store';

const rows = ref([]);
const selectedPayslip = ref(null);
const busyMonth = ref('');
const toast = useToastStore();

onMounted(async () => {
    await load();
});

async function load() {
    try {
        const response = await PayrollService.mySalary();
        rows.value = response.data.data ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load salary history.'));
    }
}

async function openPayslip(month) {
    busyMonth.value = month;
    try {
        const response = await PayrollService.getPayslip(month);
        selectedPayslip.value = response.data;
        toast.success('Payslip loaded successfully.');
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load payslip.'));
    } finally {
        busyMonth.value = '';
    }
}

async function downloadPdf(month) {
    busyMonth.value = month;
    try {
        const response = await PayrollService.getPayslipPdfPayload(month);
        const { downloadPayslipPdf } = await import('../../../utils/payslip-pdf');
        downloadPayslipPdf(response.data.payslip, response.data.filename);
        toast.success('Payslip PDF download started.');
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to generate payslip PDF.'));
    } finally {
        busyMonth.value = '';
    }
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}

function label(v) {
    return String(v)
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}
</script>
