<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Employee Profile</p>
                <h1 class="text-2xl font-black text-slate-900">{{ employee?.user?.name ?? 'Employee Details' }}</h1>
                <p class="mt-1 text-sm text-slate-600">Complete profile, salary trajectory, and recent financial records.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    @click="router.push({ name: 'admin.employees' })"
                >
                    Back
                </button>

                <AppButton variant="secondary" :loading="loading" @click="load">Refresh</AppButton>
            </div>
        </header>

        <AppAlert v-if="errorMessage" type="error" :message="errorMessage" />
        <AppAlert v-if="latestPasskey" type="warning" :message="`New temporary passkey: ${latestPasskey}`" />

        <div v-if="loading" class="flex min-h-60 items-center justify-center rounded-2xl border border-slate-200 bg-white">
            <LoadingSpinner label="Loading employee profile..." />
        </div>

        <template v-else-if="employee">
            <div class="grid gap-5 xl:grid-cols-3">
                <AppCard elevated class="xl:col-span-2">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Identity</p>
                            <h2 class="mt-1 text-xl font-black text-slate-900">{{ employee.user?.name }}</h2>
                            <p class="text-sm text-slate-600">{{ employee.designation }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <StatusBadge :status="employee.user?.is_active ? 'active' : 'inactive'" :label="employee.user?.is_active ? 'active' : 'inactive'" />
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                {{ employee.employee_code }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Work Email</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ employee.user?.email || '-' }}</p>
                        </article>

                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Department</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ employee.department?.name || '-' }}</p>
                        </article>

                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date of Joining</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ formatDate(employee.date_of_joining) }}</p>
                        </article>

                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bank</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ employee.bank_name || '-' }}</p>
                            <p class="text-xs text-slate-600">{{ maskAccountNumber(employee.bank_account_number) }}</p>
                        </article>
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <AppButton variant="secondary" :loading="busyReset" @click="resetPasskey">
                            Reset Passkey
                        </AppButton>
                        <AppButton variant="danger" :loading="busyArchive" @click="archiveEmployee">
                            Archive Employee
                        </AppButton>
                    </div>
                </AppCard>

                <AppCard elevated>
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Compensation</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li class="flex items-center justify-between gap-3">
                            <span class="text-slate-600">Basic Salary</span>
                            <strong class="text-slate-900">{{ formatCurrency(employee.basic_salary) }}</strong>
                        </li>
                        <li class="flex items-center justify-between gap-3">
                            <span class="text-slate-600">House Rent</span>
                            <strong class="text-slate-900">{{ formatCurrency(employee.house_rent) }}</strong>
                        </li>
                        <li class="flex items-center justify-between gap-3">
                            <span class="text-slate-600">Conveyance</span>
                            <strong class="text-slate-900">{{ formatCurrency(employee.conveyance) }}</strong>
                        </li>
                        <li class="flex items-center justify-between gap-3">
                            <span class="text-slate-600">Medical Allowance</span>
                            <strong class="text-slate-900">{{ formatCurrency(employee.medical_allowance) }}</strong>
                        </li>
                        <li class="flex items-center justify-between gap-3 border-t border-slate-200 pt-2">
                            <span class="text-slate-600">PF Rate</span>
                            <strong class="text-slate-900">{{ formatPercent(employee.pf_rate, { decimals: 2 }) }}</strong>
                        </li>
                        <li class="flex items-center justify-between gap-3">
                            <span class="text-slate-600">TDS Rate</span>
                            <strong class="text-slate-900">{{ formatPercent(employee.tds_rate, { decimals: 2 }) }}</strong>
                        </li>
                    </ul>
                </AppCard>
            </div>

            <div class="grid gap-5 xl:grid-cols-3">
                <AppCard elevated class="xl:col-span-2">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h2 class="text-base font-bold text-slate-900">Recent Salary History</h2>
                        <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                            {{ salaryRows.length }} Records
                        </span>
                    </div>

                    <AppTable
                        :columns="salaryColumns"
                        :rows="salaryRows"
                        empty-text="No salary month records found yet."
                    >
                        <template #cell-month="{ value }">
                            {{ formatMonth(value) }}
                        </template>

                        <template #cell-gross_earnings="{ value }">
                            {{ formatCurrency(value) }}
                        </template>

                        <template #cell-total_deductions="{ value }">
                            {{ formatCurrency(value) }}
                        </template>

                        <template #cell-net_payable="{ value }">
                            <strong>{{ formatCurrency(value) }}</strong>
                        </template>

                        <template #cell-status="{ value }">
                            <StatusBadge :status="value" />
                        </template>

                        <template #cell-actions="{ row }">
                            <button
                                type="button"
                                class="text-xs font-semibold text-indigo-700 hover:text-indigo-900"
                                @click="openPayrollPayslip(row.id)"
                            >
                                Open Payslip
                            </button>
                        </template>
                    </AppTable>
                </AppCard>

                <div class="space-y-5">
                    <AppCard elevated>
                        <h2 class="text-base font-bold text-slate-900">Loan Snapshot</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ employee.loans?.length ?? 0 }} total applications</p>

                        <ul class="mt-3 space-y-2 text-sm">
                            <li
                                v-for="loan in (employee.loans ?? []).slice(0, 5)"
                                :key="loan.id"
                                class="rounded-lg border border-slate-200 bg-slate-50 p-3"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <p class="font-semibold text-slate-900">{{ loan.loan_reference }}</p>
                                    <StatusBadge :status="loan.status" />
                                </div>
                                <p class="mt-1 text-slate-600">
                                    Remaining {{ formatCurrency(loan.amount_remaining) }}
                                </p>
                            </li>

                            <li v-if="(employee.loans ?? []).length === 0" class="text-slate-500">
                                No loans found.
                            </li>
                        </ul>
                    </AppCard>

                    <AppCard elevated>
                        <h2 class="text-base font-bold text-slate-900">Leave Snapshot</h2>
                        <ul class="mt-3 grid grid-cols-2 gap-3 text-sm">
                            <li class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Total</p>
                                <p class="mt-1 text-xl font-black text-slate-900">{{ leaveSummary.total }}</p>
                            </li>
                            <li class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-emerald-700">Approved</p>
                                <p class="mt-1 text-xl font-black text-emerald-800">{{ leaveSummary.approved }}</p>
                            </li>
                            <li class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-amber-700">Pending</p>
                                <p class="mt-1 text-xl font-black text-amber-800">{{ leaveSummary.pending }}</p>
                            </li>
                            <li class="rounded-lg border border-rose-200 bg-rose-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-rose-700">Rejected</p>
                                <p class="mt-1 text-xl font-black text-rose-800">{{ leaveSummary.rejected }}</p>
                            </li>
                        </ul>
                    </AppCard>
                </div>
            </div>
        </template>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppAlert from '../../../components/ui/AppAlert.vue';
