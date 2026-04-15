<template>
    <section class="space-y-4">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs font-semibold text-slate-600">Employee</label>
                <select v-model="employeeId" class="block mt-1 rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Select Employee</option>
                    <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                        {{ employee.employee_code }} - {{ employee.user?.name }}
                    </option>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600">Month</label>
                <input v-model="month" type="date" class="block mt-1 rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold" @click="load">Load</button>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="font-bold">Update Attendance</h3>
            <form class="mt-3 grid md:grid-cols-3 gap-3" @submit.prevent="saveRecord">
                <input v-model="form.date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <select v-model="form.status" required class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="present">Present</option>
                    <option value="late">Late</option>
                    <option value="absent">Absent</option>
                    <option value="weekly_off">Weekly Off</option>
                    <option value="holiday">Holiday</option>
                </select>
                <input v-model="form.late_minutes" type="number" min="0" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Late minutes">
                <input v-model="form.check_in" type="time" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Check In">
                <input v-model="form.check_out" type="time" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Check Out">
                <button class="rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-semibold">Save</button>
            </form>
        </article>

        <article v-if="summary" class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="grid sm:grid-cols-4 gap-3 text-sm">
                <div class="rounded-lg bg-slate-100 p-3">Expected: <strong>{{ summary.expected_working_days ?? 0 }}</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">Present: <strong>{{ summary.days_present ?? 0 }}</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">Late: <strong>{{ summary.late_entries ?? 0 }}</strong></div>
                <div class="rounded-lg bg-amber-100 p-3">Late Deduction: <strong>{{ number(summary.late_deduction_applied) }}</strong></div>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Date</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Check In</th>
                        <th class="text-left p-3">Check Out</th>
                        <th class="text-left p-3">Late Minutes</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="record in records" :key="record.id" class="border-t border-slate-100">
                        <td class="p-3">{{ record.date }}</td>
                        <td class="p-3">{{ record.status }}</td>
                        <td class="p-3">{{ record.check_in ?? '-' }}</td>
                        <td class="p-3">{{ record.check_out ?? '-' }}</td>
                        <td class="p-3">{{ record.late_minutes ?? 0 }}</td>
                        <td class="p-3 text-right space-x-3">
                            <button class="text-xs font-semibold text-amber-700" @click="editRecord(record)">Edit</button>
                            <button class="text-xs font-semibold text-rose-700" @click="openDeleteModal(record.id)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </article>

        <ConfirmModal
            v-model="showDeleteModal"
            title="Delete Attendance Record"
            message="Are you sure you want to delete this attendance record? This action cannot be undone."
            confirm-text="Delete Record"
            tone="danger"
            @confirm="confirmDeleteRecord"
        />
    </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import ConfirmModal from '../../../components/ui/ConfirmModal.vue';
import { AttendanceService } from '../../../services/attendance.service';
import { EmployeeService } from '../../../services/employee.service';
import { getApiErrorMessage } from '../../../utils/api-error';
import { useToastStore } from '../../../stores/toast.store';

const employees = ref([]);
const employeeId = ref('');
const month = ref(new Date().toISOString().slice(0, 10));
const records = ref([]);
const summary = ref(null);
const showDeleteModal = ref(false);
const deleteRecordId = ref(null);
const toast = useToastStore();
const form = reactive({
    date: new Date().toISOString().slice(0, 10),
    status: 'present',
    late_minutes: '',
    check_in: '',
    check_out: '',
});

onMounted(async () => {
    try {
        const response = await EmployeeService.list({ per_page: 200 });
        employees.value = response.data.data ?? [];

        if (employees.value.length > 0) {
            employeeId.value = String(employees.value[0].id);
            await load();
        }
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load employees for attendance.'));
    }
});

async function load() {
    if (!employeeId.value) {
        return;
    }

    try {
        const response = await AttendanceService.adminMonth({
            employee_id: Number(employeeId.value),
            month: month.value,
        });

        records.value = response.data.records ?? [];
        summary.value = response.data.summary ?? null;
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load attendance records.'));
    }
}

async function saveRecord() {
    if (!employeeId.value) {
        return;
    }

    try {
        await AttendanceService.adminUpsert({
            employee_id: Number(employeeId.value),
            date: form.date,
            status: form.status,
            late_minutes: form.late_minutes === '' ? undefined : Number(form.late_minutes),
            check_in: form.check_in || undefined,
            check_out: form.check_out || undefined,
        });

        toast.success('Attendance record saved.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to save attendance record.'));
    }
}

function editRecord(record) {
    form.date = record.date;
    form.status = record.status;
    form.late_minutes = record.late_minutes ?? '';
    form.check_in = record.check_in ?? '';
    form.check_out = record.check_out ?? '';
}

function openDeleteModal(id) {
    deleteRecordId.value = id;
    showDeleteModal.value = true;
}

async function confirmDeleteRecord() {
    if (!deleteRecordId.value) {
        return;
    }

    try {
        await AttendanceService.adminDelete(deleteRecordId.value);
        showDeleteModal.value = false;
        deleteRecordId.value = null;
        toast.success('Attendance record deleted.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to delete attendance record.'));
    }
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}
</script>
