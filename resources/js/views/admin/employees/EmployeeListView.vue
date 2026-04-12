<template>
    <section class="space-y-4">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">People Ops</p>
                <h1 class="text-2xl font-black text-slate-900">Employee Directory</h1>
            </div>

            <RouterLink
                :to="{ name: 'admin.employee.create' }"
                class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
            >
                Add Employee
            </RouterLink>
        </header>

        <div class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4">
            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Search</label>
                <input
                    v-model="search"
                    class="mt-1 block rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Name, code, or email"
                >
            </div>

            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white" @click="load">Refresh</button>
        </div>

        <article class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="p-3 text-left">Code</th>
                        <th class="p-3 text-left">Name</th>
                        <th class="p-3 text-left">Email</th>
                        <th class="p-3 text-left">Designation</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100">
                        <td class="p-3 font-semibold">{{ row.employee_code }}</td>
                        <td class="p-3">{{ row.user?.name }}</td>
                        <td class="p-3">{{ row.user?.email }}</td>
                        <td class="p-3">{{ row.designation }}</td>
                        <td class="p-3">
                            <StatusBadge :status="row.user?.is_active ? 'active' : 'inactive'" :label="row.user?.is_active ? 'active' : 'inactive'" />
                        </td>
                        <td class="space-x-3 p-3 text-right">
                            <RouterLink
                                :to="{ name: 'admin.employee.detail', params: { id: row.id } }"
                                class="text-xs font-semibold text-indigo-700 hover:text-indigo-900"
                            >
                                View
                            </RouterLink>
                            <button class="text-xs font-semibold text-blue-700" @click="resetPasskey(row.id)">Reset Passkey</button>
                        </td>
                    </tr>

                    <tr v-if="rows.length === 0">
                        <td colspan="6" class="p-4 text-center text-slate-500">No employees found.</td>
                    </tr>
                </tbody>
            </table>
        </article>

        <p v-if="lastPasskey" class="text-sm text-emerald-700">New passkey: <strong>{{ lastPasskey }}</strong></p>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import StatusBadge from '../../../components/ui/StatusBadge.vue';
import { EmployeeService } from '../../../services/employee.service';
import { useToastStore } from '../../../stores/toast.store';
import { getApiErrorMessage } from '../../../utils/api-error';

const toast = useToastStore();
const search = ref('');
const rows = ref([]);
const lastPasskey = ref('');

onMounted(load);

async function load() {
    try {
        const response = await EmployeeService.list({ search: search.value });
        rows.value = response.data.data ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load employees.'));
    }
}

async function resetPasskey(id) {
    try {
        const response = await EmployeeService.resetPasskey(id);
        lastPasskey.value = response.data.passkey;
        toast.success('Passkey reset successfully.');
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to reset passkey.'));
    }
}
</script>
