<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Asset Intelligence</p>
                <h1 class="text-2xl font-black text-slate-900">Fixed Asset Registry</h1>
                <p class="mt-1 text-sm text-slate-600">Manage capex entries, depreciation cycles, and current book valuation.</p>
            </div>

            <button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="load">
                Refresh Registry
            </button>
        </header>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Asset Count</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ assetPagination.total }}</p>
            </article>
            <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-indigo-700">Purchase Value</p>
                <p class="mt-2 text-2xl font-black text-indigo-900">{{ number(totalPurchaseCost) }}</p>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-emerald-700">Current Book Value</p>
                <p class="mt-2 text-2xl font-black text-emerald-800">{{ number(totalBookValue) }}</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-amber-700">Monthly Depreciation</p>
                <p class="mt-2 text-2xl font-black text-amber-800">{{ number(totalMonthlyDepreciation) }}</p>
            </article>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Register Asset</h2>
            <form class="mt-3 grid md:grid-cols-3 gap-3" @submit.prevent="createAsset">
                <input v-model="form.name" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Asset name">
                <input v-model="form.category" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Category">
                <input v-model="form.purchase_cost" required type="number" min="0" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Purchase cost">
                <input v-model="form.purchase_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <input v-model="form.useful_life_months" required type="number" min="1" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Useful life (months)">
                <button class="rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-semibold">Save Asset</button>
            </form>
        </article>

        <article class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <header class="border-b border-slate-200 px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Asset Ledger</h3>
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                        <span>Per page</span>
                        <select v-model.number="assetFilters.per_page" class="rounded-lg border border-slate-300 px-2 py-1" @change="onAssetPerPageChange">
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
                        <th class="text-left p-3">Category</th>
                        <th class="text-left p-3">Purchase Cost</th>
                        <th class="text-left p-3">Book Value</th>
                        <th class="text-left p-3">Monthly Dep.</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100 hover:bg-slate-50/70">
                        <td class="p-3">{{ row.name }}</td>
                        <td class="p-3">{{ row.category }}</td>
                        <td class="p-3">{{ number(row.purchase_cost) }}</td>
                        <td class="p-3">{{ number(row.current_book_value) }}</td>
                        <td class="p-3">{{ number(row.monthly_depreciation) }}</td>
                        <td class="p-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize" :class="statusClass(row.status)">
                                {{ row.status }}
                            </span>
                        </td>
                        <td class="p-3 text-right space-x-3">
                            <button class="text-xs font-semibold text-amber-700" @click="openEditModal(row)">Edit</button>
                            <button class="text-xs font-semibold text-blue-700" @click="depreciate(row.id)">Depreciate</button>
                            <button class="text-xs font-semibold text-rose-700" @click="remove(row.id)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td colspan="7" class="p-4 text-center text-slate-500">No assets found.</td>
                    </tr>
                </tbody>
            </table>
            <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 px-5 py-3 text-xs text-slate-600">
                <p>Page {{ assetPagination.page }} of {{ assetPagination.last_page }} | {{ assetPagination.total }} assets</p>
                <div class="flex gap-2">
                    <button class="rounded-lg border border-slate-300 px-3 py-1 font-semibold text-slate-700 disabled:opacity-50" :disabled="isAssetPrevDisabled" @click="prevAssetPage">Prev</button>
                    <button class="rounded-lg border border-slate-300 px-3 py-1 font-semibold text-slate-700 disabled:opacity-50" :disabled="isAssetNextDisabled" @click="nextAssetPage">Next</button>
                </div>
            </footer>
        </article>

        <AppModal v-model="showEditModal" title="Edit Asset" size="md">
            <form class="grid gap-3" @submit.prevent="submitEditAsset">
                <input v-model="editForm.name" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Asset name">
                <input v-model="editForm.category" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Category">
                <input v-model="editForm.purchase_cost" required type="number" min="0.01" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Purchase cost">
                <input v-model="editForm.purchase_date" required type="date" class="rounded-lg border border-slate-300 px-3 py-2">
                <input v-model="editForm.useful_life_months" required type="number" min="1" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Useful life months">
                <select v-model="editForm.status" required class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="active">Active</option>
                    <option value="disposed">Disposed</option>
                    <option value="fully_depreciated">Fully Depreciated</option>
                </select>

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
const rows = ref([]);
const assetFilters = reactive({
    page: 1,
    per_page: 20,
});
const assetPagination = ref({
    page: 1,
    per_page: 20,
    total: 0,
    last_page: 1,
});
const showEditModal = ref(false);
const editAssetId = ref(null);

