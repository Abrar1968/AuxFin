<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Credit Governance</p>
                <h1 class="text-2xl font-black text-slate-900">Loan Portfolio Control</h1>
                <p class="mt-1 text-sm text-slate-600">Approve, monitor, and manage employee credit lifecycle from request to repayment closure.</p>
            </div>

            <button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="load">
                Sync Portfolio
            </button>
        </header>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Total Requests</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ rows.length }}</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-amber-700">Pending Approvals</p>
                <p class="mt-2 text-2xl font-black text-amber-800">{{ pendingCount }}</p>
            </article>
            <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-indigo-700">Approved Principal</p>
                <p class="mt-2 text-2xl font-black text-indigo-900">{{ number(totalApprovedAmount) }}</p>
            </article>
            <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose-700">Outstanding Balance</p>
                <p class="mt-2 text-2xl font-black text-rose-800">{{ number(totalOutstandingAmount) }}</p>
            </article>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Create Loan Request</h2>
            <form class="mt-4 grid gap-3 md:grid-cols-4" @submit.prevent="createLoan">
                <select v-model="createForm.employee_id" required class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Select employee</option>
                    <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                        {{ employee.employee_code }} - {{ employee.user?.name }}
                    </option>
                </select>
                <input
                    v-model="createForm.amount_requested"
                    required
                    type="number"
                    min="1"
                    step="0.01"
                    class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                    placeholder="Requested amount"
                >
                <input
                    v-model="createForm.preferred_repayment_months"
                    type="number"
                    min="1"
                    max="60"
                    class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                    placeholder="Preferred months"
                >
                <button class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Create Loan</button>
                <textarea
                    v-model="createForm.reason"
                    required
                    rows="2"
                    class="md:col-span-4 rounded-xl border border-slate-300 px-3 py-2.5 text-sm"
                    placeholder="Reason and context"
                ></textarea>
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
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700" @click="load">
                    Refresh List
                </button>
            </div>
        </article>

        <article class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="p-3 text-left">Reference</th>
                        <th class="p-3 text-left">Employee</th>
                        <th class="p-3 text-left">Requested</th>
                        <th class="p-3 text-left">Approved</th>
                        <th class="p-3 text-left">EMI</th>
                        <th class="p-3 text-left">Remaining</th>
                        <th class="p-3 text-left">Progress</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="loan in rows" :key="loan.id" class="border-t border-slate-100 hover:bg-slate-50/70">
                        <td class="p-3 font-semibold text-slate-900">{{ loan.loan_reference }}</td>
                        <td class="p-3">{{ loan.employee?.user?.name || '-' }}</td>
                        <td class="p-3">{{ number(loan.amount_requested) }}</td>
                        <td class="p-3">{{ number(loan.amount_approved) }}</td>
                        <td class="p-3">{{ number(loan.emi_amount) }}</td>
                        <td class="p-3">{{ number(loan.amount_remaining) }}</td>
                        <td class="p-3">{{ Number(loan.repayment_progress_percent ?? 0).toFixed(1) }}%</td>
                        <td class="p-3">
                            <span
                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize"
                                :class="statusClass(loan.status)"
                            >
                                {{ loan.status }}
                            </span>
                        </td>
                        <td class="space-x-2 p-3 text-right">
                            <button class="text-xs font-semibold text-blue-700 hover:text-blue-900" @click="openLoan(loan.id)">View</button>
                            <button
                                v-if="loan.status === 'pending'"
                                class="text-xs font-semibold text-emerald-700 hover:text-emerald-900"
                                @click="openApproveModal(loan)"
                            >
                                Approve
                            </button>
                            <button
                                v-if="loan.status === 'pending'"
                                class="text-xs font-semibold text-rose-700 hover:text-rose-900"
                                @click="openRejectModal(loan.id)"
                            >
                                Reject
                            </button>
                            <button
                                v-if="['pending', 'rejected'].includes(loan.status)"
                                class="text-xs font-semibold text-amber-700 hover:text-amber-900"
                                @click="openEditModal(loan)"
                            >
                                Edit
                            </button>
                            <button
                                v-if="['pending', 'rejected'].includes(loan.status)"
                                class="text-xs font-semibold text-rose-700 hover:text-rose-900"
                                @click="openDeleteLoanModal(loan.id)"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td colspan="9" class="p-4 text-center text-slate-500">No loan requests found for current filter.</td>
                    </tr>
                </tbody>
            </table>
        </article>

        <AppModal v-model="showEditModal" title="Edit Loan Request" size="md">
            <form class="grid gap-3" @submit.prevent="submitEditLoan">
                <input
                    v-model="editForm.amount_requested"
                    required
                    type="number"
                    min="1"
                    step="0.01"
                    class="rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Requested amount"
                >
                <textarea
                    v-model="editForm.reason"
                    required
                    rows="3"
                    class="rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Reason"
                ></textarea>
                <select v-model="editForm.status" required class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="pending">Pending</option>
                    <option value="rejected">Rejected</option>
                </select>

                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="showEditModal = false">Cancel</button>
                    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Save Changes</button>
                </div>
            </form>
        </AppModal>

        <AppModal v-model="showApproveModal" title="Approve Loan" size="md">
            <form class="grid gap-3" @submit.prevent="submitApproveLoan">
                <input
                    v-model="approveForm.amount_approved"
                    required
                    type="number"
                    min="1"
                    step="0.01"
                    class="rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Approved amount"
                >
                <input
                    v-model="approveForm.repayment_months"
                    required
                    type="number"
                    min="1"
                    max="60"
                    class="rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Repayment months"
                >
                <input
                    v-model="approveForm.start_month"
                    required
                    type="date"
                    class="rounded-lg border border-slate-300 px-3 py-2"
                >
                <textarea
                    v-model="approveForm.admin_note"
                    rows="2"
                    class="rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Admin note (optional)"
                ></textarea>

                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="showApproveModal = false">Cancel</button>
                    <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Approve Loan</button>
                </div>
            </form>
        </AppModal>

        <AppModal v-model="showRejectModal" title="Reject Loan" size="sm">
            <form class="grid gap-3" @submit.prevent="submitRejectLoan">
                <textarea
                    v-model="rejectForm.admin_note"
                    required
                    rows="3"
                    class="rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Rejection reason"
                ></textarea>

                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="showRejectModal = false">Cancel</button>
                    <button class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white">Reject Loan</button>
                </div>
            </form>
        </AppModal>

        <ConfirmModal
            v-model="showDeleteModal"
            title="Delete Loan Request"
            message="Are you sure you want to delete this loan request?"
            confirm-text="Delete Loan"
            tone="danger"
            @confirm="confirmDeleteLoan"
        />

        <article v-if="selectedLoan" class="rounded-2xl border border-slate-200 bg-white p-5 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="font-bold">Loan Schedule — {{ selectedLoan.loan_reference }}</h3>
                <p class="text-sm text-slate-600">Months left: <strong>{{ selectedLoan.months_left ?? 0 }}</strong></p>
            </div>

            <div class="grid sm:grid-cols-3 gap-3 text-sm">
                <div class="rounded-lg bg-slate-100 p-3">Approved: <strong>{{ number(selectedLoan.amount_approved) }}</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">Repaid: <strong>{{ number(selectedLoan.total_repaid) }}</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">Remaining: <strong>{{ number(selectedLoan.amount_remaining) }}</strong></div>
            </div>

            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Month</th>
                        <th class="text-left p-3">Amount Paid</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="entry in repaymentSchedule" :key="entry.id" class="border-t border-slate-100">
                        <td class="p-3">{{ entry.month }}</td>
                        <td class="p-3">{{ number(entry.amount_paid) }}</td>
                    </tr>
                    <tr v-if="repaymentSchedule.length === 0">
                        <td class="p-3 text-slate-500" colspan="2">No repayments recorded yet.</td>
                    </tr>
                </tbody>
            </table>
        </article>
    </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';
