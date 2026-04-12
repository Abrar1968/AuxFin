<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Salary Detail</p>
                <h1 class="text-2xl font-black text-slate-900">{{ payslip?.meta?.month || 'Payslip Detail' }}</h1>
                <p class="mt-1 text-sm text-slate-600">Full month-level payslip breakdown with loan and deduction context.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    @click="router.push({ name: 'employee.salary' })"
                >
                    Back to Salary
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
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Payroll Status</p>
                        <div class="mt-2 flex justify-end">
                            <StatusBadge :status="payslip.meta?.status" />
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Payment Date: {{ payslip.meta?.payment_date || 'Pending' }}</p>
                    </div>
                </div>
            </AppCard>

            <PayslipCard
                :subtitle="`Month over month delta: ${formatPercent(payslip.month_over_month_delta_percent, { signed: true })}`"
                :earnings="earningsRows"
                :deductions="deductionRows"
                :net-payable="payslip.net_payable"
            />

            <div class="grid gap-5 xl:grid-cols-3">
                <AppCard elevated class="xl:col-span-2">
                    <h3 class="text-base font-bold text-slate-900">Repayment Timeline</h3>
                    <p class="mt-1 text-sm text-slate-600">Loan repayment deductions already applied by month.</p>

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
                                    <td colspan="2" class="p-4 text-center text-slate-500">No repayment deductions yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </AppCard>

                <LoanStatusCard
                    title="Loan Status"
                    :status="payslip.loan?.status || 'pending'"
                    :approved-amount="estimatedLoanTotal"
                    :outstanding="payslip.loan?.amount_remaining || 0"
                />
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

const requestedMonth = computed(() => String(route.params.month ?? ''));
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

onMounted(async () => {
    await load();
});

watch(
    () => requestedMonth.value,
    async () => {
        await load();
    }
);

async function load() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await PayrollService.getPayslip(requestedMonth.value);
        payslip.value = response.data;
    } catch (error) {
        errorMessage.value = getApiErrorMessage(error, 'Unable to load payslip details.');
        toast.error(errorMessage.value);
    } finally {
        loading.value = false;
    }
}

async function download() {
    if (!requestedMonth.value) {
        return;
    }

    try {
        const response = await PayrollService.getPayslipPdfPayload(requestedMonth.value);
        downloadPayslipPdf(response.data.payslip, response.data.filename);
        toast.success('Payslip PDF download started.');
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to generate payslip PDF.'));
    }
}

function mapRows(source) {
    return Object.entries(source ?? {}).map(([key, value]) => ({
        label: humanizeKey(key),
        value,
    }));
}
</script>
