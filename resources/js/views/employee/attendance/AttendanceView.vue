<template>
    <section class="space-y-4">
        <div class="flex items-end gap-3">
            <div>
                <label class="text-xs font-semibold text-slate-600">Month</label>
                <input v-model="month" type="date" class="block mt-1 rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold" @click="load">Load</button>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="grid sm:grid-cols-6 gap-3 text-sm">
                <div class="rounded-lg bg-slate-100 p-3">Expected: <strong>{{ summary.expected_working_days ?? 0 }}</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">Present: <strong>{{ summary.days_present ?? 0 }}</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">Absent: <strong>{{ summary.days_absent ?? 0 }}</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">Late: <strong>{{ summary.late_entries ?? 0 }}</strong></div>
                <div class="rounded-lg bg-rose-100 p-3">Late Deduction: <strong>{{ number(summary.late_deduction_applied) }}</strong></div>
                <div class="rounded-lg bg-amber-100 p-3">Remaining Late Budget: <strong>{{ summary.remaining_late_budget_before_next_deduction ?? 0 }}</strong></div>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Date</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Late</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="record in records" :key="record.id" class="border-t border-slate-100">
                        <td class="p-3">{{ record.date }}</td>
                        <td class="p-3">{{ record.status }}</td>
                        <td class="p-3">{{ record.is_late ? 'Yes' : 'No' }}</td>
                    </tr>
                </tbody>
            </table>
        </article>
    </section>
</template>

<script setup>
import { ref } from 'vue';
import { AttendanceService } from '../../../services/attendance.service';
import { getApiErrorMessage } from '../../../utils/api-error';
import { useToastStore } from '../../../stores/toast.store';

const month = ref(new Date().toISOString().slice(0, 10));
const records = ref([]);
const summary = ref({});
const toast = useToastStore();

async function load() {
    try {
        const response = await AttendanceService.employeeList({ month: month.value });
        records.value = response.data.records;
        summary.value = response.data.summary;
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load attendance.'));
    }
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}

load();
</script>
