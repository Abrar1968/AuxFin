<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Expense Governance</p>
                <h1 class="text-2xl font-black text-slate-900">Operating Expense Center</h1>
                <p class="mt-1 text-sm text-slate-600">Control month-wise spend, recurring commitments, and category-level tracking.</p>
            </div>

            <div class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Month</label>
                    <input v-model="month" type="date" class="mt-1 block rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                </div>
                <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700" @click="load">Refresh</button>
            </div>
        </header>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Monthly Total</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ number(summary.monthly_total) }}</p>
            </article>
            <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-indigo-700">Recurring Baseline</p>
                <p class="mt-2 text-2xl font-black text-indigo-900">{{ number(summary.recurring_total) }}</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-amber-700">Variable Spend</p>
                <p class="mt-2 text-2xl font-black text-amber-800">{{ number(variableTotal) }}</p>
            </article>
            <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose-700">Outstanding Payables</p>
                <p class="mt-2 text-2xl font-black text-rose-800">{{ number(summary.outstanding_payables) }}</p>
            </article>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Add Expense</h2>
            <form class="mt-3 grid md:grid-cols-3 gap-3" @submit.prevent="createExpense">
                <input v-model="form.category" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Category">
                <input v-model="form.amount" required type="number" min="0" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Amount">
                <input v-model="form.expense_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <select v-model="form.accounting_mode" class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="cash">Cash Expense (Immediate Payment)</option>
                    <option value="payable">Accrued Payable (Pay Later)</option>
                    <option value="prepaid">Prepaid Expense (Amortize Monthly)</option>
                </select>
                <input
                    v-if="form.accounting_mode === 'payable'"
                    v-model="form.payable_due_date"
                    type="date"
                    class="rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Payable due date"
                >
                <template v-if="form.accounting_mode === 'prepaid'">
                    <input v-model="form.prepaid_start_date" type="date" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Prepaid start date">
                    <input v-model="form.prepaid_months" type="number" min="1" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Prepaid months">
                </template>
                <textarea v-model="form.description" required rows="2" class="md:col-span-3 rounded-lg border border-slate-300 px-3 py-2" placeholder="Description"></textarea>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input v-model="form.is_recurring" type="checkbox">
                    Recurring expense
                </label>
                <select v-if="form.is_recurring" v-model="form.recurrence" class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="yearly">Yearly</option>
                </select>
                <input v-if="form.is_recurring" v-model="form.next_due_date" type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <button class="md:col-span-3 rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-semibold">Save Expense</button>
            </form>
        </article>

        <article class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <header class="border-b border-slate-200 px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Expense Ledger</h3>
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                        <span>Per page</span>
                        <select v-model.number="expenseFilters.per_page" class="rounded-lg border border-slate-300 px-2 py-1" @change="onExpensePerPageChange">
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
                        <th class="text-left p-3">Date</th>
                        <th class="text-left p-3">Category</th>
                        <th class="text-left p-3">Mode</th>
                        <th class="text-left p-3">Description</th>
                        <th class="text-left p-3">Amount</th>
                        <th class="text-left p-3">Paid</th>
                        <th class="text-left p-3">Outstanding</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Recurring</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100 hover:bg-slate-50/70">
                        <td class="p-3">{{ row.expense_date }}</td>
                        <td class="p-3">{{ row.category }}</td>
                        <td class="p-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-wide" :class="modeClass(row.accounting_mode)">
                                {{ row.accounting_mode }}
                            </span>
                        </td>
                        <td class="p-3">{{ row.description }}</td>
                        <td class="p-3">{{ number(row.amount) }}</td>
                        <td class="p-3">{{ number(row.paid_amount) }}</td>
                        <td class="p-3">{{ number(row.outstanding_amount) }}</td>
                        <td class="p-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-wide" :class="paymentStatusClass(row.payment_status)">
                                {{ row.payment_status }}
                            </span>
                        </td>
                        <td class="p-3">
                            <span
                                v-if="row.is_recurring"
                                class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-indigo-700"
                            >
                                {{ row.recurrence }}
                            </span>
                            <span v-else class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">No</span>
                            <p v-if="row.is_recurring" class="mt-1 text-xs text-slate-500">Next: {{ row.next_due_date }}</p>
                        </td>
                        <td class="p-3 text-right space-x-3">
                            <button class="text-xs font-semibold text-amber-700" @click="openEditModal(row)">Edit</button>
                            <button
                                class="text-xs font-semibold text-emerald-700 disabled:cursor-not-allowed disabled:opacity-40"
                                :disabled="Number(row.outstanding_amount ?? 0) <= 0"
                                @click="openPayModal(row)"
                            >
                                Pay
                            </button>
                            <button class="text-xs font-semibold text-rose-700" @click="remove(row.id)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td colspan="10" class="p-4 text-center text-slate-500">No expenses recorded for this month.</td>
                    </tr>
                </tbody>
            </table>
            <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 px-5 py-3 text-xs text-slate-600">
                <p>Page {{ expensePagination.page }} of {{ expensePagination.last_page }} | {{ expensePagination.total }} expenses</p>
                <div class="flex gap-2">
                    <button class="rounded-lg border border-slate-300 px-3 py-1 font-semibold text-slate-700 disabled:opacity-50" :disabled="isExpensePrevDisabled" @click="prevExpensePage">Prev</button>
                    <button class="rounded-lg border border-slate-300 px-3 py-1 font-semibold text-slate-700 disabled:opacity-50" :disabled="isExpenseNextDisabled" @click="nextExpensePage">Next</button>
                </div>
            </footer>
        </article>

        <AppModal v-model="showEditModal" title="Edit Expense" size="md">
            <form class="grid gap-3" @submit.prevent="submitEditExpense">
                <input v-model="editForm.category" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Category">
                <input v-model="editForm.amount" required type="number" min="0" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Amount">
                <input v-model="editForm.expense_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <select v-model="editForm.accounting_mode" class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="cash">Cash Expense</option>
                    <option value="payable">Accrued Payable</option>
                    <option value="prepaid">Prepaid Expense</option>
                </select>
                <input
                    v-if="editForm.accounting_mode === 'payable'"
                    v-model="editForm.payable_due_date"
                    type="date"
                    class="rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Payable due date"
                >
                <template v-if="editForm.accounting_mode === 'prepaid'">
                    <input v-model="editForm.prepaid_start_date" type="date" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Prepaid start date">
                    <input v-model="editForm.prepaid_months" type="number" min="1" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Prepaid months">
                </template>
                <textarea v-model="editForm.description" required rows="3" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Description"></textarea>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input v-model="editForm.is_recurring" type="checkbox">
                    Recurring expense
                </label>
                <select v-if="editForm.is_recurring" v-model="editForm.recurrence" class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="yearly">Yearly</option>
                </select>
                <input v-if="editForm.is_recurring" v-model="editForm.next_due_date" type="date" class="rounded-lg border border-slate-300 px-3 py-2">

                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="showEditModal = false">Cancel</button>
                    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Save Changes</button>
                </div>
            </form>
        </AppModal>

        <AppModal v-model="showPayModal" title="Record Expense Payment" size="md">
            <form class="grid gap-3" @submit.prevent="submitPayExpense">
                <p class="text-sm text-slate-600">Settle payable amounts for accrued expense entries.</p>

                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                    <div class="mb-2 flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <span>Payment History</span>
                        <span>Total Paid: {{ number(expensePaymentMeta.total_paid) }}</span>
                    </div>

                    <p v-if="expensePaymentRows.length === 0" class="text-xs text-slate-500">No payments recorded for this expense yet.</p>

                    <div v-else class="space-y-2">
                        <div
                            v-for="payment in expensePaymentRows"
                            :key="payment.id"
                            class="rounded-lg border border-slate-200 bg-white px-3 py-2"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="text-xs font-semibold text-slate-700">{{ payment.payment_date }} • {{ payment.payment_method }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ payment.reference_number || 'No reference' }}
                                        <span v-if="payment.notes">• {{ payment.notes }}</span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-semibold text-slate-800">{{ number(payment.amount) }}</p>
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-rose-700"
                                        @click="removeExpensePayment(payment.id)"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <input v-model="payForm.amount" required type="number" min="0.01" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Payment amount">
                <input v-model="payForm.payment_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <input v-model="payForm.payment_method" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Payment method (bank_transfer, cash, etc.)">
                <input v-model="payForm.reference_number" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Reference number (optional)">
                <textarea v-model="payForm.notes" rows="2" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Notes (optional)"></textarea>

                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="showPayModal = false">Cancel</button>
                    <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Record Payment</button>
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

