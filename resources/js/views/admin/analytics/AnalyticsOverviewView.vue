<template>
    <section class="space-y-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-bold">Forecast (Monte Carlo)</h3>
            <div class="mt-3 grid sm:grid-cols-3 gap-3 text-sm">
                <div class="rounded-lg bg-slate-100 p-3">P10: <strong>{{ forecast.p10 ?? 0 }}</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">P50: <strong>{{ forecast.p50 ?? 0 }}</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">P90: <strong>{{ forecast.p90 ?? 0 }}</strong></div>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-bold">Expense Anomalies (Z-Score)</h3>
            <pre class="mt-3 p-3 rounded-lg bg-slate-900 text-amber-200 text-xs overflow-auto">{{ anomalies }}</pre>
        </article>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { AnalyticsService } from '../../../services/analytics.service';

const forecast = ref({});
const anomalies = ref([]);

onMounted(async () => {
    const [f, a] = await Promise.all([AnalyticsService.forecast(), AnalyticsService.anomalies()]);
    forecast.value = f.data;
    anomalies.value = a.data;
});
</script>
