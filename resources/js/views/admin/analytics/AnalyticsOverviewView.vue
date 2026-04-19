<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Strategic Intelligence</p>
                <h1 class="text-2xl font-black text-slate-900">Analytics Overview</h1>
                <p class="mt-1 text-sm text-slate-600">Track profitability, burn trajectory, forecasting confidence, and anomaly risk at a glance.</p>
            </div>
        </header>

        <article class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Timeframe Scope</label>
                    <select v-model="timeframe" class="mt-1 block rounded-xl border border-slate-300 px-3 py-2.5 text-sm min-w-44">
                        <option v-for="option in timeframeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Available Cash (Runway Input)</label>
                    <input v-model.number="availableCash" type="number" min="0" class="mt-1 block rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                </div>
                <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700" @click="load">Refresh Analytics</button>
            </div>
        </article>

        <div v-if="loading" class="grid gap-3">
            <SkeletonLoader height="16" />
            <SkeletonLoader height="16" />
            <SkeletonLoader height="16" />
        </div>

        <template v-else>
            <div class="grid md:grid-cols-4 gap-3">
                <article class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs text-slate-500 uppercase tracking-wide">Revenue</p>
                    <p class="text-xl font-bold mt-1">{{ number(latest.total_revenue) }}</p>
                    <SparkLine :data="revenueSeries" color="#2563eb" />
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs text-slate-500 uppercase tracking-wide">Net Profit</p>
                    <p class="text-xl font-bold mt-1">{{ number(latest.net_profit) }}</p>
                    <SparkLine :data="netProfitSeries" color="#16a34a" />
                </article>
                <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-xs text-amber-700 uppercase tracking-wide">Burn Rate</p>
                    <p class="text-xl font-bold mt-1 text-amber-800">{{ number(latest.burn_rate) }}</p>
                    <p class="text-xs text-slate-500 mt-2">Runway: {{ burnRate.cash_runway_months ?? 0 }} months</p>
                </article>
                <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                    <p class="text-xs text-indigo-700 uppercase tracking-wide">AR Health</p>
                    <p class="text-xl font-bold mt-1 text-indigo-900">{{ arHealth.score ?? 0 }}</p>
                    <p class="text-xs text-slate-500 mt-2">{{ arHealth.status ?? 'n/a' }}</p>
                </article>
            </div>

            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Revenue Trend ({{ activeTimeframeLabel }})</h3>
                <LineChart :values="revenueSeries" color="#2563eb" :height="210" />
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">CMGR Metrics</h3>
                <BarChart :labels="cmgrLabels" :values="cmgrValues" :height="220" />
            </article>

            <div class="grid md:grid-cols-2 gap-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Monte Carlo Forecast</h3>
                    <div class="mt-3 grid grid-cols-3 gap-3 text-sm">
                        <div class="rounded-lg bg-slate-100 p-3">P10: <strong>{{ number(forecast.p10) }}</strong></div>
                        <div class="rounded-lg bg-slate-100 p-3">P50: <strong>{{ number(forecast.p50) }}</strong></div>
                        <div class="rounded-lg bg-slate-100 p-3">P90: <strong>{{ number(forecast.p90) }}</strong></div>
                    </div>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Runway Health</h3>
                    <GaugeChart :value="runwayPercent" />
                    <p class="text-sm text-slate-600 text-center">Target runway: 12 months</p>
                    <div class="mt-3">
                        <ProgressBar :value="runwayPercent" />
                    </div>
                </article>
            </div>

            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Expense Anomalies (Z-Score)</h3>
                    <span class="text-xs text-slate-600">Detected: {{ anomalyRows.length }}</span>
                </div>

                <table class="w-full text-sm mt-3">
                    <thead class="bg-slate-100 text-slate-600">
                        <tr>
                            <th class="text-left p-3">Value</th>
                            <th class="text-left p-3">Z-Score</th>
                            <th class="text-left p-3">Flag</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, idx) in anomalyRows.slice(0, 12)" :key="idx" class="border-t border-slate-100">
                            <td class="p-3">{{ number(row.value) }}</td>
                            <td class="p-3">{{ Number(row.z_score ?? 0).toFixed(2) }}</td>
                            <td class="p-3">
                                <span :class="row.is_anomaly ? 'text-rose-700 font-semibold' : 'text-slate-600'">
                                    {{ row.is_anomaly ? 'Anomaly' : 'Normal' }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="anomalyRows.length === 0">
                            <td class="p-3 text-slate-500" colspan="3">No anomalies available.</td>
                        </tr>
                    </tbody>
                </table>
            </article>
        </template>
    </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import BarChart from '../../../components/charts/BarChart.vue';
import GaugeChart from '../../../components/charts/GaugeChart.vue';
import LineChart from '../../../components/charts/LineChart.vue';
import ProgressBar from '../../../components/charts/ProgressBar.vue';
import SparkLine from '../../../components/charts/SparkLine.vue';
import SkeletonLoader from '../../../components/layout/SkeletonLoader.vue';
import { useAnalyticsStore } from '../../../stores/analytics.store';
import { useToastStore } from '../../../stores/toast.store';
import { getApiErrorMessage } from '../../../utils/api-error';

const analytics = useAnalyticsStore();
const toast = useToastStore();
const loading = ref(false);
const availableCash = ref(0);
const timeframe = ref('month');

const timeframeOptions = [
    { value: 'day', label: 'Day Wise' },
    { value: 'week', label: 'Week Wise' },
    { value: 'month', label: 'Month Wise' },
    { value: 'year', label: 'Year Wise' },
];

const latest = computed(() => analytics.overview?.latest ?? {});
const series = computed(() => analytics.overview?.series ?? []);
const revenueSeries = computed(() => series.value.map((row) => Number(row.total_revenue ?? 0)));
const netProfitSeries = computed(() => series.value.map((row) => Number(row.net_profit ?? 0)));
const forecast = computed(() => analytics.forecast ?? {});
const arHealth = computed(() => analytics.arHealth ?? {});
const burnRate = computed(() => analytics.burnRate ?? {});
const anomalyRows = computed(() => analytics.anomalies ?? []);
const activeTimeframeLabel = computed(() => timeframeOptions.find((option) => option.value === timeframe.value)?.label ?? 'Month Wise');

const cmgrLabels = computed(() => [
    'Revenue',
    'Payroll',
    'OpEx',
    'Net Profit',
    'Headcount',
    'AR',
]);

const cmgrValues = computed(() => {
    const row = analytics.cmgr ?? {};
    return [
        Number(row.revenue_cmgr ?? 0),
        Number(row.payroll_cmgr ?? 0),
        Number(row.opex_cmgr ?? 0),
        Number(row.net_profit_cmgr ?? 0),
        Number(row.headcount_cmgr ?? 0),
        Number(row.ar_cmgr ?? 0),
    ];
});

const runwayPercent = computed(() => {
    const runway = Number(burnRate.value.cash_runway_months ?? 0);
    return Math.max(0, Math.min(100, (runway / 12) * 100));
});

async function load() {
    loading.value = true;
    try {
        await analytics.fetchAll(Number(availableCash.value ?? 0), {
            timeframe: timeframe.value,
        });
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load analytics overview.'));
    } finally {
        loading.value = false;
    }
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}

watch(timeframe, () => {
    load();
});

load();
</script>
