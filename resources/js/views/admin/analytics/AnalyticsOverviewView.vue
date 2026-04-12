<template>
    <section class="space-y-5">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs font-semibold text-slate-600">Available Cash (for runway)</label>
                <input v-model.number="availableCash" type="number" min="0" class="block mt-1 rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold" @click="load">Refresh</button>
        </div>

        <div v-if="loading" class="grid gap-3">
            <SkeletonLoader height="16" />
            <SkeletonLoader height="16" />
            <SkeletonLoader height="16" />
        </div>

        <template v-else>
            <div class="grid md:grid-cols-4 gap-3">
                <article class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs text-slate-500 uppercase">Revenue</p>
                    <p class="text-xl font-bold mt-1">{{ number(latest.total_revenue) }}</p>
                    <SparkLine :data="revenueSeries" color="#2563eb" />
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs text-slate-500 uppercase">Net Profit</p>
                    <p class="text-xl font-bold mt-1">{{ number(latest.net_profit) }}</p>
                    <SparkLine :data="netProfitSeries" color="#16a34a" />
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs text-slate-500 uppercase">Burn Rate</p>
                    <p class="text-xl font-bold mt-1">{{ number(latest.burn_rate) }}</p>
                    <p class="text-xs text-slate-500 mt-2">Runway: {{ burnRate.cash_runway_months ?? 0 }} months</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs text-slate-500 uppercase">AR Health</p>
                    <p class="text-xl font-bold mt-1">{{ arHealth.score ?? 0 }}</p>
                    <p class="text-xs text-slate-500 mt-2">{{ arHealth.status ?? 'n/a' }}</p>
                </article>
            </div>

            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <h3 class="font-bold">Revenue Trend (Last 12 Months)</h3>
                <LineChart :values="revenueSeries" color="#2563eb" :height="210" />
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <h3 class="font-bold">CMGR Metrics</h3>
                <BarChart :labels="cmgrLabels" :values="cmgrValues" :height="220" />
            </article>

            <div class="grid md:grid-cols-2 gap-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h3 class="font-bold">Monte Carlo Forecast</h3>
                    <div class="mt-3 grid grid-cols-3 gap-3 text-sm">
                        <div class="rounded-lg bg-slate-100 p-3">P10: <strong>{{ number(forecast.p10) }}</strong></div>
                        <div class="rounded-lg bg-slate-100 p-3">P50: <strong>{{ number(forecast.p50) }}</strong></div>
                        <div class="rounded-lg bg-slate-100 p-3">P90: <strong>{{ number(forecast.p90) }}</strong></div>
                    </div>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h3 class="font-bold">Runway Health</h3>
                    <GaugeChart :value="runwayPercent" />
                    <p class="text-sm text-slate-600 text-center">Target runway: 12 months</p>
                    <div class="mt-3">
                        <ProgressBar :value="runwayPercent" />
                    </div>
                </article>
            </div>

            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-bold">Expense Anomalies (Z-Score)</h3>
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
import { computed, ref } from 'vue';
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

const latest = computed(() => analytics.overview?.latest ?? {});
const series = computed(() => analytics.overview?.series ?? []);
const revenueSeries = computed(() => series.value.map((row) => Number(row.total_revenue ?? 0)));
const netProfitSeries = computed(() => series.value.map((row) => Number(row.net_profit ?? 0)));
const forecast = computed(() => analytics.forecast ?? {});
const arHealth = computed(() => analytics.arHealth ?? {});
const burnRate = computed(() => analytics.burnRate ?? {});
const anomalyRows = computed(() => analytics.anomalies ?? []);

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
        await analytics.fetchAll(Number(availableCash.value ?? 0));
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load analytics overview.'));
    } finally {
        loading.value = false;
    }
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}

load();
</script>
