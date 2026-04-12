<template>
    <section class="space-y-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="font-bold">Late Policy</h3>
            <form class="mt-3 grid md:grid-cols-2 gap-3" @submit.prevent="savePolicy">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Late Days Per Unit</label>
                    <input v-model.number="policy.late_days_per_unit" type="number" min="1" class="block mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Deduction Unit</label>
                    <select v-model="policy.deduction_unit_type" class="block mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="full_day">Full Day</option>
                        <option value="half_day">Half Day</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Grace Period (Minutes)</label>
                    <input v-model.number="policy.grace_period_minutes" type="number" min="0" class="block mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Office Start Time</label>
                    <input v-model="policy.office_start_time" type="time" class="block mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <label class="md:col-span-2 inline-flex items-center gap-2 text-sm text-slate-700">
                    <input v-model="policy.carry_forward" type="checkbox">
                    Carry forward late balance to next month
                </label>
                <button class="md:col-span-2 rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Save Policy</button>
            </form>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="font-bold">Loan Policy</h3>
            <form class="mt-3 grid md:grid-cols-2 gap-3" @submit.prevent="saveLoanPolicy">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Max Loan Multiplier (x basic salary)</label>
                    <input v-model.number="loanPolicy.max_loan_multiplier" type="number" min="1" class="block mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Max Repayment Months</label>
                    <input v-model.number="loanPolicy.max_repayment_months" type="number" min="1" class="block mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Cooling Period (Months)</label>
                    <input v-model.number="loanPolicy.cooling_period_months" type="number" min="0" class="block mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Concurrent Loans Allowed</label>
                    <input v-model.number="loanPolicy.concurrent_loans" type="number" min="1" max="3" class="block mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <button class="md:col-span-2 rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Save Loan Policy</button>
            </form>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="font-bold">Public Holidays</h3>
            <form class="mt-3 grid md:grid-cols-3 gap-3" @submit.prevent="createHoliday">
                <input v-model="holidayForm.name" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Holiday name">
                <input v-model="holidayForm.date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <button class="rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-semibold">Add Holiday</button>
            </form>

            <table class="w-full text-sm mt-4">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Date</th>
                        <th class="text-left p-3">Name</th>
                        <th class="text-right p-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in holidays" :key="row.id" class="border-t border-slate-100">
                        <td class="p-3">{{ row.date }}</td>
                        <td class="p-3">{{ row.name }}</td>
                        <td class="p-3 text-right">
                            <button class="text-xs font-semibold text-rose-700" @click="removeHoliday(row.id)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </article>
    </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { SettingsService } from '../../../services/settings.service';
import { getApiErrorMessage } from '../../../utils/api-error';
import { useToastStore } from '../../../stores/toast.store';

const policy = reactive({
    late_days_per_unit: 2,
    deduction_unit_type: 'full_day',
    grace_period_minutes: 15,
    office_start_time: '09:00',
    carry_forward: false,
});

const loanPolicy = reactive({
    max_loan_multiplier: 3,
    max_repayment_months: 12,
    cooling_period_months: 3,
    concurrent_loans: 1,
});

const holidayForm = reactive({
    name: '',
    date: '',
});

const holidays = ref([]);
const toast = useToastStore();

onMounted(async () => {
    await loadPolicy();
    await loadLoanPolicy();
    await loadHolidays();
});

async function loadPolicy() {
    try {
        const response = await SettingsService.getLatePolicy();
        Object.assign(policy, response.data.late_policy ?? {});
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load late policy.'));
    }
}

async function savePolicy() {
    try {
        await SettingsService.updateLatePolicy({
            late_days_per_unit: Number(policy.late_days_per_unit ?? 2),
            deduction_unit_type: policy.deduction_unit_type,
            grace_period_minutes: Number(policy.grace_period_minutes ?? 15),
            office_start_time: policy.office_start_time,
            carry_forward: Boolean(policy.carry_forward),
        });

        toast.success('Late policy saved.');
        await loadPolicy();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to save late policy.'));
    }
}

async function loadLoanPolicy() {
    try {
        const response = await SettingsService.getLoanPolicy();
        Object.assign(loanPolicy, response.data.loan_policy ?? {});
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load loan policy.'));
    }
}

async function saveLoanPolicy() {
    try {
        await SettingsService.updateLoanPolicy({
            max_loan_multiplier: Number(loanPolicy.max_loan_multiplier ?? 3),
            max_repayment_months: Number(loanPolicy.max_repayment_months ?? 12),
            cooling_period_months: Number(loanPolicy.cooling_period_months ?? 3),
            concurrent_loans: Number(loanPolicy.concurrent_loans ?? 1),
        });

        toast.success('Loan policy saved.');
        await loadLoanPolicy();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to save loan policy.'));
    }
}

async function loadHolidays() {
    try {
        const response = await SettingsService.holidays();
        holidays.value = response.data.data ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load holidays.'));
    }
}

async function createHoliday() {
    try {
        await SettingsService.createHoliday({
            name: holidayForm.name,
            date: holidayForm.date,
        });

        holidayForm.name = '';
        holidayForm.date = '';
        toast.success('Holiday added successfully.');
        await loadHolidays();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to add holiday.'));
    }
}

async function removeHoliday(id) {
    try {
        await SettingsService.deleteHoliday(id);
        toast.success('Holiday deleted successfully.');
        await loadHolidays();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to delete holiday.'));
    }
}
</script>