const month = ref(new Date().toISOString().slice(0, 10));
const rows = ref([]);
const summary = ref({});
const expenseFilters = reactive({
    page: 1,
    per_page: 20,
});
const expensePagination = ref({
    page: 1,
    per_page: 20,
    total: 0,
    last_page: 1,
});
const showEditModal = ref(false);
const showPayModal = ref(false);
const editExpenseId = ref(null);
const payExpenseId = ref(null);
const expensePaymentRows = ref([]);
const expensePaymentMeta = ref({
    total_paid: 0,
});
const variableTotal = computed(() => Number(summary.value.monthly_total ?? 0) - Number(summary.value.recurring_total ?? 0));

const form = reactive({
    category: '',
    description: '',
    amount: '',
    accounting_mode: 'cash',
    expense_date: new Date().toISOString().slice(0, 10),
    payable_due_date: new Date().toISOString().slice(0, 10),
    prepaid_start_date: new Date().toISOString().slice(0, 10),
    prepaid_months: '12',
    is_recurring: false,
    recurrence: 'monthly',
    next_due_date: new Date().toISOString().slice(0, 10),
});
const editForm = reactive({
    category: '',
    description: '',
    amount: '',
    accounting_mode: 'cash',
    expense_date: new Date().toISOString().slice(0, 10),
    payable_due_date: new Date().toISOString().slice(0, 10),
    prepaid_start_date: new Date().toISOString().slice(0, 10),
    prepaid_months: '12',
    is_recurring: false,
    recurrence: 'monthly',
    next_due_date: new Date().toISOString().slice(0, 10),
});
const payForm = reactive({
    amount: '',
    payment_date: new Date().toISOString().slice(0, 10),
    payment_method: 'bank_transfer',
    reference_number: '',
    notes: '',
});

