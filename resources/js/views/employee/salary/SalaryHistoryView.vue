<template>
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <p class="text-sm text-slate-600">View monthly salary and open the full payslip detail page for each record.</p>
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white" @click="load">Refresh</button>
        </div>

        <article class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="p-3 text-left">Month</th>
                        <th class="p-3 text-left">Gross</th>
                        <th class="p-3 text-left">Deductions</th>
                        <th class="p-3 text-left">Net</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100">
                        <td class="p-3">{{ formatMonth(row.month) }}</td>
                        <td class="p-3">{{ formatCurrency(row.gross_earnings) }}</td>
                        <td class="p-3">{{ formatCurrency(row.total_deductions) }}</td>
                        <td class="p-3 font-semibold">{{ formatCurrency(row.net_payable) }}</td>
                        <td class="p-3">{{ row.status }}</td>
                        <td class="space-x-3 p-3 text-right">
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

                    <tr v-if="rows.length === 0">
                        <td colspan="6" class="p-4 text-center text-slate-500">No salary records found.</td>
                    </tr>
                </tbody>
            </table>
        </article>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { PayrollService } from '../../../services/payroll.service';
import { useToastStore } from '../../../stores/toast.store';
import { getApiErrorMessage } from '../../../utils/api-error';
import { formatCurrency, formatMonth } from '../../../utils/formatters';

const rows = ref([]);
const busyMonth = ref('');
const router = useRouter();
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

function openPayslip(month) {
    router.push({
        name: 'employee.salary.payslip',
        params: { month: normalizeMonth(month) },
    });
}

async function downloadPdf(month) {
    const normalizedMonth = normalizeMonth(month);
    busyMonth.value = normalizedMonth;

    try {
        const response = await PayrollService.getPayslipPdfPayload(normalizedMonth);
        const { downloadPayslipPdf } = await import('../../../utils/payslip-pdf');
        downloadPayslipPdf(response.data.payslip, response.data.filename);
        toast.success('Payslip PDF download started.');
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to generate payslip PDF.'));
    } finally {
        busyMonth.value = '';
    }
}

function normalizeMonth(value) {
    return String(value ?? '').slice(0, 10);
}
</script>
