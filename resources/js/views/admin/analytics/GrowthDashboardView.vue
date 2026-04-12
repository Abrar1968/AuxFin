<template>
    <section class="space-y-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-bold">Growth Velocity</h3>
            <div class="mt-3 grid md:grid-cols-3 gap-3 text-sm">
                <div class="rounded-lg bg-slate-100 p-3">Revenue CMGR: <strong>{{ velocity.revenue_cmgr ?? 0 }}%</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">Payroll CMGR: <strong>{{ velocity.payroll_cmgr ?? 0 }}%</strong></div>
                <div class="rounded-lg bg-slate-100 p-3">Headcount CMGR: <strong>{{ velocity.headcount_cmgr ?? 0 }}%</strong></div>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm">Growth Efficiency Ratio: <strong>{{ growth.efficiency_ratio ?? 0 }}</strong></p>
            <p class="text-sm mt-2">Revenue Quality Score: <strong>{{ growth.revenue_quality_score ?? 0 }}%</strong></p>
        </article>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { AnalyticsService } from '../../../services/analytics.service';

const growth = ref({});
const velocity = ref({});

onMounted(async () => {
    const response = await AnalyticsService.growth();
    growth.value = response.data;
    velocity.value = response.data.velocity ?? {};
});
</script>
