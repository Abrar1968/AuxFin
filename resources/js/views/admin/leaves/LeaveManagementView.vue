<template>
    <section class="space-y-4">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs font-semibold text-slate-600">Status</label>
                <select v-model="status" class="block mt-1 rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold" @click="load">Refresh</button>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Employee</th>
                        <th class="text-left p-3">Type</th>
                        <th class="text-left p-3">Range</th>
                        <th class="text-left p-3">Days</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Admin Note</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100">
                        <td class="p-3">{{ row.employee?.user?.name }}</td>
                        <td class="p-3">{{ row.leave_type }}</td>
                        <td class="p-3">{{ row.from_date }} - {{ row.to_date }}</td>
                        <td class="p-3">{{ row.days }}</td>
                        <td class="p-3">{{ row.status }}</td>
                        <td class="p-3">{{ row.admin_note ?? '-' }}</td>
                        <td class="p-3 text-right space-x-3">
                            <button
                                v-if="row.status === 'pending'"
                                class="text-xs font-semibold text-emerald-700"
                                @click="decide(row.id, 'approved')"
                            >
                                Approve
                            </button>
                            <button
                                v-if="row.status === 'pending'"
                                class="text-xs font-semibold text-rose-700"
                                @click="decide(row.id, 'rejected')"
                            >
                                Reject
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </article>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { LeaveService } from '../../../services/leave.service';
import { getApiErrorMessage } from '../../../utils/api-error';
import { useToastStore } from '../../../stores/toast.store';

const status = ref('pending');
const rows = ref([]);
const toast = useToastStore();

onMounted(load);

async function load() {
    try {
        const response = await LeaveService.adminList({ status: status.value || undefined });
        rows.value = response.data.data ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load leave requests.'));
    }
}

async function decide(id, nextStatus) {
    const adminNote = nextStatus === 'rejected' ? window.prompt('Enter rejection reason') : '';
    if (nextStatus === 'rejected' && !adminNote) {
        return;
    }

    try {
        await LeaveService.decide(id, {
            status: nextStatus,
            admin_note: adminNote || undefined,
        });

        toast.success(nextStatus === 'approved' ? 'Leave approved.' : 'Leave rejected.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to submit leave decision.'));
    }
}
</script>