onMounted(load);

async function load() {
    try {
        const [list, totals] = await Promise.all([
            FinanceService.expenses({
                month: month.value,
                page: expenseFilters.page,
                per_page: expenseFilters.per_page,
            }),
            FinanceService.expenseSummary({ month: month.value }),
        ]);

        rows.value = list.data.data ?? [];
        summary.value = totals.data ?? {};
        syncPagination(expensePagination.value, list.data, expenseFilters.per_page);
        expenseFilters.page = expensePagination.value.page;
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load expenses.'));
    }
}

async function createExpense() {
    try {
        await FinanceService.createExpense({
            category: form.category,
            description: form.description,
            amount: Number(form.amount),
            accounting_mode: form.accounting_mode,
            expense_date: form.expense_date,
            payable_due_date: form.accounting_mode === 'payable' ? form.payable_due_date : undefined,
            prepaid_start_date: form.accounting_mode === 'prepaid' ? form.prepaid_start_date : undefined,
            prepaid_months: form.accounting_mode === 'prepaid' ? Number(form.prepaid_months) : undefined,
            is_recurring: Boolean(form.is_recurring),
            recurrence: form.is_recurring ? form.recurrence : undefined,
            next_due_date: form.is_recurring ? form.next_due_date : undefined,
        });

        form.category = '';
        form.description = '';
        form.amount = '';
        form.accounting_mode = 'cash';
        form.expense_date = new Date().toISOString().slice(0, 10);
        form.payable_due_date = new Date().toISOString().slice(0, 10);
        form.prepaid_start_date = new Date().toISOString().slice(0, 10);
        form.prepaid_months = '12';
        form.is_recurring = false;
        form.recurrence = 'monthly';
        form.next_due_date = new Date().toISOString().slice(0, 10);

        toast.success('Expense created successfully.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to create expense.'));
    }
}

async function remove(id) {
    try {
        await FinanceService.deleteExpense(id);
        toast.success('Expense deleted successfully.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to delete expense.'));
    }
}

function openEditModal(row) {
    editExpenseId.value = row.id;
    editForm.category = row.category ?? '';
    editForm.description = row.description ?? '';
    editForm.amount = String(row.amount ?? '0');
    editForm.accounting_mode = row.accounting_mode ?? 'cash';
    editForm.expense_date = row.expense_date ?? new Date().toISOString().slice(0, 10);
    editForm.payable_due_date = row.payable_due_date ?? editForm.expense_date;
    editForm.prepaid_start_date = row.prepaid_start_date ?? editForm.expense_date;
    editForm.prepaid_months = String(row.prepaid_months ?? '12');
    editForm.is_recurring = Boolean(row.is_recurring);
    editForm.recurrence = row.recurrence ?? 'monthly';
    editForm.next_due_date = row.next_due_date ?? editForm.expense_date;
    showEditModal.value = true;
}

