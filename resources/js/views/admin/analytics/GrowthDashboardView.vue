<template>
    <section class="space-y-5">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-bold">Growth Velocity Dashboard</h3>
            <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold" @click="load">Refresh</button>
        </div>

        <div v-if="loading" class="grid gap-3">
            <SkeletonLoader height="16" />
            <SkeletonLoader height="16" />
        </div>

        <template v-else>
            <div class="grid md:grid-cols-3 gap-3">
                <article v-for="item in velocityCards" :key="item.label" class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ item.label }}</p>
                    <p class="text-xl font-bold mt-1">{{ item.value }}%</p>
                </article>
            </div>

            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <h4 class="font-bold">Net Profit Trend</h4>
                <LineChart :values="netProfitSeries" color="#16a34a" :height="200" />
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <h4 class="font-bold">Headcount Trend</h4>
                <BarChart :labels="monthLabels" :values="headcountSeries" :height="200" />
            </article>

            <div class="grid md:grid-cols-3 gap-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Efficiency Ratio</p>
                    <p class="text-xl font-bold mt-1">{{ Number(growth.efficiency_ratio ?? 0).toFixed(2) }}</p>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Revenue Quality</p>
                    <GaugeChart :value="Number(growth.revenue_quality_score ?? 0)" />
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Payroll Efficiency</p>
                    <p class="text-sm mt-2">Revenue per Employee: <strong>{{ number(growth.payroll_efficiency?.revenue_per_employee) }}</strong></p>
                    <p class="text-sm mt-1">Payroll Ratio: <strong>{{ Number(growth.payroll_efficiency?.payroll_ratio ?? 0).toFixed(2) }}%</strong></p>
                    <p class="text-sm mt-1">Status: <strong class="uppercase">{{ growth.payroll_efficiency?.status ?? 'watch' }}</strong></p>
                </article>
            </div>

            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <h4 class="font-bold">Automated Recommendations</h4>
                <ul class="mt-3 space-y-2 text-sm">
                    <li v-for="item in recommendations" :key="item" class="rounded-lg bg-slate-100 px-3 py-2">{{ item }}</li>
                </ul>
            </article>
        </template>
    </section>
</template>

<script setup>
import { computed, ref } from 'vue';
import BarChart from '../../../components/charts/BarChart.vue';
import GaugeChart from '../../../components/charts/GaugeChart.vue';
import LineChart from '../../../components/charts/LineChart.vue';
import SkeletonLoader from '../../../components/layout/SkeletonLoader.vue';
import { AnalyticsService } from '../../../services/analytics.service';
import { useToastStore } from '../../../stores/toast.store';
import { getApiErrorMessage } from '../../../utils/api-error';

const toast = useToastStore();
const loading = ref(false);
const growth = ref({ velocity: {}, series: [] });

const velocity = computed(() => growth.value.velocity ?? {});
const series = computed(() => growth.value.series ?? []);

const velocityCards = computed(() => [
    { label: 'Revenue CMGR', value: Number(velocity.value.revenue_cmgr ?? 0).toFixed(2) },
    { label: 'Payroll CMGR', value: Number(velocity.value.payroll_cmgr ?? 0).toFixed(2) },
    { label: 'Headcount CMGR', value: Number(velocity.value.headcount_cmgr ?? 0).toFixed(2) },
    { label: 'Net Profit CMGR', value: Number(velocity.value.net_profit_cmgr ?? 0).toFixed(2) },
    { label: 'OpEx CMGR', value: Number(velocity.value.opex_cmgr ?? 0).toFixed(2) },
    { label: 'AR CMGR', value: Number(velocity.value.ar_cmgr ?? 0).toFixed(2) },
]);

const monthLabels = computed(() => series.value.map((row) => String(row.snapshot_month ?? '').slice(0, 7)));
const headcountSeries = computed(() => series.value.map((row) => Number(row.headcount ?? 0)));
const netProfitSeries = computed(() => series.value.map((row) => Number(row.net_profit ?? 0)));

const recommendations = computed(() => {
    const list = [];

    if (Number(velocity.value.revenue_cmgr ?? 0) <= Number(velocity.value.payroll_cmgr ?? 0)) {
        list.push('Revenue CMGR is below payroll CMGR; review hiring pace and compensation growth.');
    }

    if (Number(growth.value.revenue_quality_score ?? 0) < 50) {
        list.push('Revenue quality is below 50%; strengthen invoice collection and AR follow-up.');
    }

    if (String(growth.value.payroll_efficiency?.status ?? '') === 'critical') {
        list.push('Payroll efficiency status is critical; run workforce productivity and margin optimization review.');
    }

    if (list.length === 0) {
        list.push('Growth metrics are healthy across tracked dimensions.');
    }

    return list;
});

async function load() {
    loading.value = true;
    try {
        const response = await AnalyticsService.growth();
        growth.value = response.data ?? { velocity: {}, series: [] };
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load growth analytics.'));
    } finally {
        loading.value = false;
    }
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}

load();
</script>
