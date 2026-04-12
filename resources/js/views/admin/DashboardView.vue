<template>
    <section class="space-y-5">
        <header>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900">Admin Dashboard</h1>
            <p class="text-sm text-slate-600 mt-1">Operational finance intelligence with realtime growth and anomaly visibility.</p>
        </header>

        <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-4">
            <KpiCard
                v-for="card in cards"
                :key="card.label"
                :title="card.label"
                :value="card.value"
                :delta="card.delta"
                :delta-type="card.deltaType"
                :sparkline-data="card.sparkline"
            />
        </div>

        <div class="grid lg:grid-cols-3 gap-4">
            <GrowthVelocityCard label="Revenue CMGR" :value="cmgr.revenue_cmgr ?? 0" />
            <GrowthVelocityCard label="Payroll CMGR" :value="cmgr.payroll_cmgr ?? 0" />
            <GrowthVelocityCard label="Net Profit CMGR" :value="cmgr.net_profit_cmgr ?? 0" />
        </div>

        <article class="fin-card-panel p-5">
            <h3 class="font-bold text-slate-900">Revenue Trend</h3>
            <LineChart :values="revenueSeries" color="#4f46e5" :height="210" />
        </article>

        <article class="fin-card-panel p-5 space-y-3">
            <h3 class="font-bold text-slate-900">Anomaly Alerts</h3>
            <AnomalyAlert
                v-for="(row, index) in topAnomalies"
                :key="index"
                :title="`Expense point ${index + 1}`"
                :description="`Z-score ${Number(row.z_score ?? 0).toFixed(2)} with value ${number(row.value)}`"
                :critical="Boolean(row.is_anomaly)"
            />
            <p v-if="topAnomalies.length === 0" class="text-sm text-slate-500">No anomalies detected.</p>
        </article>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import AnomalyAlert from '../../components/domain/analytics/AnomalyAlert.vue';
import GrowthVelocityCard from '../../components/domain/analytics/GrowthVelocityCard.vue';
import LineChart from '../../components/charts/LineChart.vue';
import KpiCard from '../../components/ui/KpiCard.vue';
import { AnalyticsService } from '../../services/analytics.service';
import { getApiErrorMessage } from '../../utils/api-error';
import { useToastStore } from '../../stores/toast.store';

const latest = ref({});
const series = ref([]);
const cmgr = ref({});
const anomalies = ref([]);
const toast = useToastStore();

const revenueSeries = computed(() => series.value.map((row) => Number(row.total_revenue ?? 0)));
const topAnomalies = computed(() => anomalies.value.filter((row) => row.is_anomaly).slice(0, 4));

const cards = computed(() => [
    {
        label: 'Revenue',
        value: number(latest.value.total_revenue),
        delta: `${Number(cmgr.value.revenue_cmgr ?? 0).toFixed(2)}%`,
        deltaType: Number(cmgr.value.revenue_cmgr ?? 0) >= 0 ? 'up' : 'down',
        sparkline: revenueSeries.value,
    },
    {
        label: 'Payroll',
        value: number(latest.value.total_payroll),
        delta: `${Number(cmgr.value.payroll_cmgr ?? 0).toFixed(2)}%`,
        deltaType: Number(cmgr.value.payroll_cmgr ?? 0) >= 0 ? 'up' : 'down',
        sparkline: series.value.map((row) => Number(row.total_payroll ?? 0)),
    },
    {
        label: 'Net Profit',
        value: number(latest.value.net_profit),
        delta: `${Number(cmgr.value.net_profit_cmgr ?? 0).toFixed(2)}%`,
        deltaType: Number(cmgr.value.net_profit_cmgr ?? 0) >= 0 ? 'up' : 'down',
        sparkline: series.value.map((row) => Number(row.net_profit ?? 0)),
    },
    {
        label: 'Accounts Receivable',
        value: number(latest.value.total_ar),
        delta: `${Number(cmgr.value.ar_cmgr ?? 0).toFixed(2)}%`,
        deltaType: Number(cmgr.value.ar_cmgr ?? 0) >= 0 ? 'up' : 'down',
        sparkline: series.value.map((row) => Number(row.total_ar ?? 0)),
    },
]);

onMounted(async () => {
    try {
        const [overviewResponse, cmgrResponse, anomalyResponse] = await Promise.all([
            AnalyticsService.overview(),
            AnalyticsService.cmgr(),
            AnalyticsService.anomalies(),
        ]);

        latest.value = overviewResponse.data.latest ?? {};
        series.value = overviewResponse.data.series ?? [];
        cmgr.value = cmgrResponse.data ?? {};
        anomalies.value = anomalyResponse.data ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load admin dashboard analytics.'));
    }
});

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(Number(v ?? 0));
}
</script>
