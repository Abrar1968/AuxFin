<template>
    <section class="space-y-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="font-bold">Apply Leave</h3>
            <form class="mt-3 grid md:grid-cols-2 gap-3" @submit.prevent="submit">
                <select v-model="form.leave_type" required class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Select Type</option>
                    <option value="casual">Casual</option>
                    <option value="sick">Sick</option>
                    <option value="earned">Earned</option>
                    <option value="unpaid">Unpaid</option>
                </select>
                <input v-model="form.from_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <input v-model="form.to_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <textarea
                    v-model="form.reason"
                    required
                    rows="3"
                    class="md:col-span-2 rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Reason"
                ></textarea>
                <button class="md:col-span-2 rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold">
                    Submit Request
                </button>
            </form>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Type</th>
                        <th class="text-left p-3">From</th>
                        <th class="text-left p-3">To</th>
                        <th class="text-left p-3">Days</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Admin Note</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100">
                        <td class="p-3">{{ row.leave_type }}</td>
                        <td class="p-3">{{ row.from_date }}</td>
                        <td class="p-3">{{ row.to_date }}</td>
                        <td class="p-3">{{ row.days }}</td>
                        <td class="p-3">{{ row.status }}</td>
                        <td class="p-3">{{ row.admin_note ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </article>
    </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { LeaveService } from '../../../services/leave.service';
import { getApiErrorMessage } from '../../../utils/api-error';
import { useToastStore } from '../../../stores/toast.store';

const rows = ref([]);
const toast = useToastStore();
const form = reactive({
    leave_type: '',
    from_date: '',
    to_date: '',
    reason: '',
});

onMounted(load);

async function load() {
    try {
        const response = await LeaveService.myList();
        rows.value = response.data;
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load leave history.'));
    }
}

async function submit() {
    try {
        await LeaveService.apply(form);
        form.leave_type = '';
        form.from_date = '';
        form.to_date = '';
        form.reason = '';
        toast.success('Leave application submitted successfully.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to submit leave request.'));
    }
}
</script>