import AppModal from '../../../components/ui/AppModal.vue';
import ConfirmModal from '../../../components/ui/ConfirmModal.vue';
import { useAuthStore } from '../../../stores/auth.store';
import { useToastStore } from '../../../stores/toast.store';
import { EmployeeService } from '../../../services/employee.service';
import { LoanService } from '../../../services/loan.service';
import { getApiErrorMessage } from '../../../utils/api-error';

const auth = useAuthStore();
const toast = useToastStore();

const status = ref('pending');
const employees = ref([]);
const rows = ref([]);
const selectedLoan = ref(null);
const repaymentSchedule = ref([]);
const showEditModal = ref(false);
const showApproveModal = ref(false);
const showRejectModal = ref(false);
const showDeleteModal = ref(false);
const actionLoanId = ref(null);
const deleteLoanId = ref(null);
const createForm = reactive({
    employee_id: '',
    amount_requested: '',
    preferred_repayment_months: '',
    reason: '',
});
const editForm = reactive({
    amount_requested: '',
    reason: '',
    status: 'pending',
});
const approveForm = reactive({
    amount_approved: '',
    repayment_months: '12',
    start_month: new Date().toISOString().slice(0, 10),
    admin_note: '',
});
const rejectForm = reactive({
    admin_note: '',
});

const pendingCount = computed(() => rows.value.filter((loan) => String(loan.status).toLowerCase() === 'pending').length);
const totalApprovedAmount = computed(() => rows.value.reduce((sum, loan) => sum + Number(loan.amount_approved ?? 0), 0));
const totalOutstandingAmount = computed(() => rows.value.reduce((sum, loan) => sum + Number(loan.amount_remaining ?? 0), 0));

