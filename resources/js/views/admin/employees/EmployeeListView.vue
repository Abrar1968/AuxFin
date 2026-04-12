<template>
    <section class="space-y-4">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs font-semibold text-slate-600">Search</label>
                <input v-model="search" class="block mt-1 rounded-lg border border-slate-300 px-3 py-2" placeholder="Name or email">
            </div>
            <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold" @click="load">Refresh</button>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Code</th>
                        <th class="text-left p-3">Name</th>
                        <th class="text-left p-3">Email</th>
                        <th class="text-left p-3">Designation</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100">
                        <td class="p-3 font-semibold">{{ row.employee_code }}</td>
                        <td class="p-3">{{ row.user?.name }}</td>
                        <td class="p-3">{{ row.user?.email }}</td>
                        <td class="p-3">{{ row.designation }}</td>
                        <td class="p-3 text-right">
                            <button class="text-xs font-semibold text-blue-700" @click="resetPasskey(row.id)">Reset Passkey</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </article>

        <p v-if="lastPasskey" class="text-sm text-emerald-700">New passkey: <strong>{{ lastPasskey }}</strong></p>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { EmployeeService } from '../../../services/employee.service';

const search = ref('');
const rows = ref([]);
const lastPasskey = ref('');

onMounted(load);

async function load() {
    const response = await EmployeeService.list({ search: search.value });
    rows.value = response.data.data ?? [];
}

async function resetPasskey(id) {
    const response = await EmployeeService.resetPasskey(id);
    lastPasskey.value = response.data.passkey;
}
</script>
