<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Debt Management</p>
                <h1 class="text-2xl font-black text-slate-900">Liability Control Desk</h1>
                <p class="mt-1 text-sm text-slate-600">Track repayment obligations, due exposures, and monthly liability reductions.</p>
            </div>

            <button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="load">
                Refresh Liabilities
            </button>
        </header>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Total Liabilities</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ liabilityPagination.total }}</p>
            </article>
            <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-indigo-700">Outstanding</p>
                <p class="mt-2 text-2xl font-black text-indigo-900">{{ number(totalOutstanding) }}</p>
            </article>
            <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose-700">Due In 7 Days</p>
                <p class="mt-2 text-2xl font-black text-rose-800">{{ dueSoon.length }}</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-amber-700">Monthly Commitment</p>
                <p class="mt-2 text-2xl font-black text-amber-800">{{ number(totalMonthlyCommitment) }}</p>
            </article>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Register Liability</h2>
            <form class="mt-3 grid md:grid-cols-3 gap-3" @submit.prevent="createLiability">
                <input v-model="form.name" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Name">
                <input v-model="form.principal_amount" required type="number" min="0" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Principal">
                <input v-model="form.monthly_payment" required type="number" min="0" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Monthly payment">
                <input v-model="form.start_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <input v-model="form.next_due_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <button class="rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-semibold">Save Liability</button>
            </form>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Due Soon (7 Days)</h2>
            <ul class="mt-3 text-sm space-y-1">
                <li v-for="row in dueSoon" :key="row.id" class="flex justify-between rounded-lg bg-slate-100 px-3 py-2">
                    <span>{{ row.name }} ({{ row.next_due_date }})</span>
                    <strong>{{ number(row.outstanding) }}</strong>
                </li>
                <li v-if="dueSoon.length === 0" class="text-slate-500">No liabilities due soon.</li>
            </ul>
        </article>

        <article class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <header class="border-b border-slate-200 px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Liability Ledger</h3>
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                        <span>Per page</span>
                        <select v-model.number="liabilityFilters.per_page" class="rounded-lg border border-slate-300 px-2 py-1" @change="onLiabilityPerPageChange">
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
                        <th class="text-left p-3">Name</th>
                        <th class="text-left p-3">Outstanding</th>
                        <th class="text-left p-3">Monthly</th>
                        <th class="text-left p-3">Months Left</th>
                        <th class="text-left p-3">Next Due</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100 hover:bg-slate-50/70">
                        <td class="p-3">{{ row.name }}</td>
                        <td class="p-3">{{ number(row.outstanding) }}</td>
                        <td class="p-3">{{ number(row.monthly_payment) }}</td>
                        <td class="p-3">{{ row.months_left ?? '-' }}</td>
                        <td class="p-3">{{ row.next_due_date ?? '-' }}</td>
                        <td class="p-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize" :class="statusClass(row.status)">
                                {{ row.status }}
                            </span>
                        </td>
                        <td class="p-3 text-right space-x-3">
                            <button class="text-xs font-semibold text-amber-700" @click="openEditModal(row)">Edit</button>
                            <button class="text-xs font-semibold text-blue-700" @click="openPayModal(row.id)">Pay</button>
                            <button class="text-xs font-semibold text-rose-700" @click="remove(row.id)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td colspan="7" class="p-4 text-center text-slate-500">No liabilities found.</td>
                    </tr>
                </tbody>
            </table>
            <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 px-5 py-3 text-xs text-slate-600">
                <p>Page {{ liabilityPagination.page }} of {{ liabilityPagination.last_page }} | {{ liabilityPagination.total }} liabilities</p>
                <div class="flex gap-2">
                    <button class="rounded-lg border border-slate-300 px-3 py-1 font-semibold text-slate-700 disabled:opacity-50" :disabled="isLiabilityPrevDisabled" @click="prevLiabilityPage">Prev</button>
                    <button class="rounded-lg border border-slate-300 px-3 py-1 font-semibold text-slate-700 disabled:opacity-50" :disabled="isLiabilityNextDisabled" @click="nextLiabilityPage">Next</button>
                </div>
            </footer>
        </article>

        <AppModal v-model="showEditModal" title="Edit Liability" size="md">
            <form class="grid gap-3" @submit.prevent="submitEditLiability">
                <input v-model="editForm.name" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Name">
                <input v-model="editForm.outstanding" required type="number" min="0" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Outstanding amount">
                <input v-model="editForm.monthly_payment" required type="number" min="0.01" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Monthly payment">
                <input v-model="editForm.next_due_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <select v-model="editForm.status" required class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="defaulted">Defaulted</option>
                </select>

                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="showEditModal = false">Cancel</button>
                    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Save Changes</button>
                </div>
            </form>
        </AppModal>

        <AppModal v-model="showPayModal" title="Process Liability Payment" size="sm">
            <form class="grid gap-3" @submit.prevent="submitPayLiability">
                <p class="text-sm text-slate-600">Leave amount blank to apply scheduled monthly payment.</p>
                <input v-model="payForm.amount" type="number" min="0.01" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Payment amount (optional)">

                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="showPayModal = false">Cancel</button>
                    <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Process Payment</button>
                </div>
            </form>
        </AppModal>
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AppModal from '../../../components/ui/AppModal.vue';
import { FinanceService } from '../../../services/finance.service';
import { getApiErrorMessage } from '../../../utils/api-error';
import { useToastStore } from '../../../stores/toast.store';