let adminChannel = null;

onMounted(async () => {
    await loadEmployees();
    await load();
    subscribeRealTime();
});

onUnmounted(() => {
    if (adminChannel) {
        adminChannel.stopListening('.loan.applied');
        adminChannel.stopListening('loan.applied');
    }
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
        const response = await LoanService.adminList({ status: status.value || undefined });
        rows.value = response.data.data ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load loans.'));
    }
}

async function openLoan(id) {
    try {
        const response = await LoanService.adminGet(id);
        selectedLoan.value = response.data.loan;
        repaymentSchedule.value = response.data.repayment_schedule ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load loan details.'));
    }
}

async function createLoan() {
    try {
        await LoanService.adminCreate({
            employee_id: Number(createForm.employee_id),
            amount_requested: Number(createForm.amount_requested),
            reason: createForm.reason,
            preferred_repayment_months: createForm.preferred_repayment_months
                ? Number(createForm.preferred_repayment_months)
                : undefined,
        });

        createForm.employee_id = '';
        createForm.amount_requested = '';
        createForm.preferred_repayment_months = '';
        createForm.reason = '';

        toast.success('Loan created successfully.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to create loan.'));
    }
}

function openEditModal(loan) {
    actionLoanId.value = loan.id;
    editForm.amount_requested = String(loan.amount_requested ?? '');
    editForm.reason = loan.reason ?? '';
    editForm.status = loan.status === 'pending' ? 'pending' : 'rejected';
    showEditModal.value = true;
}

async function submitEditLoan() {
    if (!actionLoanId.value) {
        return;
    }

    try {
        await LoanService.adminUpdate(actionLoanId.value, {
            amount_requested: Number(editForm.amount_requested),
            reason: editForm.reason,
            status: editForm.status,
        });

        showEditModal.value = false;
        toast.success('Loan updated successfully.');
        await load();

        if (selectedLoan.value?.id === actionLoanId.value) {
            await openLoan(actionLoanId.value);
        }
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to update loan.'));
    }
}

function openApproveModal(loan) {
    actionLoanId.value = loan.id;
    approveForm.amount_approved = String(loan.amount_requested ?? '');
    approveForm.repayment_months = '12';
    approveForm.start_month = new Date().toISOString().slice(0, 10);
    approveForm.admin_note = '';
    showApproveModal.value = true;
}

async function submitApproveLoan() {
    if (!actionLoanId.value) {
        return;
    }

    try {
        await LoanService.approve(actionLoanId.value, {
            amount_approved: Number(approveForm.amount_approved),
            repayment_months: Number(approveForm.repayment_months),
            start_month: approveForm.start_month,
            admin_note: approveForm.admin_note || undefined,
        });

        showApproveModal.value = false;
        toast.success('Loan approved successfully.');
        await load();
        await openLoan(actionLoanId.value);
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to approve loan.'));
    }
}

function openRejectModal(id) {
    actionLoanId.value = id;
    rejectForm.admin_note = '';
    showRejectModal.value = true;
}

async function submitRejectLoan() {
    if (!actionLoanId.value || !rejectForm.admin_note.trim()) {
        return;
    }

    try {
        await LoanService.reject(actionLoanId.value, { admin_note: rejectForm.admin_note });
        showRejectModal.value = false;
        toast.success('Loan rejected successfully.');
        await load();

        if (selectedLoan.value?.id === actionLoanId.value) {
            selectedLoan.value = null;
            repaymentSchedule.value = [];
        }
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to reject loan.'));
    }
}

function openDeleteLoanModal(id) {
    deleteLoanId.value = id;
    showDeleteModal.value = true;
}

async function confirmDeleteLoan() {
    if (!deleteLoanId.value) {
        return;
    }

    const id = deleteLoanId.value;

    try {
        await LoanService.adminDelete(id);
        showDeleteModal.value = false;
        deleteLoanId.value = null;
        toast.success('Loan deleted successfully.');
        await load();

        if (selectedLoan.value?.id === id) {
            selectedLoan.value = null;
            repaymentSchedule.value = [];
        }
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to delete loan.'));
    }
}

function subscribeRealTime() {
    if (!window.Echo || !auth.token) {
        return;
    }

    window.Echo.connector.options.auth = {
        headers: {
            Authorization: `Bearer ${auth.token}`,
        },
    };

    adminChannel = window.Echo.private('admin-broadcast');

    adminChannel.listen('.loan.applied', async () => {
        toast.info('New loan application received in real time.');
        await load();
    });

    adminChannel.listen('loan.applied', async () => {
        toast.info('New loan application received in real time.');
        await load();
    });
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}

function statusClass(status) {
    const value = String(status ?? '').toLowerCase();

    if (value === 'approved' || value === 'active') {
        return 'bg-indigo-100 text-indigo-700';
    }

    if (value === 'completed') {
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
