<template>
    <section class="space-y-4">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs font-semibold text-slate-600">Status</label>
                <select v-model="status" class="block mt-1 rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold" @click="load">Refresh</button>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Reference</th>
                        <th class="text-left p-3">Employee</th>
                        <th class="text-left p-3">Requested</th>
                        <th class="text-left p-3">Approved</th>
                        <th class="text-left p-3">EMI</th>
                        <th class="text-left p-3">Remaining</th>
                        <th class="text-left p-3">Progress</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="loan in rows" :key="loan.id" class="border-t border-slate-100">
                        <td class="p-3">{{ loan.loan_reference }}</td>
                        <td class="p-3">{{ loan.employee?.user?.name }}</td>
                        <td class="p-3">{{ number(loan.amount_requested) }}</td>
                        <td class="p-3">{{ number(loan.amount_approved) }}</td>
                        <td class="p-3">{{ number(loan.emi_amount) }}</td>
                        <td class="p-3">{{ number(loan.amount_remaining) }}</td>
                        <td class="p-3">{{ Number(loan.repayment_progress_percent ?? 0).toFixed(1) }}%</td>
                        <td class="p-3 capitalize">{{ loan.status }}</td>
                        <td class="p-3 text-right space-x-2">
                            <button class="text-xs font-semibold text-blue-700" @click="openLoan(loan.id)">View</button>
                            <button
                                v-if="loan.status === 'pending'"
                                class="text-xs font-semibold text-emerald-700"
                                @click="approve(loan)"
                            >
                                Approve
                            </button>
                            <button
                                v-if="loan.status === 'pending'"
                                class="text-xs font-semibold text-rose-700"
                                @click="reject(loan.id)"
                            >
                                Reject
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </article>

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
import { onMounted, onUnmounted, ref } from 'vue';
import { useAuthStore } from '../../../stores/auth.store';
import { useToastStore } from '../../../stores/toast.store';
import { LoanService } from '../../../services/loan.service';
import { getApiErrorMessage } from '../../../utils/api-error';

const auth = useAuthStore();
const toast = useToastStore();

const status = ref('pending');
const rows = ref([]);
const selectedLoan = ref(null);
const repaymentSchedule = ref([]);

let adminChannel = null;

onMounted(async () => {
    await load();
    subscribeRealTime();
});

onUnmounted(() => {
    if (adminChannel) {
        adminChannel.stopListening('.loan.applied');
        adminChannel.stopListening('loan.applied');
    }
});

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

async function approve(loan) {
    const amount = window.prompt('Approved amount', String(loan.amount_requested ?? ''));
    if (!amount) {
        return;
    }

    const months = window.prompt('Repayment months', '12');
    if (!months) {
        return;
    }

    const startMonth = window.prompt('Start month (YYYY-MM-01)', new Date().toISOString().slice(0, 10));
    if (!startMonth) {
        return;
    }

    try {
        await LoanService.approve(loan.id, {
            amount_approved: Number(amount),
            repayment_months: Number(months),
            start_month: startMonth,
        });

        toast.success('Loan approved successfully.');
        await load();
        await openLoan(loan.id);
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to approve loan.'));
    }
}

async function reject(id) {
    const adminNote = window.prompt('Rejection reason');
    if (!adminNote) {
        return;
    }

    try {
        await LoanService.reject(id, { admin_note: adminNote });
        toast.success('Loan rejected successfully.');
        await load();

        if (selectedLoan.value?.id === id) {
            selectedLoan.value = null;
            repaymentSchedule.value = [];
        }
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to reject loan.'));
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
</script>
