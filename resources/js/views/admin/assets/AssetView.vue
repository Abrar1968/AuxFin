<template>
    <section class="space-y-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="font-bold">Register Asset</h3>
            <form class="mt-3 grid md:grid-cols-3 gap-3" @submit.prevent="createAsset">
                <input v-model="form.name" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Asset name">
                <input v-model="form.category" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Category">
                <input v-model="form.purchase_cost" required type="number" min="0" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Purchase cost">
                <input v-model="form.purchase_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <input v-model="form.useful_life_months" required type="number" min="1" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Useful life (months)">
                <button class="rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-semibold">Save Asset</button>
            </form>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Name</th>
                        <th class="text-left p-3">Category</th>
                        <th class="text-left p-3">Purchase Cost</th>
                        <th class="text-left p-3">Book Value</th>
                        <th class="text-left p-3">Monthly Dep.</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100">
                        <td class="p-3">{{ row.name }}</td>
                        <td class="p-3">{{ row.category }}</td>
                        <td class="p-3">{{ number(row.purchase_cost) }}</td>
                        <td class="p-3">{{ number(row.current_book_value) }}</td>
                        <td class="p-3">{{ number(row.monthly_depreciation) }}</td>
                        <td class="p-3 capitalize">{{ row.status }}</td>
                        <td class="p-3 text-right space-x-3">
                            <button class="text-xs font-semibold text-blue-700" @click="depreciate(row.id)">Depreciate</button>
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
import { FinanceService } from '../../../services/finance.service';
import { getApiErrorMessage } from '../../../utils/api-error';
import { useToastStore } from '../../../stores/toast.store';

const toast = useToastStore();
const rows = ref([]);

const form = reactive({
    name: '',
    category: '',
    purchase_cost: '',
    purchase_date: new Date().toISOString().slice(0, 10),
    useful_life_months: '36',
});

onMounted(load);

async function load() {
    try {
        const response = await FinanceService.assets();
        rows.value = response.data.data ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load assets.'));
    }
}

async function createAsset() {
    try {
        await FinanceService.createAsset({
            name: form.name,
            category: form.category,
            purchase_cost: Number(form.purchase_cost),
            purchase_date: form.purchase_date,
            useful_life_months: Number(form.useful_life_months),
        });

        form.name = '';
        form.category = '';
        form.purchase_cost = '';
        form.purchase_date = new Date().toISOString().slice(0, 10);
        form.useful_life_months = '36';

        toast.success('Asset created successfully.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to create asset.'));
    }
}

async function depreciate(id) {
    try {
        await FinanceService.depreciateAsset(id);
        toast.success('Asset depreciated successfully.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to depreciate asset.'));
    }
}

async function remove(id) {
    try {
        await FinanceService.deleteAsset(id);
        toast.success('Asset deleted successfully.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to delete asset.'));
    }
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}
</script>