const toast = useToastStore();

const rows = ref([]);
const liabilityFilters = reactive({
    page: 1,
    per_page: 20,
});
const liabilityPagination = ref({
    page: 1,
    per_page: 20,
    total: 0,
    last_page: 1,
});
const dueSoon = ref([]);
const showEditModal = ref(false);
const showPayModal = ref(false);
const editLiabilityId = ref(null);
const payLiabilityId = ref(null);

const form = reactive({
    name: '',
    principal_amount: '',
    monthly_payment: '',
    start_date: new Date().toISOString().slice(0, 10),
    next_due_date: new Date().toISOString().slice(0, 10),
});
const editForm = reactive({
    name: '',
    outstanding: '',
    monthly_payment: '',
    next_due_date: new Date().toISOString().slice(0, 10),
    status: 'active',
});
const payForm = reactive({
    amount: '',
});

const totalOutstanding = computed(() => rows.value.reduce((sum, row) => sum + Number(row.outstanding ?? 0), 0));
const totalMonthlyCommitment = computed(() => rows.value.reduce((sum, row) => sum + Number(row.monthly_payment ?? 0), 0));

onMounted(load);

async function load() {
    try {
        const [list, due] = await Promise.all([
            FinanceService.liabilities({
                page: liabilityFilters.page,
                per_page: liabilityFilters.per_page,
            }),
            FinanceService.liabilitiesDueSoon({ days: 7 }),
        ]);

        rows.value = list.data.data ?? [];
        dueSoon.value = due.data.rows ?? [];
        syncPagination(liabilityPagination.value, list.data, liabilityFilters.per_page);
        liabilityFilters.page = liabilityPagination.value.page;
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load liabilities.'));
    }
}

async function createLiability() {
    try {
        await FinanceService.createLiability({
            name: form.name,
            principal_amount: Number(form.principal_amount),
            monthly_payment: Number(form.monthly_payment),
            start_date: form.start_date,
            next_due_date: form.next_due_date,
        });

        form.name = '';
        form.principal_amount = '';
        form.monthly_payment = '';
        form.start_date = new Date().toISOString().slice(0, 10);
        form.next_due_date = new Date().toISOString().slice(0, 10);

        toast.success('Liability created successfully.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to create liability.'));
    }
}

function openEditModal(row) {
    editLiabilityId.value = row.id;
    editForm.name = row.name ?? '';
    editForm.outstanding = String(row.outstanding ?? '0');
    editForm.monthly_payment = String(row.monthly_payment ?? '0');
    editForm.next_due_date = row.next_due_date ?? new Date().toISOString().slice(0, 10);
    editForm.status = row.status ?? 'active';
    showEditModal.value = true;
}

async function submitEditLiability() {
    if (!editLiabilityId.value) {
        return;
    }

    try {
        await FinanceService.updateLiability(editLiabilityId.value, {
            name: editForm.name,
            outstanding: Number(editForm.outstanding),
            monthly_payment: Number(editForm.monthly_payment),
            next_due_date: editForm.next_due_date,
            status: editForm.status,
        });

        showEditModal.value = false;
        toast.success('Liability updated successfully.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to update liability.'));
    }
}

function openPayModal(id) {
    payLiabilityId.value = id;
    payForm.amount = '';
    showPayModal.value = true;
}

async function submitPayLiability() {
    if (!payLiabilityId.value) {
        return;
    }

    try {
        await FinanceService.processLiabilityPayment(
            payLiabilityId.value,
            payForm.amount ? { amount: Number(payForm.amount) } : {}
        );

        showPayModal.value = false;
        toast.success('Liability payment processed.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to process liability payment.'));
    }
}

async function remove(id) {
    try {
        await FinanceService.deleteLiability(id);
        toast.success('Liability deleted successfully.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to delete liability.'));
    }
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}

function statusClass(status) {
    const value = String(status ?? '').toLowerCase();

    if (value === 'active') {
        return 'bg-indigo-100 text-indigo-700';
    }

    if (value === 'completed') {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (value === 'defaulted') {
        return 'bg-rose-100 text-rose-700';
    }

    return 'bg-slate-100 text-slate-700';
}

const isLiabilityPrevDisabled = computed(() => (liabilityPagination.value.page ?? 1) <= 1);
const isLiabilityNextDisabled = computed(() => (liabilityPagination.value.page ?? 1) >= (liabilityPagination.value.last_page ?? 1));

async function onLiabilityPerPageChange() {
    liabilityFilters.page = 1;
    await load();
}

async function prevLiabilityPage() {
    if (isLiabilityPrevDisabled.value) {
        return;
    }

    liabilityFilters.page -= 1;
    await load();
}

async function nextLiabilityPage() {
    if (isLiabilityNextDisabled.value) {
        return;
    }

    liabilityFilters.page += 1;
    await load();
}

function syncPagination(target, payload, fallbackPerPage = 20) {
    target.page = Number(payload.current_page ?? 1);
    target.per_page = Number(payload.per_page ?? fallbackPerPage);
    target.total = Number(payload.total ?? 0);
    target.last_page = Number(payload.last_page ?? 1);
}
</script>
