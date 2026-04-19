<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Revenue Operations</p>
                <h1 class="text-2xl font-black text-slate-900">Projects & Clients Portfolio</h1>
                <p class="mt-1 text-sm text-slate-600">Maintain client accounts, contract pipeline, and receivable health in one workspace.</p>
            </div>

            <button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="loadAll">
                Refresh Portfolio
            </button>
        </header>

        <article class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5">
            <div class="grid gap-3 text-sm md:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Booked Revenue</p>
                    <p class="mt-2 text-xl font-black text-slate-900">{{ number(kpis.booked_revenue) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Recognized Revenue</p>
                    <p class="mt-2 text-xl font-black text-slate-900">{{ number(kpis.recognized_revenue) }}</p>
                </div>
                <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-indigo-700">Accounts Receivable</p>
                    <p class="mt-2 text-xl font-black text-indigo-900">{{ number(kpis.accounts_receivable) }}</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-amber-700">Overdue Invoices</p>
                    <p class="mt-2 text-xl font-black text-amber-800">{{ kpis.overdue_invoices ?? 0 }}</p>
                </div>
            </div>

            <div>
                <div class="mb-1 flex items-center justify-between text-sm">
                    <span class="font-semibold text-slate-700">Revenue Collection Rate</span>
                    <span class="font-bold text-slate-900">{{ Number(kpis.collection_rate_percent ?? 0).toFixed(2) }}%</span>
                </div>
                <ProgressBar :value="Number(kpis.collection_rate_percent ?? 0)" />
            </div>

            <div class="grid items-center gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-3">
                    <h4 class="mb-2 text-sm font-semibold text-slate-900">Invoice Funnel</h4>
                    <FunnelChart />
                </div>
                <div class="rounded-xl border border-slate-200 p-3 text-sm">
                    <h4 class="mb-2 font-semibold text-slate-900">Invoice Stage Totals</h4>
                    <ul class="space-y-1">
                        <li v-for="row in invoiceFunnel" :key="row.status" class="flex justify-between">
                            <span class="capitalize text-slate-600">{{ row.status }}</span>
                            <span class="font-semibold text-slate-900">{{ number(row.amount) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Add Client</h2>
            <form class="mt-3 grid md:grid-cols-4 gap-3" @submit.prevent="createClient">
                <input v-model="clientForm.name" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Client name">
                <input v-model="clientForm.email" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Email">
                <input v-model="clientForm.phone" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Phone">
                <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Save Client</button>
            </form>
        </article>

        <article class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <header class="border-b border-slate-200 px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Client Directory</h3>
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                        <span>Per page</span>
                        <select v-model.number="clientFilters.per_page" class="rounded-lg border border-slate-300 px-2 py-1" @change="onClientPerPageChange">
                            <option :value="10">10</option>
                            <option :value="20">20</option>
                            <option :value="50">50</option>
                        </select>
                    </div>
                </div>
            </header>
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Client</th>
                        <th class="text-left p-3">Email</th>
                        <th class="text-left p-3">Phone</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="client in clientRows" :key="client.id" class="border-t border-slate-100 hover:bg-slate-50/70">
                        <td class="p-3">{{ client.name }}</td>
                        <td class="p-3">{{ client.email ?? '-' }}</td>
                        <td class="p-3">{{ client.phone ?? '-' }}</td>
                        <td class="p-3 text-right space-x-3">
                            <button class="text-xs font-semibold text-amber-700" @click="openClientEditModal(client)">Edit</button>
                            <button class="text-xs font-semibold text-rose-700" @click="openClientDeleteModal(client.id)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="clientRows.length === 0">
                        <td colspan="4" class="p-3 text-center text-slate-500">No clients found.</td>
                    </tr>
                </tbody>
            </table>
            <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 px-5 py-3 text-xs text-slate-600">
                <p>Page {{ clientPagination.page }} of {{ clientPagination.last_page }} | {{ clientPagination.total }} clients</p>
                <div class="flex gap-2">
                    <button class="rounded-lg border border-slate-300 px-3 py-1 font-semibold text-slate-700 disabled:opacity-50" :disabled="isClientPrevDisabled" @click="prevClientPage">Prev</button>
                    <button class="rounded-lg border border-slate-300 px-3 py-1 font-semibold text-slate-700 disabled:opacity-50" :disabled="isClientNextDisabled" @click="nextClientPage">Next</button>
                </div>
            </footer>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Create Project</h2>
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

        <article class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <header class="border-b border-slate-200 px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Project Pipeline</h3>
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                        <span>Per page</span>
                        <select v-model.number="projectFilters.per_page" class="rounded-lg border border-slate-300 px-2 py-1" @change="onProjectPerPageChange">
                            <option :value="10">10</option>
                            <option :value="20">20</option>
                            <option :value="50">50</option>
                        </select>
                    </div>
                </div>
            </header>
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
                    <tr v-for="row in projectRows" :key="row.id" class="border-t border-slate-100 hover:bg-slate-50/70">
                        <td class="p-3">{{ row.name }}</td>
                        <td class="p-3">{{ row.client?.name }}</td>
                        <td class="p-3">{{ number(row.contract_amount) }}</td>
                        <td class="p-3">{{ number(row.booked_revenue) }}</td>
                        <td class="p-3">{{ number(row.recognized_revenue) }}</td>
                        <td class="p-3">{{ number(row.accounts_receivable) }}</td>
                        <td class="p-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize" :class="projectStatusClass(row.status)">
                                {{ row.status }}
                            </span>
                        </td>
                        <td class="p-3 text-right space-x-3">
                            <RouterLink :to="`/admin/projects/${row.id}/invoices`" class="text-xs font-semibold text-blue-700">Invoices</RouterLink>
                            <button class="text-xs font-semibold text-amber-700" @click="openProjectEditModal(row)">Edit</button>
                            <button class="text-xs font-semibold text-rose-700" @click="removeProject(row.id)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 px-5 py-3 text-xs text-slate-600">
                <p>Page {{ projectPagination.page }} of {{ projectPagination.last_page }} | {{ projectPagination.total }} projects</p>
                <div class="flex gap-2">
                    <button class="rounded-lg border border-slate-300 px-3 py-1 font-semibold text-slate-700 disabled:opacity-50" :disabled="isProjectPrevDisabled" @click="prevProjectPage">Prev</button>
                    <button class="rounded-lg border border-slate-300 px-3 py-1 font-semibold text-slate-700 disabled:opacity-50" :disabled="isProjectNextDisabled" @click="nextProjectPage">Next</button>
                </div>
            </footer>
        </article>

        <AppModal v-model="showClientEditModal" title="Edit Client" size="md">
            <form class="grid gap-3" @submit.prevent="submitClientEdit">
                <input v-model="clientEditForm.name" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Client name">
                <input v-model="clientEditForm.email" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Email">
                <input v-model="clientEditForm.phone" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Phone">

                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="showClientEditModal = false">Cancel</button>
                    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Save Changes</button>
                </div>
            </form>
        </AppModal>

        <AppModal v-model="showProjectEditModal" title="Edit Project" size="md">
            <form class="grid gap-3" @submit.prevent="submitProjectEdit">
                <select v-model="projectEditForm.client_id" required class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Select Client</option>
                    <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                </select>
                <input v-model="projectEditForm.name" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Project name">
                <input v-model="projectEditForm.contract_amount" required type="number" min="0" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Contract amount">
                <select v-model="projectEditForm.status" required class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="active">Active</option>
                    <option value="on_hold">On Hold</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <input v-model="projectEditForm.start_date" type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <input v-model="projectEditForm.end_date" type="date" class="rounded-lg border border-slate-300 px-3 py-2">

                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="showProjectEditModal = false">Cancel</button>
                    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Save Changes</button>
                </div>
            </form>
        </AppModal>

        <ConfirmModal
            v-model="showClientDeleteModal"
            title="Delete Client"
            message="Are you sure you want to delete this client and linked projects?"
            confirm-text="Delete Client"
            tone="danger"
            @confirm="confirmDeleteClient"
        />
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import AppModal from '../../../components/ui/AppModal.vue';
import ConfirmModal from '../../../components/ui/ConfirmModal.vue';
import FunnelChart from '../../../components/charts/FunnelChart.vue';
import ProgressBar from '../../../components/charts/ProgressBar.vue';
import { FinanceService } from '../../../services/finance.service';
import { getApiErrorMessage } from '../../../utils/api-error';
import { useToastStore } from '../../../stores/toast.store';

const toast = useToastStore();

const kpis = ref({});
const invoiceFunnel = ref([]);
const clients = ref([]);
const clientRows = ref([]);
const projectRows = ref([]);
const clientFilters = reactive({
    page: 1,
    per_page: 20,
});
const projectFilters = reactive({
    page: 1,
    per_page: 20,
});
const clientPagination = ref({
    page: 1,
    per_page: 20,
    total: 0,
    last_page: 1,
});
const projectPagination = ref({
    page: 1,
    per_page: 20,
    total: 0,
    last_page: 1,
});
const showClientEditModal = ref(false);
const showProjectEditModal = ref(false);
const showClientDeleteModal = ref(false);
const clientEditId = ref(null);
const projectEditId = ref(null);
const deleteClientId = ref(null);

const clientForm = reactive({
    name: '',
    email: '',
    phone: '',
});
const clientEditForm = reactive({
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
const projectEditForm = reactive({
    client_id: '',
    name: '',
    contract_amount: '',
    status: 'active',
    start_date: '',
    end_date: '',
});

onMounted(async () => {
    await loadAll();
});

async function loadAll() {
    await Promise.all([loadOverview(), loadClientOptions(), loadClients(), loadProjects()]);
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

async function loadClientOptions() {
    try {
        const response = await FinanceService.clients({ per_page: 500 });
        clients.value = response.data.data ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load clients.'));
    }
}

async function loadClients(resetPage = false) {
    if (resetPage) {
        clientFilters.page = 1;
    }

    try {
        const response = await FinanceService.clients({
            page: clientFilters.page,
            per_page: clientFilters.per_page,
        });

        clientRows.value = response.data.data ?? [];
        syncPagination(clientPagination.value, response.data, clientFilters.per_page);
        clientFilters.page = clientPagination.value.page;
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load clients.'));
    }
}

async function loadProjects(resetPage = false) {
    if (resetPage) {
        projectFilters.page = 1;
    }

    try {
        const response = await FinanceService.projects({
            page: projectFilters.page,
            per_page: projectFilters.per_page,
        });

        projectRows.value = response.data.data ?? [];
        syncPagination(projectPagination.value, response.data, projectFilters.per_page);
        projectFilters.page = projectPagination.value.page;
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
        await Promise.all([loadClientOptions(), loadClients(true)]);
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to create client.'));
    }
}

function openClientEditModal(client) {
    clientEditId.value = client.id;
    clientEditForm.name = client.name ?? '';
    clientEditForm.email = client.email ?? '';
    clientEditForm.phone = client.phone ?? '';
    showClientEditModal.value = true;
}

async function submitClientEdit() {
    if (!clientEditId.value) {
        return;
    }

    try {
        await FinanceService.updateClient(clientEditId.value, {
            name: clientEditForm.name,
            email: clientEditForm.email || null,
            phone: clientEditForm.phone || null,
        });

        showClientEditModal.value = false;
        toast.success('Client updated successfully.');
        await Promise.all([loadClientOptions(), loadClients()]);
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to update client.'));
    }
}

function openClientDeleteModal(id) {
    deleteClientId.value = id;
    showClientDeleteModal.value = true;
}

async function confirmDeleteClient() {
    if (!deleteClientId.value) {
        return;
    }

    try {
        await FinanceService.deleteClient(deleteClientId.value);
        showClientDeleteModal.value = false;
        deleteClientId.value = null;
        toast.success('Client deleted successfully.');
        await Promise.all([loadClientOptions(), loadClients(), loadProjects(), loadOverview()]);
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to delete client.'));
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
        await Promise.all([loadProjects(true), loadOverview()]);
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

function openProjectEditModal(row) {
    projectEditId.value = row.id;
    projectEditForm.client_id = String(row.client_id ?? row.client?.id ?? '');
    projectEditForm.name = row.name ?? '';
    projectEditForm.contract_amount = String(row.contract_amount ?? '0');
    projectEditForm.status = row.status ?? 'active';
    projectEditForm.start_date = row.start_date ?? '';
    projectEditForm.end_date = row.end_date ?? '';
    showProjectEditModal.value = true;
}

async function submitProjectEdit() {
    if (!projectEditId.value) {
        return;
    }

    try {
        const resolvedClientId = Number(projectEditForm.client_id);
        if (!resolvedClientId) {
            toast.warning('Project is missing a valid client reference.');
            return;
        }

        await FinanceService.updateProject(projectEditId.value, {
            client_id: resolvedClientId,
            name: projectEditForm.name,
            contract_amount: Number(projectEditForm.contract_amount),
            status: projectEditForm.status,
            start_date: projectEditForm.start_date || null,
            end_date: projectEditForm.end_date || null,
        });

        showProjectEditModal.value = false;
        toast.success('Project updated successfully.');
        await Promise.all([loadProjects(), loadOverview()]);
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to update project.'));
    }
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}

const isClientPrevDisabled = computed(() => (clientPagination.value.page ?? 1) <= 1);
const isClientNextDisabled = computed(() => (clientPagination.value.page ?? 1) >= (clientPagination.value.last_page ?? 1));
const isProjectPrevDisabled = computed(() => (projectPagination.value.page ?? 1) <= 1);
const isProjectNextDisabled = computed(() => (projectPagination.value.page ?? 1) >= (projectPagination.value.last_page ?? 1));

async function onClientPerPageChange() {
    await loadClients(true);
}

async function onProjectPerPageChange() {
    await loadProjects(true);
}

async function prevClientPage() {
    if (isClientPrevDisabled.value) {
        return;
    }

    clientFilters.page -= 1;
    await loadClients();
}

async function nextClientPage() {
    if (isClientNextDisabled.value) {
        return;
    }

    clientFilters.page += 1;
    await loadClients();
}

async function prevProjectPage() {
    if (isProjectPrevDisabled.value) {
        return;
    }

    projectFilters.page -= 1;
    await loadProjects();
}

async function nextProjectPage() {
    if (isProjectNextDisabled.value) {
        return;
    }

    projectFilters.page += 1;
    await loadProjects();
}

function syncPagination(target, payload, fallbackPerPage = 20) {
    target.page = Number(payload.current_page ?? 1);
    target.per_page = Number(payload.per_page ?? fallbackPerPage);
    target.total = Number(payload.total ?? 0);
    target.last_page = Number(payload.last_page ?? 1);
}

function projectStatusClass(status) {
    const value = String(status ?? '').toLowerCase();

    if (value === 'active') {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (value === 'completed') {
        return 'bg-indigo-100 text-indigo-700';
    }

    if (value === 'on_hold') {
        return 'bg-amber-100 text-amber-700';
    }

    if (value === 'cancelled') {
        return 'bg-rose-100 text-rose-700';
    }

    return 'bg-slate-100 text-slate-700';
}
</script>
