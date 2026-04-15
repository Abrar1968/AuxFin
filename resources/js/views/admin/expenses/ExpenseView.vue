<template>
    <section class="space-y-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Month</label>
                    <input v-model="month" type="date" class="block mt-1 rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold" @click="load">Refresh</button>
            </div>

            <div class="mt-3 grid sm:grid-cols-2 gap-3 text-sm">
                <div class="rounded-lg bg-slate-100 p-3">Monthly Total: <strong>{{ number(summary.monthly_total) }}</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">Recurring Baseline: <strong>{{ number(summary.recurring_total) }}</strong></div>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="font-bold">Add Expense</h3>
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

        <article class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
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
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100">
                        <td class="p-3">{{ row.expense_date }}</td>
                        <td class="p-3">{{ row.category }}</td>
                        <td class="p-3">{{ row.description }}</td>
                        <td class="p-3">{{ number(row.amount) }}</td>
                        <td class="p-3">{{ row.is_recurring ? `${row.recurrence} (${row.next_due_date})` : 'No' }}</td>
                        <td class="p-3 text-right space-x-3">
                            <button class="text-xs font-semibold text-amber-700" @click="openEditModal(row)">Edit</button>
                            <button class="text-xs font-semibold text-rose-700" @click="remove(row.id)">Delete</button>
                        </td>
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
import { onMounted, reactive, ref } from 'vue';
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
