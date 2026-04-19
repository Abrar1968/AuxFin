<template>
    <form class="space-y-3" @submit.prevent="submit">
        <AppSelect v-model="form.type" label="Type" :options="typeOptions" />
        <AppInput v-model="form.subject" label="Subject" placeholder="Enter subject" />

        <label class="block space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-600">Message</span>
            <textarea
                v-model="form.body"
                rows="4"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                placeholder="Write your query"
            ></textarea>
        </label>

        <div class="grid gap-3 md:grid-cols-2">
            <AppInput v-model="form.reference_date" type="date" label="Reference Date (optional)" />
            <AppInput v-model="form.reference_month" type="date" label="Reference Month (optional)" />
        </div>

        <div class="pt-1">
            <AppButton type="submit" variant="primary" :loading="loading">Submit Query</AppButton>
        </div>
    </form>
</template>

<script setup>
import { reactive } from 'vue';
import AppButton from '../../ui/AppButton.vue';
import AppInput from '../../ui/AppInput.vue';
import AppSelect from '../../ui/AppSelect.vue';

const emit = defineEmits(['submit']);

const props = defineProps({
    loading: { type: Boolean, default: false },
});

const form = reactive({
    type: 'general_hr',
    subject: '',
    body: '',
    reference_date: '',
    reference_month: '',
});

const typeOptions = [
    { label: 'General HR', value: 'general_hr' },
    { label: 'Late Appeal', value: 'late_appeal' },
    { label: 'Deduction Dispute', value: 'deduction_dispute' },
    { label: 'Leave Clarification', value: 'leave_clarification' },
    { label: 'Salary Query', value: 'salary_query' },
    { label: 'Loan Query', value: 'loan_query' },
];

function submit() {
    emit('submit', {
        ...form,
    });
}
</script>
