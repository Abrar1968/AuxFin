<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Time & Presence</p>
                <h1 class="text-2xl font-black text-slate-900">Attendance Management Hub</h1>
                <p class="mt-1 text-sm text-slate-600">Review attendance outcomes, apply corrections, and keep payroll-impact signals accurate.</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-right">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Selected Period</p>
                <p class="mt-1 text-lg font-black text-slate-900">{{ monthLabel }}</p>
            </div>
        </header>

        <article class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Employee</label>
                        <select v-model="employeeId" class="mt-1 block rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                            <option value="">Select Employee</option>
                            <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                {{ employee.employee_code }} - {{ employee.user?.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Month</label>
                        <input v-model="month" type="date" class="mt-1 block rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    </div>
                </div>

                <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700" @click="load">
                    Load Records
                </button>
            </div>
        </article>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Expected Days</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ summary?.expected_working_days ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-emerald-700">Present</p>
                <p class="mt-2 text-2xl font-black text-emerald-800">{{ summary?.days_present ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-amber-700">Late Entries</p>
                <p class="mt-2 text-2xl font-black text-amber-800">{{ summary?.late_entries ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose-700">Late Deduction</p>
                <p class="mt-2 text-2xl font-black text-rose-800">{{ number(summary?.late_deduction_applied) }}</p>
            </article>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Update Attendance</h2>
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

        <article class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <header class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Attendance Register</h3>
            </header>
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
                    <tr v-for="record in records" :key="record.id" class="border-t border-slate-100 hover:bg-slate-50/70">
                        <td class="p-3">{{ record.date }}</td>
                        <td class="p-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize" :class="statusClass(record.status)">
                                {{ record.status }}
                            </span>
                        </td>
                        <td class="p-3">{{ record.check_in ?? '-' }}</td>
                        <td class="p-3">{{ record.check_out ?? '-' }}</td>
                        <td class="p-3">{{ record.late_minutes ?? 0 }}</td>
                        <td class="p-3 text-right space-x-3">
                            <button class="text-xs font-semibold text-amber-700" @click="editRecord(record)">Edit</button>
                            <button class="text-xs font-semibold text-rose-700" @click="openDeleteModal(record.id)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="records.length === 0">
                        <td colspan="6" class="p-4 text-center text-slate-500">No attendance rows found for this period.</td>
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
import { computed, onMounted, reactive, ref } from 'vue';
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

function statusClass(status) {
    const value = String(status ?? '').toLowerCase();

    if (value === 'present') {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (value === 'late') {
        return 'bg-amber-100 text-amber-700';
    }

    if (value === 'absent') {
        return 'bg-rose-100 text-rose-700';
    }

    if (value === 'weekly_off' || value === 'holiday') {
        return 'bg-indigo-100 text-indigo-700';
    }

    return 'bg-slate-100 text-slate-700';
}
</script>
