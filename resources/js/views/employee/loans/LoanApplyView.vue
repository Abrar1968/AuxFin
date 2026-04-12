<template>
    <section class="space-y-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 space-y-4">
            <h3 class="font-bold">Loan Application</h3>
            <p class="text-sm text-slate-600">Max eligible amount for you: <strong>{{ number(maxAmount) }}</strong></p>

            <form class="grid md:grid-cols-3 gap-3" @submit.prevent="submit">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Amount Requested</label>
                    <input v-model.number="form.amount_requested" type="number" min="1" required class="block mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Preferred Repayment Months</label>
                    <input v-model.number="form.preferred_repayment_months" type="number" min="1" :max="policy.max_repayment_months || 12" class="block mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div class="rounded-lg bg-slate-100 p-3 text-sm">
                    Estimated EMI (preview):
                    <div class="text-lg font-bold mt-1">{{ number(estimatedEmi) }}</div>
                </div>
                <textarea
                    v-model="form.reason"
                    required
                    rows="3"
                    class="md:col-span-3 rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Reason for loan request"
                ></textarea>
                <button class="md:col-span-3 rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Submit Application</button>
            </form>
        </article>
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { LoanService } from '../../../services/loan.service';
import { useToastStore } from '../../../stores/toast.store';
import { getApiErrorMessage } from '../../../utils/api-error';

const router = useRouter();
const toast = useToastStore();

const maxAmount = ref(0);
const policy = ref({
    max_loan_multiplier: 3,
    max_repayment_months: 12,
    cooling_period_months: 3,
    concurrent_loans: 1,
});

const form = reactive({
    amount_requested: 0,
    preferred_repayment_months: 12,
    reason: '',
});

const estimatedEmi = computed(() => {
    const amount = Number(form.amount_requested || 0);
    const months = Number(form.preferred_repayment_months || 0);

    if (amount <= 0 || months <= 0) {
        return 0;
    }

    return amount / months;
});

onMounted(loadPolicy);

async function loadPolicy() {
    try {
        const response = await LoanService.myPolicy();
        policy.value = response.data.policy ?? policy.value;
        maxAmount.value = Number(response.data.max_amount_for_employee ?? 0);

        if (!form.preferred_repayment_months) {
            form.preferred_repayment_months = policy.value.max_repayment_months ?? 12;
        }
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load loan policy.'));
    }
}

async function submit() {
    try {
        await LoanService.apply({
            amount_requested: Number(form.amount_requested),
            preferred_repayment_months: Number(form.preferred_repayment_months || 0) || undefined,
            reason: form.reason,
        });

        toast.success('Loan application submitted successfully.');
        router.push('/portal/loans');
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to submit loan application.'));
    }
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}
</script>
