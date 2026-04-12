<template>
    <section class="space-y-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 space-y-4">
            <div class="grid md:grid-cols-4 gap-3 text-sm">
                <div class="rounded-lg bg-slate-100 p-3">Booked Revenue: <strong>{{ number(kpis.booked_revenue) }}</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">Recognized Revenue: <strong>{{ number(kpis.recognized_revenue) }}</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">Accounts Receivable: <strong>{{ number(kpis.accounts_receivable) }}</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">Overdue Invoices: <strong>{{ kpis.overdue_invoices ?? 0 }}</strong></div>
            </div>

            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="font-semibold text-slate-700">Revenue Collection Rate</span>
                    <span class="font-bold">{{ Number(kpis.collection_rate_percent ?? 0).toFixed(2) }}%</span>
                </div>
                <ProgressBar :value="Number(kpis.collection_rate_percent ?? 0)" />
            </div>

            <div class="grid md:grid-cols-2 gap-4 items-center">
                <div class="rounded-xl border border-slate-200 p-3">
                    <h4 class="font-semibold text-sm mb-2">Invoice Funnel</h4>
                    <FunnelChart />
                </div>
                <div class="rounded-xl border border-slate-200 p-3 text-sm">
                    <h4 class="font-semibold mb-2">Invoice Stage Totals</h4>
                    <ul class="space-y-1">
                        <li v-for="row in invoiceFunnel" :key="row.status" class="flex justify-between">
                            <span class="capitalize">{{ row.status }}</span>
                            <span>{{ number(row.amount) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="font-bold">Add Client</h3>
            <form class="mt-3 grid md:grid-cols-4 gap-3" @submit.prevent="createClient">
                <input v-model="clientForm.name" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Client name">
                <input v-model="clientForm.email" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Email">
                <input v-model="clientForm.phone" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Phone">
                <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Save Client</button>
            </form>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="font-bold">Create Project</h3>
            <form class="mt-3 grid md:grid-cols-3 gap-3" @submit.prevent="createProject">
                <select v-model="projectForm.client_id" required class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Select Client</option>
                    <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                </select>
                <input v-model="projectForm.name" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Project name">
                <input v-model="projectForm.contract_amount" required type="number" min="0" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Contract amount">
                <select v-model="projectForm.status" class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="active">Active</option>
                    <option value="on_hold">On Hold</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <input v-model="projectForm.start_date" type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <button class="rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-semibold">Create Project</button>
            </form>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Project</th>
                        <th class="text-left p-3">Client</th>
                        <th class="text-left p-3">Contract</th>
                        <th class="text-left p-3">Booked</th>
                        <th class="text-left p-3">Recognized</th>
                        <th class="text-left p-3">AR</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in projectRows" :key="row.id" class="border-t border-slate-100">
                        <td class="p-3">{{ row.name }}</td>
                        <td class="p-3">{{ row.client?.name }}</td>
                        <td class="p-3">{{ number(row.contract_amount) }}</td>
                        <td class="p-3">{{ number(row.booked_revenue) }}</td>
                        <td class="p-3">{{ number(row.recognized_revenue) }}</td>
                        <td class="p-3">{{ number(row.accounts_receivable) }}</td>
                        <td class="p-3 capitalize">{{ row.status }}</td>
                        <td class="p-3 text-right space-x-3">
                            <RouterLink :to="`/admin/projects/${row.id}/invoices`" class="text-xs font-semibold text-blue-700">Invoices</RouterLink>
                            <button class="text-xs font-semibold text-rose-700" @click="removeProject(row.id)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </article>
    </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import FunnelChart from '../../../components/charts/FunnelChart.vue';
import ProgressBar from '../../../components/charts/ProgressBar.vue';
import { FinanceService } from '../../../services/finance.service';
import { getApiErrorMessage } from '../../../utils/api-error';
import { useToastStore } from '../../../stores/toast.store';

const toast = useToastStore();

const kpis = ref({});
const invoiceFunnel = ref([]);
const clients = ref([]);
const projectRows = ref([]);

const clientForm = reactive({
    name: '',
    email: '',
    phone: '',
});

const projectForm = reactive({
    client_id: '',
    name: '',
    contract_amount: '',
    status: 'active',
    start_date: '',
});

onMounted(async () => {
    await loadAll();
});

async function loadAll() {
    await Promise.all([loadOverview(), loadClients(), loadProjects()]);
}

async function loadOverview() {
    try {
        const response = await FinanceService.overview();
        kpis.value = response.data.kpis ?? {};
        invoiceFunnel.value = response.data.invoice_funnel ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load finance overview.'));
    }
}

async function loadClients() {
    try {
        const response = await FinanceService.clients({ per_page: 200 });
        clients.value = response.data.data ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load clients.'));
    }
}

async function loadProjects() {
    try {
        const response = await FinanceService.projects();
        projectRows.value = response.data.data ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load projects.'));
    }
}

async function createClient() {
    try {
        await FinanceService.createClient(clientForm);
        clientForm.name = '';
        clientForm.email = '';
        clientForm.phone = '';
        toast.success('Client created successfully.');
        await loadClients();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to create client.'));
    }
}

async function createProject() {
    try {
        await FinanceService.createProject({
            client_id: Number(projectForm.client_id),
            name: projectForm.name,
            contract_amount: Number(projectForm.contract_amount),
            status: projectForm.status,
            start_date: projectForm.start_date || undefined,
        });

        projectForm.client_id = '';
        projectForm.name = '';
        projectForm.contract_amount = '';
        projectForm.status = 'active';
        projectForm.start_date = '';

        toast.success('Project created successfully.');
        await Promise.all([loadProjects(), loadOverview()]);
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to create project.'));
    }
}

async function removeProject(id) {
    try {
        await FinanceService.deleteProject(id);
        toast.success('Project deleted successfully.');
        await Promise.all([loadProjects(), loadOverview()]);
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to delete project.'));
    }
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}
</script>