async function submitEditExpense() {
    if (!editExpenseId.value) {
        return;
    }

    try {
        await FinanceService.updateExpense(editExpenseId.value, {
            category: editForm.category,
            description: editForm.description,
            amount: Number(editForm.amount),
            accounting_mode: editForm.accounting_mode,
            expense_date: editForm.expense_date,
            payable_due_date: editForm.accounting_mode === 'payable' ? editForm.payable_due_date : null,
            prepaid_start_date: editForm.accounting_mode === 'prepaid' ? editForm.prepaid_start_date : null,
            prepaid_months: editForm.accounting_mode === 'prepaid' ? Number(editForm.prepaid_months) : null,
            is_recurring: Boolean(editForm.is_recurring),
            recurrence: editForm.is_recurring ? editForm.recurrence : null,
            next_due_date: editForm.is_recurring ? editForm.next_due_date : null,
        });

        showEditModal.value = false;
        toast.success('Expense updated successfully.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to update expense.'));
    }
}

async function openPayModal(row) {
    payExpenseId.value = row.id;
    payForm.amount = String(row.outstanding_amount ?? row.amount ?? '0');
    payForm.payment_date = new Date().toISOString().slice(0, 10);
    payForm.payment_method = 'bank_transfer';
    payForm.reference_number = '';
    payForm.notes = '';

    await loadExpensePayments(row.id);
    showPayModal.value = true;
}

async function submitPayExpense() {
    if (!payExpenseId.value) {
        return;
    }

    try {
        await FinanceService.recordExpensePayment(payExpenseId.value, {
            amount: Number(payForm.amount),
            payment_date: payForm.payment_date,
            payment_method: payForm.payment_method || 'bank_transfer',
            reference_number: payForm.reference_number || null,
            notes: payForm.notes || null,
        });

        toast.success('Expense payment recorded successfully.');
        await loadExpensePayments(payExpenseId.value);
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to record expense payment.'));
    }
}

async function loadExpensePayments(expenseId) {
    try {
        const response = await FinanceService.expensePayments(expenseId);
        expensePaymentRows.value = response.data.rows ?? [];
        expensePaymentMeta.value = {
            total_paid: Number(response.data.total_paid ?? 0),
        };
    } catch (error) {
        expensePaymentRows.value = [];
        expensePaymentMeta.value = { total_paid: 0 };
        toast.error(getApiErrorMessage(error, 'Unable to load expense payments.'));
    }
}

async function removeExpensePayment(paymentId) {
    if (!payExpenseId.value) {
        return;
    }

    if (!confirm('Delete this expense payment?')) {
        return;
    }

    try {
        await FinanceService.deleteExpensePayment(payExpenseId.value, paymentId);
        toast.success('Expense payment deleted successfully.');
        await loadExpensePayments(payExpenseId.value);
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to delete expense payment.'));
    }
}

function modeClass(mode) {
    const value = String(mode ?? '').toLowerCase();

    if (value === 'payable') {
        return 'bg-rose-100 text-rose-700';
    }

    if (value === 'prepaid') {
        return 'bg-indigo-100 text-indigo-700';
    }

    return 'bg-emerald-100 text-emerald-700';
}

function paymentStatusClass(status) {
    const value = String(status ?? '').toLowerCase();

    if (value === 'paid') {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (value === 'partial') {
        return 'bg-amber-100 text-amber-700';
    }

    return 'bg-rose-100 text-rose-700';
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}

const isExpensePrevDisabled = computed(() => (expensePagination.value.page ?? 1) <= 1);
const isExpenseNextDisabled = computed(() => (expensePagination.value.page ?? 1) >= (expensePagination.value.last_page ?? 1));

async function onExpensePerPageChange() {
    expenseFilters.page = 1;
    await load();
}

async function prevExpensePage() {
    if (isExpensePrevDisabled.value) {
        return;
    }

    expenseFilters.page -= 1;
    await load();
}

async function nextExpensePage() {
    if (isExpenseNextDisabled.value) {
        return;
    }

    expenseFilters.page += 1;
    await load();
}

function syncPagination(target, payload, fallbackPerPage = 20) {
    target.page = Number(payload.current_page ?? 1);
    target.per_page = Number(payload.per_page ?? fallbackPerPage);
    target.total = Number(payload.total ?? 0);
    target.last_page = Number(payload.last_page ?? 1);
}
</script>
