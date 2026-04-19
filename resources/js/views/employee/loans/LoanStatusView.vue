<template>
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <p class="text-sm text-slate-600">Track all your loan requests and repayment progress.</p>
            <RouterLink to="/portal/loans/apply" class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Apply New Loan</RouterLink>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Reference</th>
                        <th class="text-left p-3">Requested</th>
                        <th class="text-left p-3">Approved</th>
                        <th class="text-left p-3">Remaining</th>
                        <th class="text-left p-3">Progress</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100">
                        <td class="p-3">{{ row.loan_reference }}</td>
                        <td class="p-3">{{ number(row.amount_requested) }}</td>
                        <td class="p-3">{{ number(row.amount_approved) }}</td>
                        <td class="p-3">{{ number(row.amount_remaining) }}</td>
                        <td class="p-3">{{ Number(row.repayment_progress_percent ?? 0).toFixed(1) }}%</td>
                        <td class="p-3">{{ row.status }}</td>
                        <td class="p-3 text-right">
                            <button class="text-xs font-semibold text-blue-700" @click="openLoan(row.id)">View</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </article>

        <article v-if="selectedLoan" class="rounded-2xl border border-slate-200 bg-white p-5 space-y-3">
            <h3 class="font-bold">Repayment Schedule — {{ selectedLoan.loan_reference }}</h3>

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
import { RouterLink } from 'vue-router';
import { LoanService } from '../../../services/loan.service';
import { useAuthStore } from '../../../stores/auth.store';
import { useToastStore } from '../../../stores/toast.store';
import { getApiErrorMessage } from '../../../utils/api-error';

const auth = useAuthStore();
const toast = useToastStore();
const rows = ref([]);
const selectedLoan = ref(null);
const repaymentSchedule = ref([]);

let employeeChannel = null;

onMounted(load);
onMounted(async () => {
    if (!auth.user?.employee && auth.token) {
        try {
            await auth.fetchMe();
        } catch {
            return;
        }
    }

    subscribeRealTime();
});

onUnmounted(() => {
    if (employeeChannel) {
        employeeChannel.stopListening('.loan.approved');
        employeeChannel.stopListening('loan.approved');
        employeeChannel.stopListening('.loan.rejected');
        employeeChannel.stopListening('loan.rejected');
    }
});

async function load() {
    try {
        const response = await LoanService.myList();
        rows.value = response.data;
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load loan records.'));
    }
}

async function openLoan(id) {
    try {
        const response = await LoanService.myGet(id);
        selectedLoan.value = response.data.loan;
        repaymentSchedule.value = response.data.repayment_schedule ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load repayment schedule.'));
    }
}

function subscribeRealTime() {
    const employeeId = auth.user?.employee?.id;
    if (!window.Echo || !auth.token || !employeeId) {
        return;
    }

    window.Echo.connector.options.auth = {
        headers: {
            Authorization: `Bearer ${auth.token}`,
        },
    };

    employeeChannel = window.Echo.private(`employee.${employeeId}`);

    employeeChannel.listen('.loan.approved', async () => {
        toast.success('Your loan has been approved.');
        await load();
    });
    employeeChannel.listen('loan.approved', async () => {
        toast.success('Your loan has been approved.');
        await load();
    });

    employeeChannel.listen('.loan.rejected', async () => {
        toast.warning('Your loan request was rejected. Check details in loan status.');
        await load();
    });
    employeeChannel.listen('loan.rejected', async () => {
        toast.warning('Your loan request was rejected. Check details in loan status.');
        await load();
    });
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}
</script>