const totalPurchaseCost = computed(() => rows.value.reduce((sum, row) => sum + Number(row.purchase_cost ?? 0), 0));
const totalBookValue = computed(() => rows.value.reduce((sum, row) => sum + Number(row.current_book_value ?? 0), 0));
const totalMonthlyDepreciation = computed(() => rows.value.reduce((sum, row) => sum + Number(row.monthly_depreciation ?? 0), 0));

const form = reactive({
    name: '',
    category: '',
    purchase_cost: '',
    purchase_date: new Date().toISOString().slice(0, 10),
    useful_life_months: '36',
});
const editForm = reactive({
    name: '',
    category: '',
    purchase_cost: '',
    purchase_date: new Date().toISOString().slice(0, 10),
    useful_life_months: '36',
    status: 'active',
});

onMounted(load);

async function load() {
    try {
        const response = await FinanceService.assets({
            page: assetFilters.page,
            per_page: assetFilters.per_page,
        });

        rows.value = response.data.data ?? [];
        syncPagination(assetPagination.value, response.data, assetFilters.per_page);
        assetFilters.page = assetPagination.value.page;
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

function openEditModal(row) {
    editAssetId.value = row.id;
    editForm.name = row.name ?? '';
    editForm.category = row.category ?? '';
    editForm.purchase_cost = String(row.purchase_cost ?? '0');
    editForm.purchase_date = row.purchase_date ?? new Date().toISOString().slice(0, 10);
    editForm.useful_life_months = String(row.useful_life_months ?? '36');
    editForm.status = row.status ?? 'active';
    showEditModal.value = true;
}

async function submitEditAsset() {
    if (!editAssetId.value) {
        return;
    }

    try {
        await FinanceService.updateAsset(editAssetId.value, {
            name: editForm.name,
            category: editForm.category,
            purchase_cost: Number(editForm.purchase_cost),
            purchase_date: editForm.purchase_date,
            useful_life_months: Number(editForm.useful_life_months),
            status: editForm.status,
        });

        showEditModal.value = false;
        toast.success('Asset updated successfully.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to update asset.'));
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

function statusClass(status) {
    const value = String(status ?? '').toLowerCase();

    if (value === 'active') {
        return 'bg-indigo-100 text-indigo-700';
    }

    if (value === 'disposed') {
        return 'bg-amber-100 text-amber-700';
    }

    if (value === 'fully_depreciated') {
        return 'bg-emerald-100 text-emerald-700';
    }

    return 'bg-slate-100 text-slate-700';
}

const isAssetPrevDisabled = computed(() => (assetPagination.value.page ?? 1) <= 1);
const isAssetNextDisabled = computed(() => (assetPagination.value.page ?? 1) >= (assetPagination.value.last_page ?? 1));

async function onAssetPerPageChange() {
    assetFilters.page = 1;
    await load();
}

async function prevAssetPage() {
    if (isAssetPrevDisabled.value) {
        return;
    }

    assetFilters.page -= 1;
    await load();
}

async function nextAssetPage() {
    if (isAssetNextDisabled.value) {
        return;
    }

    assetFilters.page += 1;
    await load();
}

function syncPagination(target, payload, fallbackPerPage = 20) {
    target.page = Number(payload.current_page ?? 1);
    target.per_page = Number(payload.per_page ?? fallbackPerPage);
    target.total = Number(payload.total ?? 0);
    target.last_page = Number(payload.last_page ?? 1);
}
</script>
