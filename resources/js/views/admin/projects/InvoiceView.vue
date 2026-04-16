<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Billing Operations</p>
                <h1 class="text-2xl font-black text-slate-900">Project Invoice Console</h1>
                <p class="mt-1 text-sm text-slate-600">Track billed milestones, receivables, and payment state transitions for this project.</p>
            </div>

            <button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="loadAll">
                Refresh Invoices
            </button>
        </header>

        <article class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Project Revenue</h2>
                <p class="text-sm font-semibold text-slate-700">{{ project?.name || '-' }} | {{ project?.client?.name || '-' }}</p>
            </div>

            <div class="grid gap-3 text-sm sm:grid-cols-5">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Booked</p>
                    <p class="mt-2 text-lg font-black text-slate-900">{{ number(summary.booked_revenue) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Accrued</p>
                    <p class="mt-2 text-lg font-black text-slate-900">{{ number(summary.accrued_revenue ?? summary.recognized_revenue) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Cash Collected</p>
                    <p class="mt-2 text-lg font-black text-slate-900">{{ number(summary.cash_collected) }}</p>
                </div>
                <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-indigo-700">AR</p>
                    <p class="mt-2 text-lg font-black text-indigo-900">{{ number(summary.accounts_receivable) }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-emerald-700">Collection Rate</p>
                    <p class="mt-2 text-lg font-black text-emerald-800">{{ Number(summary.collection_rate_percent ?? 0).toFixed(2) }}%</p>
                </div>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Create Invoice</h2>
            <form class="mt-3 grid md:grid-cols-5 gap-3" @submit.prevent="createInvoice">
                <input v-model="form.invoice_number" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Invoice number">
                <input v-model="form.amount" required type="number" min="0" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Amount">
                <input v-model="form.invoice_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <input v-model="form.due_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <button class="rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-semibold">Create</button>
            </form>
        </article>

        <article class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <header class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Invoice Ledger</h3>
            </header>
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Invoice #</th>
                        <th class="text-left p-3">Amount</th>
                        <th class="text-left p-3">Invoice Date</th>
                        <th class="text-left p-3">Due</th>
                        <th class="text-left p-3">Partial</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Paid At</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100 hover:bg-slate-50/70">
                        <td class="p-3">{{ row.invoice_number }}</td>
                        <td class="p-3">{{ number(row.amount) }}</td>
                        <td class="p-3">{{ row.invoice_date ?? '-' }}</td>
                        <td class="p-3">{{ row.due_date }}</td>
                        <td class="p-3">{{ row.partial_amount ? number(row.partial_amount) : '-' }}</td>
                        <td class="p-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize" :class="statusClass(row.status)">
                                {{ row.status }}
                            </span>
                        </td>
                        <td class="p-3">{{ row.payment_completed_at ?? '-' }}</td>
                        <td class="p-3 text-right space-x-2">
                            <button class="text-xs font-semibold text-blue-700" @click="transition(row.id, 'sent')">Sent</button>
                            <button class="text-xs font-semibold text-amber-700" @click="openPartialModal(row)">Partial</button>
                            <button class="text-xs font-semibold text-emerald-700" @click="transition(row.id, 'paid')">Paid</button>
                            <button class="text-xs font-semibold text-rose-700" @click="remove(row.id)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td colspan="8" class="p-4 text-center text-slate-500">No invoices found for this project.</td>
                    </tr>
                </tbody>
            </table>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 space-y-3">
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Project Payment Records</h3>
                <span class="text-xs text-slate-600">All cash collection entries for this project</span>
            </div>

            <form class="grid gap-3 md:grid-cols-6" @submit.prevent="recordPayment">
                <select v-model="paymentForm.invoice_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">No Invoice (Advance / Misc)</option>
                    <option v-for="invoice in invoiceOptions" :key="invoice.id" :value="String(invoice.id)">
                        {{ invoice.invoice_number }} (Outstanding {{ number(invoiceOutstanding(invoice)) }})
                    </option>
                </select>

                <input v-model="paymentForm.payment_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <input v-model="paymentForm.amount" required type="number" min="0.01" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Amount">

                <select v-model="paymentForm.payment_method" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cash">Cash</option>
                    <option value="check">Check</option>
                    <option value="online_gateway">Online Gateway</option>
                    <option value="mobile_banking">Mobile Banking</option>
                    <option value="other">Other</option>
                </select>

                <input v-model="paymentForm.reference_number" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Reference #">
                <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Record Payment</button>
            </form>

            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Date</th>
                        <th class="text-left p-3">Invoice</th>
                        <th class="text-left p-3">Method</th>
                        <th class="text-left p-3">Reference</th>
                        <th class="text-left p-3">Amount</th>
                        <th class="text-right p-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="payment in payments" :key="payment.id" class="border-t border-slate-100 hover:bg-slate-50/70">
                        <td class="p-3">{{ payment.payment_date }}</td>
                        <td class="p-3">{{ payment.invoice?.invoice_number ?? '-' }}</td>
                        <td class="p-3">{{ payment.payment_method ?? '-' }}</td>
                        <td class="p-3">{{ payment.reference_number ?? '-' }}</td>
                        <td class="p-3">{{ number(payment.amount) }}</td>
                        <td class="p-3 text-right">
                            <button class="text-xs font-semibold text-rose-700" @click="removePayment(payment.id)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="payments.length === 0">
                        <td colspan="6" class="p-4 text-center text-slate-500">No payment records found for this project.</td>
                    </tr>
                </tbody>
            </table>
        </article>

        <AppModal v-model="showPartialModal" title="Record Partial Payment" size="sm">
            <form class="grid gap-3" @submit.prevent="submitPartialPayment">
                <input
                    v-model="partialAmount"
                    required
                    type="number"
                    min="0.01"
                    step="0.01"
                    class="rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Received amount"
                >

                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="showPartialModal = false">Cancel</button>
                    <button class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white">Save</button>
                </div>
            </form>
        </AppModal>
    </section>
</template>

<script setup>
import AppModal from '../../../components/ui/AppModal.vue';
import { computed, onMounted, reactive, ref } from 'vue';
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
const payments = ref([]);
const showPartialModal = ref(false);
const partialInvoiceId = ref(null);
const partialAmount = ref('');

const todayDate = new Date().toISOString().slice(0, 10);

const form = reactive({
    invoice_number: '',
    amount: '',
    invoice_date: todayDate,
    due_date: '',
});

const paymentForm = reactive({
    invoice_id: '',
    payment_date: todayDate,
    amount: '',
    payment_method: 'bank_transfer',
    reference_number: '',
});

const invoiceOptions = computed(() => rows.value.filter((row) => invoiceOutstanding(row) > 0));

onMounted(async () => {
    await loadAll();
});

async function loadAll() {
    await Promise.all([loadProjectRevenue(), loadInvoices(), loadPayments()]);
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

async function loadPayments() {
    try {
        const response = await FinanceService.projectPayments(projectId, { per_page: 50 });
        payments.value = response.data.data ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load project payments.'));
    }
}

async function createInvoice() {
    try {
        await FinanceService.createInvoice(projectId, {
            invoice_number: form.invoice_number,
            amount: Number(form.amount),
            invoice_date: form.invoice_date,
            due_date: form.due_date,
        });

        form.invoice_number = '';
        form.amount = '';
        form.invoice_date = todayDate;
        form.due_date = '';

        toast.success('Invoice created successfully.');
        await loadAll();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to create invoice.'));
    }
}

function openPartialModal(row) {
    partialInvoiceId.value = row.id;
    partialAmount.value = row.partial_amount ? String(row.partial_amount) : '';
    showPartialModal.value = true;
}

async function submitPartialPayment() {
    if (!partialInvoiceId.value || !partialAmount.value) {
        return;
    }

    await transition(partialInvoiceId.value, 'partial', { partial_amount: Number(partialAmount.value) });
    showPartialModal.value = false;
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

function invoiceOutstanding(invoice) {
    return Math.max(0, Number(invoice.amount ?? 0) - Number(invoice.partial_amount ?? 0));
}

async function recordPayment() {
    try {
        const payload = {
            payment_date: paymentForm.payment_date,
            amount: Number(paymentForm.amount),
            payment_method: paymentForm.payment_method,
            reference_number: paymentForm.reference_number || null,
        };

        if (paymentForm.invoice_id) {
            payload.invoice_id = Number(paymentForm.invoice_id);
        }

        await FinanceService.recordProjectPayment(projectId, payload);

        paymentForm.invoice_id = '';
        paymentForm.payment_date = todayDate;
        paymentForm.amount = '';
        paymentForm.payment_method = 'bank_transfer';
        paymentForm.reference_number = '';

        toast.success('Project payment recorded successfully.');
        await loadAll();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to record project payment.'));
    }
}

async function removePayment(id) {
    try {
        await FinanceService.deleteProjectPayment(projectId, id);
        toast.success('Project payment deleted successfully.');
        await loadAll();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to delete project payment.'));
    }
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}

function statusClass(status) {
    const value = String(status ?? '').toLowerCase();

    if (value === 'paid') {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (value === 'partial') {
        return 'bg-amber-100 text-amber-700';
    }

    if (value === 'sent') {
        return 'bg-indigo-100 text-indigo-700';
    }

    if (value === 'overdue') {
        return 'bg-rose-100 text-rose-700';
    }

    return 'bg-slate-100 text-slate-700';
}
</script>