import AppButton from '../../../components/ui/AppButton.vue';
import AppCard from '../../../components/ui/AppCard.vue';
import AppTable from '../../../components/ui/AppTable.vue';
import LoadingSpinner from '../../../components/ui/LoadingSpinner.vue';
import StatusBadge from '../../../components/ui/StatusBadge.vue';
import { EmployeeService } from '../../../services/employee.service';
import { useToastStore } from '../../../stores/toast.store';
import { getApiErrorMessage } from '../../../utils/api-error';
import {
    formatCurrency,
    formatDate,
    formatMonth,
    formatPercent,
    maskAccountNumber,
} from '../../../utils/formatters';

const route = useRoute();
const router = useRouter();
const toast = useToastStore();

const employee = ref(null);
const loading = ref(false);
const busyReset = ref(false);
const busyArchive = ref(false);
const errorMessage = ref('');
const latestPasskey = ref('');

const salaryColumns = [
    { key: 'month', label: 'Month' },
    { key: 'gross_earnings', label: 'Gross' },
    { key: 'total_deductions', label: 'Deductions' },
    { key: 'net_payable', label: 'Net' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Actions' },
];

const salaryRows = computed(() => employee.value?.salary_months ?? []);

const leaveSummary = computed(() => {
    const rows = employee.value?.leave_requests ?? [];

    return {
        total: rows.length,
        approved: rows.filter((row) => row.status === 'approved').length,
        pending: rows.filter((row) => row.status === 'pending').length,
        rejected: rows.filter((row) => row.status === 'rejected').length,
    };
});

onMounted(async () => {
    await load();
});

async function load() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await EmployeeService.show(route.params.id);
        employee.value = response.data;
    } catch (error) {
        errorMessage.value = getApiErrorMessage(error, 'Unable to load employee details.');
        toast.error(errorMessage.value);
    } finally {
        loading.value = false;
    }
}

async function resetPasskey() {
    if (!employee.value?.id) {
        return;
    }

    busyReset.value = true;

    try {
        const response = await EmployeeService.resetPasskey(employee.value.id);
        latestPasskey.value = response.data?.passkey ?? '';
        toast.success('Passkey reset successfully.');
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to reset employee passkey.'));
    } finally {
        busyReset.value = false;
    }
}

async function archiveEmployee() {
    if (!employee.value?.id) {
        return;
    }

    const confirmed = window.confirm('Archive this employee profile? The user login will be disabled.');
    if (!confirmed) {
        return;
    }

    busyArchive.value = true;

    try {
        await EmployeeService.remove(employee.value.id);
        toast.success('Employee archived successfully.');
        router.push({ name: 'admin.employees' });
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to archive employee.'));
    } finally {
        busyArchive.value = false;
    }
}

function openPayrollPayslip(id) {
    router.push({
        name: 'admin.payroll.payslip',
        params: { id },
    });
}
</script>
