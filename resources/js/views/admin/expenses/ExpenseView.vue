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

        <div class="grid gap-3 sm:grid-cols-3">
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
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Add Expense</h2>
            <form class="mt-3 grid md:grid-cols-3 gap-3" @submit.prevent="createExpense">
                <input v-model="form.category" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Category">
                <input v-model="form.amount" required type="number" min="0" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Amount">
                <input v-model="form.expense_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
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
                <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Expense Ledger</h3>
            </header>
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Date</th>
                        <th class="text-left p-3">Category</th>
                        <th class="text-left p-3">Description</th>
                        <th class="text-left p-3">Amount</th>
                        <th class="text-left p-3">Recurring</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100 hover:bg-slate-50/70">
                        <td class="p-3">{{ row.expense_date }}</td>
                        <td class="p-3">{{ row.category }}</td>
                        <td class="p-3">{{ row.description }}</td>
                        <td class="p-3">{{ number(row.amount) }}</td>
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
                            <button class="text-xs font-semibold text-rose-700" @click="remove(row.id)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td colspan="6" class="p-4 text-center text-slate-500">No expenses recorded for this month.</td>
                    </tr>
                </tbody>
            </table>
        </article>

        <AppModal v-model="showEditModal" title="Edit Expense" size="md">
            <form class="grid gap-3" @submit.prevent="submitEditExpense">
                <input v-model="editForm.category" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Category">
                <input v-model="editForm.amount" required type="number" min="0" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Amount">
                <input v-model="editForm.expense_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
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
const showEditModal = ref(false);
const editExpenseId = ref(null);
const variableTotal = computed(() => Number(summary.value.monthly_total ?? 0) - Number(summary.value.recurring_total ?? 0));

const form = reactive({
    category: '',
    description: '',
    amount: '',
    expense_date: new Date().toISOString().slice(0, 10),
    is_recurring: false,
    recurrence: 'monthly',
    next_due_date: new Date().toISOString().slice(0, 10),
});
const editForm = reactive({
    category: '',
    description: '',
    amount: '',
    expense_date: new Date().toISOString().slice(0, 10),
    is_recurring: false,
    recurrence: 'monthly',
    next_due_date: new Date().toISOString().slice(0, 10),
});

onMounted(load);

async function load() {
    try {
        const [list, totals] = await Promise.all([
            FinanceService.expenses({ month: month.value }),
            FinanceService.expenseSummary({ month: month.value }),
        ]);

        rows.value = list.data.data ?? [];
        summary.value = totals.data ?? {};
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
            expense_date: form.expense_date,
            is_recurring: Boolean(form.is_recurring),
            recurrence: form.is_recurring ? form.recurrence : undefined,
            next_due_date: form.is_recurring ? form.next_due_date : undefined,
        });

        form.category = '';
        form.description = '';
        form.amount = '';
        form.expense_date = new Date().toISOString().slice(0, 10);
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
    editForm.expense_date = row.expense_date ?? new Date().toISOString().slice(0, 10);
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
            expense_date: editForm.expense_date,
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

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}
</script>
