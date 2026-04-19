<template>
    <section class="space-y-5">
        <header class="fin-card-panel p-5 md:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-2">
                    <p class="inline-flex rounded-full border border-sky-300/40 bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-sky-700">
                        Superadmin Command Center
                    </p>
                    <h1 class="text-2xl font-extrabold text-slate-900 md:text-3xl">Executive Operations Dashboard</h1>
                    <p class="max-w-3xl text-sm text-slate-600">
                        Monitor growth, financial health, cash runway, and anomaly risk in one operational cockpit designed for fast strategic decisions.
                    </p>
                    <div class="flex flex-wrap gap-2 text-xs font-semibold">
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-emerald-700">Realtime Analytics</span>
                        <span class="rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-cyan-700">Forecast & Risk Signals</span>
                        <span class="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-indigo-700">Operational Readiness</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white/85 p-4 shadow-sm min-w-60">
                    <p class="text-xs font-semibold uppercase tracking-[0.11em] text-slate-500">Last Synced</p>
                    <p class="mt-1 text-sm font-bold text-slate-800">{{ lastUpdatedLabel }}</p>
                    <p class="mt-2 text-xs text-slate-500">
                        {{ autoRefreshEnabled ? 'Auto refresh every 90 seconds.' : 'Manual refresh mode enabled.' }}
                    </p>
                </div>
            </div>
        </header>

        <article class="fin-card p-5 md:p-6 space-y-4">
            <div class="grid gap-4 xl:grid-cols-[1.15fr_1fr]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.11em] text-slate-500">Time Window</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="option in WINDOW_OPTIONS"
                            :key="option.value"
                            type="button"
                            class="rounded-full border px-3 py-1.5 text-xs font-semibold transition"
                            :class="activeWindow === option.value
                                ? 'border-sky-500 bg-sky-500 text-white shadow-[0_8px_18px_rgba(2,132,199,.28)]'
                                : 'border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700'"
                            @click="activeWindow = option.value"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.11em] text-slate-500" for="trend_metric">Trend Focus Metric</label>
                    <select
                        id="trend_metric"
                        v-model="trendMetric"
                        class="fin-focus-ring mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700"
                    >
                        <option v-for="option in TREND_METRIC_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <label class="flex-1 min-w-55">
                    <span class="text-xs font-semibold uppercase tracking-[0.11em] text-slate-500">Available Cash Simulation</span>
                    <input
                        v-model.number="availableCash"
                        type="number"
                        min="0"
                        step="1000"
                        class="fin-focus-ring mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700"
                        @input="hasCustomCashInput = true"
                    >
                </label>

                <button
                    type="button"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:-translate-y-px hover:border-cyan-300 hover:text-cyan-700 disabled:cursor-not-allowed disabled:opacity-70"
                    :disabled="isRunwayLoading || isLoading"
                    @click="refreshRunway"
                >
                    {{ isRunwayLoading ? 'Simulating...' : 'Simulate Runway' }}
                </button>

                <button
                    type="button"
                    class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_rgba(15,23,42,.22)] transition hover:-translate-y-px hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-70"
                    :disabled="isLoading"
                    @click="loadDashboard"
                >
                    {{ isLoading ? 'Refreshing...' : 'Refresh Dashboard' }}
                </button>

                <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700">
                    <input v-model="autoRefreshEnabled" type="checkbox" class="h-4 w-4 accent-cyan-600">
                    Auto Refresh
                </label>
            </div>
        </article>

        <div v-if="isLoading && !hasData" class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <SkeletonLoader v-for="idx in 4" :key="idx" height="14" />
        </div>

        <template v-else>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <KpiCard
                    v-for="card in cards"
                    :key="card.label"
                    :title="card.label"
                    :subtitle="card.subtitle"
                    :value="card.value"
                    :delta="card.delta"
                    :delta-type="card.deltaType"
                    :sparkline-data="card.sparkline"
                />
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <article class="fin-card-panel p-5 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-base font-bold text-slate-900">Cash Collection Forecast</h3>
                        <span class="rounded-full border border-cyan-200 bg-cyan-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-widest text-cyan-700">
                            Monte Carlo
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-sm">
                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                            <p class="text-xs text-slate-500">P10</p>
                            <p class="mt-1 font-bold text-slate-800">{{ number(forecast.p10) }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                            <p class="text-xs text-slate-500">P50</p>
                            <p class="mt-1 font-bold text-slate-800">{{ number(forecast.p50) }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                            <p class="text-xs text-slate-500">P90</p>
                            <p class="mt-1 font-bold text-slate-800">{{ number(forecast.p90) }}</p>
                        </div>
                    </div>

                    <p class="text-xs text-slate-500">
                        Simulation count: {{ Number(forecast.simulations ?? 0) || 0 }}
                    </p>
                </article>

                <article class="fin-card-panel p-5 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-base font-bold text-slate-900">Runway Scenario</h3>
                        <span class="rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-widest text-sky-700">
                            {{ runwayMonths.toFixed(1) }} months
                        </span>
                    </div>

                    <GaugeChart :value="runwayPercent" />
                    <ProgressBar :value="runwayPercent" />
                    <p class="text-xs text-slate-500">Target runway threshold: 12 months</p>
                </article>

                <article class="fin-card-panel p-5 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-base font-bold text-slate-900">AR Health Index</h3>
                        <span class="rounded-full border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-widest" :class="arHealthStatusClass">
                            {{ arHealthStatus }}
                        </span>
                    </div>

                    <GaugeChart :value="arHealthScore" />
                    <p class="text-center text-sm font-semibold text-slate-700">Score {{ arHealthScore.toFixed(2) }}%</p>
                    <p class="text-xs text-center text-slate-500">Weighted by receivable aging buckets and collection risk.</p>
                </article>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <GrowthVelocityCard label="Revenue CMGR" :value="growthVelocity.revenue_cmgr ?? 0" />
                <GrowthVelocityCard label="Payroll CMGR" :value="growthVelocity.payroll_cmgr ?? 0" />
                <GrowthVelocityCard label="Net Profit CMGR" :value="growthVelocity.net_profit_cmgr ?? 0" />
                <GrowthVelocityCard label="Headcount CMGR" :value="growthVelocity.headcount_cmgr ?? 0" />
            </div>

            <div class="grid gap-4 xl:grid-cols-[1.75fr_1fr]">
                <article class="fin-card-panel p-5 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Performance Trend</h3>
                            <p class="text-xs text-slate-500">{{ trendMetricLabel }} across {{ activeWindowLabel }}</p>
                        </div>
                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-slate-600">
                            {{ filteredSeries.length }} points
                        </span>
                    </div>

                    <LineChart :values="trendSeries" :color="trendColor" :height="240" />

                    <div class="grid grid-cols-3 gap-2 text-sm">
                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                            <p class="text-xs text-slate-500">Current</p>
                            <p class="mt-1 font-bold text-slate-800">{{ number(trendStats.current) }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                            <p class="text-xs text-slate-500">Peak</p>
                            <p class="mt-1 font-bold text-slate-800">{{ number(trendStats.high) }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                            <p class="text-xs text-slate-500">Low</p>
                            <p class="mt-1 font-bold text-slate-800">{{ number(trendStats.low) }}</p>
                        </div>
                    </div>
                </article>

                <article class="fin-card-panel p-5 space-y-4">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-base font-bold text-slate-900">Anomaly & Actions</h3>
                        <span class="rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-widest text-rose-700">
                            {{ topAnomalies.length }} flagged
                        </span>
                    </div>

                    <div class="space-y-2">
                        <AnomalyAlert
                            v-for="(row, index) in topAnomalies"
                            :key="index"
                            :title="`Expense outlier #${index + 1}`"
                            :description="`Z-score ${Number(row.z_score ?? 0).toFixed(2)} with value ${number(row.value)}`"
                            :critical="Boolean(row.is_anomaly)"
                        />

                        <p v-if="topAnomalies.length === 0" class="rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-500">
                            No active anomalies detected in recent expense patterns.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <h4 class="text-xs font-semibold uppercase tracking-widest text-slate-500">Recommended Actions</h4>
                        <ul class="mt-2 space-y-2 text-sm text-slate-700">
                            <li v-for="item in recommendations" :key="item" class="rounded-lg bg-slate-50 px-3 py-2">{{ item }}</li>
                        </ul>
                    </div>
                </article>
            </div>
        </template>
    </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import GaugeChart from '../../components/charts/GaugeChart.vue';
import LineChart from '../../components/charts/LineChart.vue';
import ProgressBar from '../../components/charts/ProgressBar.vue';
import AnomalyAlert from '../../components/domain/analytics/AnomalyAlert.vue';
import GrowthVelocityCard from '../../components/domain/analytics/GrowthVelocityCard.vue';
import SkeletonLoader from '../../components/layout/SkeletonLoader.vue';
import KpiCard from '../../components/ui/KpiCard.vue';
import { AnalyticsService } from '../../services/analytics.service';
import { useToastStore } from '../../stores/toast.store';
import { getApiErrorMessage } from '../../utils/api-error';

const WINDOW_OPTIONS = [
    { value: '3m', label: 'Last 3 Months', points: 3 },
    { value: '6m', label: 'Last 6 Months', points: 6 },
    { value: '12m', label: 'Last 12 Months', points: 12 },
];

const TREND_METRIC_OPTIONS = [
    { value: 'revenue', label: 'Revenue', key: 'total_revenue', color: '#0284c7' },
    { value: 'payroll', label: 'Payroll', key: 'total_payroll', color: '#0f766e' },
    { value: 'net_profit', label: 'Net Profit', key: 'net_profit', color: '#16a34a' },
    { value: 'accounts_receivable', label: 'Accounts Receivable', key: 'total_ar', color: '#7c3aed' },
];

const latest = ref({});
const series = ref([]);
const cmgr = ref({});
const anomalies = ref([]);
const forecast = ref({});
const arHealth = ref({});
const burnRate = ref({});
const growth = ref({ velocity: {}, payroll_efficiency: {} });

const isLoading = ref(false);
const isRunwayLoading = ref(false);
const activeWindow = ref('12m');
const trendMetric = ref('revenue');
const availableCash = ref(0);
const hasCustomCashInput = ref(false);
const autoRefreshEnabled = ref(true);
const lastUpdatedAt = ref(null);

const toast = useToastStore();
let refreshIntervalId = null;

const hasData = computed(() => series.value.length > 0);
const growthVelocity = computed(() => growth.value.velocity ?? {});

const suggestedCashFromLatest = computed(() => {
    const totalRevenue = Number(latest.value.total_revenue ?? 0);
    const netProfit = Number(latest.value.net_profit ?? 0);
    const base = totalRevenue + Math.max(netProfit, 0);
    return Math.max(50000, Math.round(base || 0));
});

const pointCount = computed(() => WINDOW_OPTIONS.find((row) => row.value === activeWindow.value)?.points ?? 12);
const filteredSeries = computed(() => {
    const rows = series.value ?? [];
    const sliceFrom = Math.max(0, rows.length - pointCount.value);
    return rows.slice(sliceFrom);
});

const trendMetricConfig = computed(() => {
    return TREND_METRIC_OPTIONS.find((row) => row.value === trendMetric.value) ?? TREND_METRIC_OPTIONS[0];
});

const trendSeries = computed(() => {
    const key = trendMetricConfig.value.key;
    return filteredSeries.value.map((row) => Number(row?.[key] ?? 0));
});

const trendMetricLabel = computed(() => trendMetricConfig.value.label);
const trendColor = computed(() => trendMetricConfig.value.color);
const activeWindowLabel = computed(() => WINDOW_OPTIONS.find((row) => row.value === activeWindow.value)?.label ?? 'Last 12 Months');

const trendStats = computed(() => {
    const values = trendSeries.value;
    if (!values.length) {
        return { current: 0, high: 0, low: 0 };
    }

    return {
        current: values[values.length - 1],
        high: Math.max(...values),
        low: Math.min(...values),
    };
});

const cards = computed(() => [
    {
        label: 'Revenue',
        subtitle: 'Current cycle total',
        value: number(latest.value.total_revenue),
        delta: `${Number(cmgr.value.revenue_cmgr ?? 0).toFixed(2)}%`,
        deltaType: Number(cmgr.value.revenue_cmgr ?? 0) >= 0 ? 'up' : 'down',
        sparkline: filteredSeries.value.map((row) => Number(row.total_revenue ?? 0)),
    },
    {
        label: 'Payroll',
        subtitle: 'Compensation load',
        value: number(latest.value.total_payroll),
        delta: `${Number(cmgr.value.payroll_cmgr ?? 0).toFixed(2)}%`,
        deltaType: Number(cmgr.value.payroll_cmgr ?? 0) >= 0 ? 'up' : 'down',
        sparkline: filteredSeries.value.map((row) => Number(row.total_payroll ?? 0)),
    },
    {
        label: 'Net Profit',
        subtitle: 'After operating costs',
        value: number(latest.value.net_profit),
        delta: `${Number(cmgr.value.net_profit_cmgr ?? 0).toFixed(2)}%`,
        deltaType: Number(cmgr.value.net_profit_cmgr ?? 0) >= 0 ? 'up' : 'down',
        sparkline: filteredSeries.value.map((row) => Number(row.net_profit ?? 0)),
    },
    {
        label: 'Accounts Receivable',
        subtitle: 'Outstanding invoices',
        value: number(latest.value.total_ar),
        delta: `${Number(cmgr.value.ar_cmgr ?? 0).toFixed(2)}%`,
        deltaType: Number(cmgr.value.ar_cmgr ?? 0) >= 0 ? 'up' : 'down',
        sparkline: filteredSeries.value.map((row) => Number(row.total_ar ?? 0)),
    },
]);

const runwayMonths = computed(() => Number(burnRate.value.cash_runway_months ?? 0));
const runwayPercent = computed(() => Math.max(0, Math.min(100, (runwayMonths.value / 12) * 100)));

const arHealthScore = computed(() => Number(arHealth.value.score ?? 0));
const arHealthStatus = computed(() => String(arHealth.value.status ?? 'watch'));
const arHealthStatusClass = computed(() => {
    return {
        excellent: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        good: 'border-teal-200 bg-teal-50 text-teal-700',
        watch: 'border-amber-200 bg-amber-50 text-amber-700',
        critical: 'border-rose-200 bg-rose-50 text-rose-700',
    }[arHealthStatus.value] ?? 'border-slate-200 bg-slate-50 text-slate-700';
});

const topAnomalies = computed(() => {
    return (anomalies.value ?? [])
        .filter((row) => Boolean(row?.is_anomaly))
        .sort((a, b) => Math.abs(Number(b?.z_score ?? 0)) - Math.abs(Number(a?.z_score ?? 0)))
        .slice(0, 4);
});

const recommendations = computed(() => {
    const tips = [];

    if (runwayMonths.value < 6) {
        tips.push('Increase near-term collections or reduce discretionary spend to extend runway beyond 6 months.');
    }

    if (arHealthScore.value < 70) {
        tips.push('AR health is below target; prioritize follow-ups on invoices beyond 30 days.');
    }

    if (String(growth.value.payroll_efficiency?.status ?? '') === 'critical') {
        tips.push('Payroll efficiency is critical; review staffing productivity and margin contribution by function.');
    }

    if (Number(forecast.value.p10 ?? 0) < Number(forecast.value.p50 ?? 0) * 0.7) {
        tips.push('Forecast downside spread is wide; preserve liquidity buffer for low-probability downside outcomes.');
    }

    if (topAnomalies.value.length > 2) {
        tips.push('Multiple anomalies detected; run category-level expense audit before month close.');
    }

    if (tips.length === 0) {
        tips.push('Core indicators are stable; continue current execution cadence with weekly monitoring.');
    }

    return tips.slice(0, 4);
});

const lastUpdatedLabel = computed(() => {
    if (!lastUpdatedAt.value) {
        return 'Awaiting first sync';
    }

    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(lastUpdatedAt.value);
});

watch(autoRefreshEnabled, (enabled) => {
    if (enabled) {
        startAutoRefresh();
    } else {
        stopAutoRefresh();
    }
});

onMounted(async () => {
    await loadDashboard();
    startAutoRefresh();
});

onUnmounted(() => {
    stopAutoRefresh();
});

async function loadDashboard() {
    if (isLoading.value) {
        return;
    }

    isLoading.value = true;

    try {
        const [overviewResponse, cmgrResponse, anomalyResponse, forecastResponse, arHealthResponse, growthResponse] = await Promise.all([
            AnalyticsService.overview(),
            AnalyticsService.cmgr(),
            AnalyticsService.anomalies(),
            AnalyticsService.forecast(),
            AnalyticsService.arHealth(),
            AnalyticsService.growth(),
        ]);

        latest.value = overviewResponse.data.latest ?? {};
        series.value = overviewResponse.data.series ?? [];
        cmgr.value = cmgrResponse.data ?? {};
        anomalies.value = anomalyResponse.data ?? [];
        forecast.value = forecastResponse.data ?? {};
        arHealth.value = arHealthResponse.data ?? {};
        growth.value = growthResponse.data ?? { velocity: {}, payroll_efficiency: {} };

        if (!hasCustomCashInput.value) {
            availableCash.value = suggestedCashFromLatest.value;
        }

        await refreshRunway();
        lastUpdatedAt.value = new Date();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load executive dashboard metrics.'));
    } finally {
        isLoading.value = false;
    }
}

async function refreshRunway() {
    if (isRunwayLoading.value) {
        return;
    }

    isRunwayLoading.value = true;

    try {
        const response = await AnalyticsService.burnRate(Number(availableCash.value ?? 0));
        burnRate.value = response.data ?? {};
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to simulate runway with current cash input.'));
    } finally {
        isRunwayLoading.value = false;
    }
}

function startAutoRefresh() {
    stopAutoRefresh();

    if (!autoRefreshEnabled.value) {
        return;
    }

    refreshIntervalId = window.setInterval(() => {
        loadDashboard();
    }, 90_000);
}

function stopAutoRefresh() {
    if (!refreshIntervalId) {
        return;
    }

    window.clearInterval(refreshIntervalId);
    refreshIntervalId = null;
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}
</script>
