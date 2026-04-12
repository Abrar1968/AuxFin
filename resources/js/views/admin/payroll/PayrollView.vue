<template>
    <section class="space-y-4">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs font-semibold text-slate-600">Month</label>
                <input v-model="month" type="date" class="block mt-1 rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold" @click="load">Load Payroll</button>
            <button class="rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-semibold" @click="bulk">Bulk Process</button>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Employee</th>
                        <th class="text-left p-3">Gross</th>
                        <th class="text-left p-3">Deductions</th>
                        <th class="text-left p-3">Net</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100">
                        <td class="p-3">{{ row.employee?.user?.name }}</td>
                        <td class="p-3">{{ row.gross_earnings }}</td>
                        <td class="p-3">{{ row.total_deductions }}</td>
                        <td class="p-3 font-semibold">{{ row.net_payable }}</td>
                        <td class="p-3">{{ row.status }}</td>
                        <td class="p-3 text-right space-x-3">
                            <button class="text-xs font-semibold text-indigo-700" @click="openPayslip(row.id)">Payslip</button>
                            <button class="text-xs font-semibold text-blue-700" @click="markPaid(row.id)">Mark Paid</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </article>
    </section>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { PayrollService } from '../../../services/payroll.service';
import { getApiErrorMessage } from '../../../utils/api-error';
import { useToastStore } from '../../../stores/toast.store';

const month = ref(new Date().toISOString().slice(0, 10));
const rows = ref([]);
const router = useRouter();
const toast = useToastStore();

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
</script>
