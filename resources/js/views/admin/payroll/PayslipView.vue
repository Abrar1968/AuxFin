<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Payroll Record</p>
                <h1 class="text-2xl font-black text-slate-900">Admin Payslip Breakdown</h1>
                <p class="mt-1 text-sm text-slate-600">Detailed earnings, deductions, and loan impact for this salary cycle.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    @click="router.push({ name: 'admin.payroll' })"
                >
                    Back to Payroll
                </button>

                <AppButton variant="secondary" :loading="loading" @click="load">Refresh</AppButton>
                <AppButton :disabled="!payslip" @click="download">Download PDF</AppButton>
            </div>
        </header>

        <AppAlert v-if="errorMessage" type="error" :message="errorMessage" />

        <div v-if="loading" class="flex min-h-60 items-center justify-center rounded-2xl border border-slate-200 bg-white">
            <LoadingSpinner label="Loading payslip..." />
        </div>

        <template v-else-if="payslip">
            <AppCard elevated>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Employee</p>
                        <h2 class="mt-1 text-xl font-black text-slate-900">{{ payslip.employee?.name || '-' }}</h2>
                        <p class="text-sm text-slate-600">
                            {{ payslip.employee?.employee_code || '-' }} | {{ payslip.employee?.designation || '-' }}
                        </p>
                        <p class="text-sm text-slate-600">{{ payslip.employee?.department || '-' }}</p>
                    </div>

                    <div class="text-right">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Payroll Month</p>
                        <p class="mt-1 text-base font-bold text-slate-900">{{ payslip.meta?.month || '-' }}</p>
                        <div class="mt-2 flex justify-end">
                            <StatusBadge :status="payslip.meta?.status" />
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Payment Date: {{ payslip.meta?.payment_date || 'Pending' }}</p>
                    </div>
                </div>
            </AppCard>

            <PayslipCard
                :subtitle="`Salary Record #${route.params.id}`"
                :earnings="earningsRows"
                :deductions="deductionRows"
                :net-payable="payslip.net_payable"
            />

            <div class="grid gap-5 xl:grid-cols-3">
                <AppCard elevated class="xl:col-span-2">
                    <h3 class="text-base font-bold text-slate-900">Repayment Schedule</h3>
                    <p class="mt-1 text-sm text-slate-600">Monthly deduction timeline linked to this payslip.</p>

                    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-100 text-slate-600">
                                <tr>
                                    <th class="p-3 text-left">Month</th>
                                    <th class="p-3 text-right">Amount Paid</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in payslip.loan?.repayment_schedule ?? []"
                                    :key="item.month"
                                    class="border-t border-slate-100"
                                >
                                    <td class="p-3">{{ item.month }}</td>
                                    <td class="p-3 text-right">{{ formatCurrency(item.amount_paid) }}</td>
                                </tr>
                                <tr v-if="(payslip.loan?.repayment_schedule ?? []).length === 0">
                                    <td colspan="2" class="p-4 text-center text-slate-500">No repayment deductions recorded.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </AppCard>

                <div class="space-y-5">
                    <LoanStatusCard
                        title="Linked Loan"
                        :status="payslip.loan?.status || 'pending'"
                        :approved-amount="estimatedLoanTotal"
                        :outstanding="payslip.loan?.amount_remaining || 0"
                    />

                    <AppCard elevated>
                        <h3 class="text-sm font-bold text-slate-900">Month-over-Month Delta</h3>
                        <p class="mt-2 text-3xl font-black" :class="deltaClass">
                            {{ formatPercent(payslip.month_over_month_delta_percent, { signed: true }) }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">Compared with previous processed payroll month.</p>
                    </AppCard>
                </div>
            </div>
        </template>
    </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import LoanStatusCard from '../../../components/domain/loans/LoanStatusCard.vue';
import PayslipCard from '../../../components/domain/payroll/PayslipCard.vue';
import AppAlert from '../../../components/ui/AppAlert.vue';
import AppButton from '../../../components/ui/AppButton.vue';
import AppCard from '../../../components/ui/AppCard.vue';
import LoadingSpinner from '../../../components/ui/LoadingSpinner.vue';
import StatusBadge from '../../../components/ui/StatusBadge.vue';
import { PayrollService } from '../../../services/payroll.service';
import { useToastStore } from '../../../stores/toast.store';
import { getApiErrorMessage } from '../../../utils/api-error';
import { downloadPayslipPdf } from '../../../utils/payslip-pdf';
import { formatCurrency, formatPercent, humanizeKey } from '../../../utils/formatters';

const route = useRoute();
const router = useRouter();
const toast = useToastStore();

const loading = ref(false);
const errorMessage = ref('');
const payslip = ref(null);

const earningsRows = computed(() => mapRows(payslip.value?.earnings));
const deductionRows = computed(() => mapRows(payslip.value?.deductions));

const estimatedLoanTotal = computed(() => {
    const loan = payslip.value?.loan;
    if (!loan) {
        return 0;
    }

    const remaining = Number(loan.amount_remaining ?? 0);
    const emi = Number(loan.emi_amount ?? 0);
    const monthsLeft = Number(loan.months_left ?? 0);
    return remaining + emi * monthsLeft;
});

const deltaClass = computed(() => {
    const delta = Number(payslip.value?.month_over_month_delta_percent ?? 0);
    return delta >= 0 ? 'text-emerald-600' : 'text-rose-600';
});

onMounted(async () => {
    await load();
});

watch(
    () => route.params.id,
    async () => {
        await load();
    }
);

async function load() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await PayrollService.adminPayslip(route.params.id);
        payslip.value = response.data;
    } catch (error) {
        errorMessage.value = getApiErrorMessage(error, 'Unable to load payslip details.');
        toast.error(errorMessage.value);
    } finally {
        loading.value = false;
    }
}

function download() {
    if (!payslip.value) {
        return;
    }

    const employeeCode = payslip.value.employee?.employee_code || 'employee';
    const month = String(payslip.value.meta?.month || 'payslip')
        .toLowerCase()
        .replace(/\s+/g, '-');

    downloadPayslipPdf(payslip.value, `payslip-${employeeCode}-${month}.pdf`);
    toast.success('Payslip PDF download started.');
}

function mapRows(source) {
    return Object.entries(source ?? {}).map(([key, value]) => ({
        label: humanizeKey(key),
        value,
    }));
}
</script>
