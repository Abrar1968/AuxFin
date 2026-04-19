<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Financial Reporting</p>
                <h1 class="text-2xl font-black text-slate-900">Reporting Workspace</h1>
                <p class="mt-1 text-sm text-slate-600">Generate executive-ready statements for profitability, tax exposure, and receivable aging.</p>
            </div>
        </header>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Reporting Controls</h2>
            <div class="mt-3 grid md:grid-cols-3 gap-3">
                <div>
                    <label class="text-xs font-semibold text-slate-600">From Month</label>
                    <input v-model="filters.from_month" type="date" class="block mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">To Month</label>
                    <input v-model="filters.to_month" type="date" class="block mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">AR As Of Date</label>
                    <input v-model="filters.as_of" type="date" class="block mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold" @click="loadAll">Refresh Reports</button>
                <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="downloadProfitLoss">Export P&L PDF</button>
                <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="downloadTax">Export Tax PDF</button>
                <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="downloadAging">Export AR Aging PDF</button>
            </div>
        </article>

        <div v-if="loading" class="grid gap-4">
            <SkeletonLoader height="16" />
            <SkeletonLoader height="16" />
            <SkeletonLoader height="16" />
        </div>

        <template v-else>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Profit &amp; Loss</h3>
                    <span class="text-xs text-slate-600">{{ profitLoss.from }} to {{ profitLoss.to }}</span>
                </div>

                <div class="grid md:grid-cols-4 gap-3 text-sm">
                    <div class="rounded-lg bg-slate-100 p-3">Revenue: <strong>{{ number(profitLoss.totals?.revenue) }}</strong></div>
                    <div class="rounded-lg bg-slate-100 p-3">Net Profit: <strong>{{ number(profitLoss.totals?.net_profit) }}</strong></div>
                    <div class="rounded-lg bg-slate-100 p-3">Tax: <strong>{{ number(profitLoss.totals?.estimated_tax) }}</strong></div>
                    <div class="rounded-lg bg-emerald-100 p-3">After Tax: <strong>{{ number(profitLoss.totals?.profit_after_tax) }}</strong></div>
                </div>

                <LineChart :values="profitAfterTaxSeries" color="#2563eb" :height="180" />
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Tax Summary</h3>
                    <span class="text-xs text-slate-600">Rate: {{ taxSummary.tax_rate_percent ?? 0 }}%</span>
                </div>

                <BarChart :labels="taxMonths" :values="taxSeries" :height="200" />
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">AR Aging</h3>
                    <span class="text-xs text-slate-600">Health: {{ arAging.health?.status ?? 'n/a' }} ({{ arAging.health?.score ?? 0 }})</span>
                </div>

                <div class="grid md:grid-cols-4 gap-3 text-sm">
                    <div v-for="bucket in agingBuckets" :key="bucket.key" class="rounded-lg bg-slate-100 p-3">
                        <p class="font-semibold">{{ bucket.key }}</p>
                        <p>{{ number(bucket.amount) }}</p>
                        <p class="text-xs text-slate-600">{{ bucket.percent }}%</p>
                    </div>
                </div>

                <table class="w-full text-sm">
                    <thead class="bg-slate-100 text-slate-600">
                        <tr>
                            <th class="text-left p-3">Invoice</th>
                            <th class="text-left p-3">Client</th>
                            <th class="text-left p-3">Age</th>
                            <th class="text-left p-3">Bucket</th>
                            <th class="text-left p-3">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in arAging.items" :key="row.invoice_id" class="border-t border-slate-100 hover:bg-slate-50/70">
                            <td class="p-3">{{ row.invoice_number }}</td>
                            <td class="p-3">{{ row.client_name ?? '-' }}</td>
                            <td class="p-3">{{ row.age_days }}</td>
                            <td class="p-3">{{ row.bucket }}</td>
                            <td class="p-3">{{ number(row.outstanding) }}</td>
                        </tr>
                        <tr v-if="(arAging.items ?? []).length === 0">
                            <td class="p-3 text-slate-500" colspan="5">No outstanding invoices.</td>
                        </tr>
                    </tbody>
                </table>
            </article>
        </template>
    </section>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import BarChart from '../../../components/charts/BarChart.vue';
import LineChart from '../../../components/charts/LineChart.vue';
import SkeletonLoader from '../../../components/layout/SkeletonLoader.vue';
import { ReportService } from '../../../services/report.service';
import { useToastStore } from '../../../stores/toast.store';
import { getApiErrorMessage } from '../../../utils/api-error';
import { exportArAgingPdf, exportProfitLossPdf, exportTaxSummaryPdf } from '../../../utils/report-pdf';

const toast = useToastStore();

const loading = ref(false);
const profitLoss = ref({ rows: [], totals: {} });
const taxSummary = ref({ rows: [], totals: {} });
const arAging = ref({ distribution: {}, items: [], health: {} });

const now = new Date();
const filters = reactive({
    from_month: new Date(now.getFullYear(), now.getMonth() - 5, 1).toISOString().slice(0, 10),
    to_month: new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10),
    as_of: now.toISOString().slice(0, 10),
});

const profitAfterTaxSeries = computed(() => (profitLoss.value.rows ?? []).map((row) => Number(row.profit_after_tax ?? 0)));
const taxMonths = computed(() => (taxSummary.value.rows ?? []).map((row) => row.month));
const taxSeries = computed(() => (taxSummary.value.rows ?? []).map((row) => Number(row.corporate_tax_estimate ?? 0)));

const agingBuckets = computed(() => Object.entries(arAging.value.distribution ?? {}).map(([key, row]) => ({
    key,
    amount: Number(row.amount ?? 0),
    percent: Number(row.percent ?? 0),
})));

async function loadAll() {
    loading.value = true;

    try {
        const params = {
            from_month: filters.from_month,
            to_month: filters.to_month,
        };

        const [pl, tx, ar] = await Promise.all([
            ReportService.profitLoss(params),
            ReportService.taxSummary(params),
            ReportService.arAging({ as_of: filters.as_of }),
        ]);

        profitLoss.value = pl.data;
        taxSummary.value = tx.data;
        arAging.value = ar.data;
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load reports.'));
    } finally {
        loading.value = false;
    }
}

function downloadProfitLoss() {
    exportProfitLossPdf(profitLoss.value);
    toast.success('Profit and loss PDF export started.');
}

function downloadTax() {
    exportTaxSummaryPdf(taxSummary.value);
    toast.success('Tax summary PDF export started.');
}

function downloadAging() {
    exportArAgingPdf(arAging.value);
    toast.success('AR aging PDF export started.');
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}

loadAll();
</script>
