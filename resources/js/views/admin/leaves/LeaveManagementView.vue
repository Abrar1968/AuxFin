<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Leave Governance</p>
                <h1 class="text-2xl font-black text-slate-900">Leave Management Desk</h1>
                <p class="mt-1 text-sm text-slate-600">Oversee leave intake, approvals, and policy-aligned decision actions.</p>
            </div>

            <button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="load">
                Refresh Requests
            </button>
        </header>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Total Requests</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ rows.length }}</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-amber-700">Pending</p>
                <p class="mt-2 text-2xl font-black text-amber-800">{{ pendingCount }}</p>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-emerald-700">Approved</p>
                <p class="mt-2 text-2xl font-black text-emerald-800">{{ approvedCount }}</p>
            </article>
            <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose-700">Rejected</p>
                <p class="mt-2 text-2xl font-black text-rose-800">{{ rejectedCount }}</p>
            </article>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Create Leave Record</h2>
            <form class="mt-3 grid md:grid-cols-4 gap-3" @submit.prevent="createLeave">
                <select v-model="createForm.employee_id" required class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Select employee</option>
                    <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                        {{ employee.employee_code }} - {{ employee.user?.name }}
                    </option>
                </select>
                <select v-model="createForm.leave_type" required class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="casual">Casual</option>
                    <option value="sick">Sick</option>
                    <option value="earned">Earned</option>
                    <option value="unpaid">Unpaid</option>
                </select>
                <input v-model="createForm.from_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <input v-model="createForm.to_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <textarea
                    v-model="createForm.reason"
                    required
                    rows="2"
                    class="md:col-span-3 rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Reason"
                ></textarea>
                <button class="rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-semibold">Create Leave</button>
            </form>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Status Filter</label>
                <select v-model="status" class="mt-1 block rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700" @click="load">Refresh</button>
            </div>
        </article>

        <article class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <header class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Leave Request Ledger</h3>
            </header>
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Employee</th>
                        <th class="text-left p-3">Type</th>
                        <th class="text-left p-3">Range</th>
                        <th class="text-left p-3">Days</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Admin Note</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100 hover:bg-slate-50/70">
                        <td class="p-3">{{ row.employee?.user?.name }}</td>
                        <td class="p-3 capitalize">{{ row.leave_type }}</td>
                        <td class="p-3">{{ row.from_date }} - {{ row.to_date }}</td>
                        <td class="p-3">{{ row.days }}</td>
                        <td class="p-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize" :class="statusClass(row.status)">
                                {{ row.status }}
                            </span>
                        </td>
                        <td class="p-3">{{ row.admin_note ?? '-' }}</td>
                        <td class="p-3 text-right space-x-3">
                            <button
                                v-if="row.status === 'pending'"
                                class="text-xs font-semibold text-emerald-700"
                                @click="openDecisionModal(row.id, 'approved')"
                            >
                                Approve
                            </button>
                            <button
                                v-if="row.status === 'pending'"
                                class="text-xs font-semibold text-rose-700"
                                @click="openDecisionModal(row.id, 'rejected')"
                            >
                                Reject
                            </button>
                            <button
                                v-if="['pending', 'rejected'].includes(row.status)"
                                class="text-xs font-semibold text-amber-700"
                                @click="openEditModal(row)"
                            >
                                Edit
                            </button>
                            <button
                                v-if="row.status !== 'approved'"
                                class="text-xs font-semibold text-rose-700"
                                @click="openDeleteLeaveModal(row.id)"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td colspan="7" class="p-4 text-center text-slate-500">No leave requests found for current filter.</td>
                    </tr>
                </tbody>
            </table>
        </article>

        <AppModal v-model="showEditModal" title="Edit Leave Record" size="md">
            <form class="grid gap-3" @submit.prevent="submitEditLeave">
                <select v-model="editForm.leave_type" required class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="casual">Casual</option>
                    <option value="sick">Sick</option>
                    <option value="earned">Earned</option>
                    <option value="unpaid">Unpaid</option>
                </select>
                <input v-model="editForm.from_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <input v-model="editForm.to_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <select v-model="editForm.status" required class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <textarea
                    v-model="editForm.reason"
                    required
                    rows="3"
                    class="rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Reason"
                ></textarea>
                <textarea
                    v-model="editForm.admin_note"
                    rows="2"
                    class="rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Admin note (optional)"
                ></textarea>

                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="showEditModal = false">Cancel</button>
                    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Save Changes</button>
                </div>
            </form>
        </AppModal>

        <AppModal v-model="showDecisionModal" :title="decisionForm.status === 'approved' ? 'Approve Leave' : 'Reject Leave'" size="sm">
            <form class="grid gap-3" @submit.prevent="submitDecision">
                <p class="text-sm text-slate-600">
                    You are about to mark this leave request as
                    <strong class="capitalize">{{ decisionForm.status }}</strong>.
                </p>
                <textarea
                    v-model="decisionForm.admin_note"
                    :required="decisionForm.status === 'rejected'"
                    rows="3"
                    class="rounded-lg border border-slate-300 px-3 py-2"
                    :placeholder="decisionForm.status === 'rejected' ? 'Rejection reason' : 'Admin note (optional)'"
                ></textarea>

                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="showDecisionModal = false">Cancel</button>
                    <button
                        class="rounded-lg px-4 py-2 text-sm font-semibold text-white"
                        :class="decisionForm.status === 'approved' ? 'bg-emerald-600' : 'bg-rose-600'"
                    >
                        Confirm
                    </button>
                </div>
            </form>
        </AppModal>

        <ConfirmModal
            v-model="showDeleteModal"
            title="Delete Leave Record"
            message="Are you sure you want to delete this leave record?"
            confirm-text="Delete Leave"
            tone="danger"
            @confirm="confirmDeleteLeave"
        />
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AppModal from '../../../components/ui/AppModal.vue';
import ConfirmModal from '../../../components/ui/ConfirmModal.vue';
import { EmployeeService } from '../../../services/employee.service';
import { LeaveService } from '../../../services/leave.service';
import { getApiErrorMessage } from '../../../utils/api-error';
import { useToastStore } from '../../../stores/toast.store';

