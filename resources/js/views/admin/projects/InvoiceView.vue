<template>
    <section class="space-y-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 space-y-3">
            <h3 class="font-bold">Project Revenue</h3>
            <p class="text-sm text-slate-600">{{ project?.name }} | {{ project?.client?.name }}</p>
            <div class="grid sm:grid-cols-4 gap-3 text-sm">
                <div class="rounded-lg bg-slate-100 p-3">Booked: <strong>{{ number(summary.booked_revenue) }}</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">Recognized: <strong>{{ number(summary.recognized_revenue) }}</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">AR: <strong>{{ number(summary.accounts_receivable) }}</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">Collection: <strong>{{ Number(summary.collection_rate_percent ?? 0).toFixed(2) }}%</strong></div>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="font-bold">Create Invoice</h3>
            <form class="mt-3 grid md:grid-cols-4 gap-3" @submit.prevent="createInvoice">
                <input v-model="form.invoice_number" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Invoice number">
                <input v-model="form.amount" required type="number" min="0" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Amount">
                <input v-model="form.due_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <button class="rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-semibold">Create</button>
            </form>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Invoice #</th>
                        <th class="text-left p-3">Amount</th>
                        <th class="text-left p-3">Due</th>
                        <th class="text-left p-3">Partial</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Paid At</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100">
                        <td class="p-3">{{ row.invoice_number }}</td>
                        <td class="p-3">{{ number(row.amount) }}</td>
                        <td class="p-3">{{ row.due_date }}</td>
                        <td class="p-3">{{ row.partial_amount ? number(row.partial_amount) : '-' }}</td>
                        <td class="p-3 capitalize">{{ row.status }}</td>
                        <td class="p-3">{{ row.payment_completed_at ?? '-' }}</td>
                        <td class="p-3 text-right space-x-2">
                            <button class="text-xs font-semibold text-blue-700" @click="transition(row.id, 'sent')">Sent</button>
                            <button class="text-xs font-semibold text-amber-700" @click="markPartial(row)">Partial</button>
                            <button class="text-xs font-semibold text-emerald-700" @click="transition(row.id, 'paid')">Paid</button>
                            <button class="text-xs font-semibold text-rose-700" @click="remove(row.id)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </article>
    </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import { FinanceService } from '../../../services/finance.service';
import { getApiErrorMessage } from '../../../utils/api-error';
import { useToastStore } from '../../../stores/toast.store';

const route = useRoute();
const projectId = Number(route.params.id);
const toast = useToastStore();

const project = ref(null);
const summary = ref({});
const rows = ref([]);

const form = reactive({
    invoice_number: '',
    amount: '',
    due_date: '',
});

onMounted(async () => {
    await loadAll();
});

async function loadAll() {
    await Promise.all([loadProjectRevenue(), loadInvoices()]);
}

async function loadProjectRevenue() {
    try {
        const response = await FinanceService.projectRevenue(projectId);
        project.value = response.data.project;
        summary.value = response.data.summary ?? {};
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load project revenue.'));
    }
}

async function loadInvoices() {
    try {
        const response = await FinanceService.projectInvoices(projectId);
        rows.value = response.data.data ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load invoices.'));
    }
}

async function createInvoice() {
    try {
        await FinanceService.createInvoice(projectId, {
            invoice_number: form.invoice_number,
            amount: Number(form.amount),
            due_date: form.due_date,
        });

        form.invoice_number = '';
        form.amount = '';
        form.due_date = '';

        toast.success('Invoice created successfully.');
        await loadAll();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to create invoice.'));
    }
}

async function markPartial(row) {
    const amount = window.prompt('Enter received partial amount', row.partial_amount ?? '');
    if (!amount) {
        return;
    }

    await transition(row.id, 'partial', { partial_amount: Number(amount) });
}

async function transition(id, status, extra = {}) {
    try {
        await FinanceService.transitionInvoice(projectId, id, {
            status,
            ...extra,
        });

        toast.success('Invoice status updated successfully.');
        await loadAll();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to update invoice status.'));
    }
}

async function remove(id) {
    try {
        await FinanceService.deleteInvoice(projectId, id);
        toast.success('Invoice deleted successfully.');
        await loadAll();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to delete invoice.'));
    }
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}
</script>
