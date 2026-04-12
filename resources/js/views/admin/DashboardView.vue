<template>
    <section class="space-y-5">
        <div class="grid md:grid-cols-4 gap-4">
            <article
                v-for="card in cards"
                :key="card.label"
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <p class="text-xs uppercase tracking-wider text-slate-500">{{ card.label }}</p>
                <p class="text-2xl font-bold mt-1">{{ card.value }}</p>
            </article>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-bold">Latest Snapshot</h3>
            <pre class="mt-3 p-3 rounded-lg bg-slate-900 text-emerald-200 text-xs overflow-auto">{{ latest }}</pre>
        </article>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { AnalyticsService } from '../../services/analytics.service';

const latest = ref({});

const cards = computed(() => [
    { label: 'Revenue', value: number(latest.value.total_revenue) },
    { label: 'Payroll', value: number(latest.value.total_payroll) },
    { label: 'Net Profit', value: number(latest.value.net_profit) },
    { label: 'Accounts Receivable', value: number(latest.value.total_ar) },
]);

onMounted(async () => {
    const response = await AnalyticsService.overview();
    latest.value = response.data.latest ?? {};
});

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(Number(v ?? 0));
}
</script>