const status = ref('pending');
const employees = ref([]);
const rows = ref([]);
const showEditModal = ref(false);
const showDecisionModal = ref(false);
const showDeleteModal = ref(false);
const actionLeaveId = ref(null);
const deleteLeaveId = ref(null);
const toast = useToastStore();
const createForm = reactive({
    employee_id: '',
    leave_type: 'casual',
    from_date: new Date().toISOString().slice(0, 10),
    to_date: new Date().toISOString().slice(0, 10),
    reason: '',
});
const editForm = reactive({
    leave_type: 'casual',
    from_date: new Date().toISOString().slice(0, 10),
    to_date: new Date().toISOString().slice(0, 10),
    reason: '',
    status: 'pending',
    admin_note: '',
});
const decisionForm = reactive({
    status: 'approved',
    admin_note: '',
});

const pendingCount = computed(() => rows.value.filter((row) => String(row.status).toLowerCase() === 'pending').length);
const approvedCount = computed(() => rows.value.filter((row) => String(row.status).toLowerCase() === 'approved').length);
const rejectedCount = computed(() => rows.value.filter((row) => String(row.status).toLowerCase() === 'rejected').length);

onMounted(async () => {
    await loadEmployees();
    await load();
});

async function loadEmployees() {
    try {
        const response = await EmployeeService.list({ per_page: 200 });
        employees.value = response.data.data ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load employees.'));
    }
}

async function load() {
    try {
        const response = await LeaveService.adminList({ status: status.value || undefined });
        rows.value = response.data.data ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load leave requests.'));
    }
}

async function createLeave() {
    try {
        await LeaveService.adminCreate({
            employee_id: Number(createForm.employee_id),
            leave_type: createForm.leave_type,
            from_date: createForm.from_date,
            to_date: createForm.to_date,
            reason: createForm.reason,
            status: 'pending',
        });

        createForm.employee_id = '';
        createForm.leave_type = 'casual';
        createForm.from_date = new Date().toISOString().slice(0, 10);
        createForm.to_date = new Date().toISOString().slice(0, 10);
        createForm.reason = '';

        toast.success('Leave record created successfully.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to create leave record.'));
    }
}

function openEditModal(row) {
    actionLeaveId.value = row.id;
    editForm.leave_type = row.leave_type ?? 'casual';
    editForm.from_date = row.from_date ?? new Date().toISOString().slice(0, 10);
    editForm.to_date = row.to_date ?? new Date().toISOString().slice(0, 10);
    editForm.reason = row.reason ?? '';
    editForm.status = row.status ?? 'pending';
    editForm.admin_note = row.admin_note ?? '';
    showEditModal.value = true;
}

async function submitEditLeave() {
    if (!actionLeaveId.value) {
        return;
    }

    try {
        await LeaveService.adminUpdate(actionLeaveId.value, {
            leave_type: editForm.leave_type,
            from_date: editForm.from_date,
            to_date: editForm.to_date,
            reason: editForm.reason,
            status: editForm.status,
            admin_note: editForm.admin_note || null,
        });

        showEditModal.value = false;
        toast.success('Leave record updated successfully.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to update leave record.'));
    }
}

function openDecisionModal(id, nextStatus) {
    actionLeaveId.value = id;
    decisionForm.status = nextStatus;
    decisionForm.admin_note = '';
    showDecisionModal.value = true;
}

async function submitDecision() {
    if (!actionLeaveId.value) {
        return;
    }

    if (decisionForm.status === 'rejected' && !decisionForm.admin_note.trim()) {
        toast.warning('Rejection reason is required.');
        return;
    }

    try {
        await LeaveService.decide(actionLeaveId.value, {
            status: decisionForm.status,
            admin_note: decisionForm.admin_note || undefined,
        });

        showDecisionModal.value = false;
        toast.success(decisionForm.status === 'approved' ? 'Leave approved.' : 'Leave rejected.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to submit leave decision.'));
    }
}

function openDeleteLeaveModal(id) {
    deleteLeaveId.value = id;
    showDeleteModal.value = true;
}

async function confirmDeleteLeave() {
    if (!deleteLeaveId.value) {
        return;
    }

    try {
        await LeaveService.adminDelete(deleteLeaveId.value);
        showDeleteModal.value = false;
        deleteLeaveId.value = null;
        toast.success('Leave record deleted successfully.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to delete leave record.'));
    }
}

function statusClass(status) {
    const value = String(status ?? '').toLowerCase();

    if (value === 'approved') {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (value === 'pending') {
        return 'bg-amber-100 text-amber-700';
    }

    if (value === 'rejected') {
        return 'bg-rose-100 text-rose-700';
    }

    return 'bg-slate-100 text-slate-700';
}
</script>
